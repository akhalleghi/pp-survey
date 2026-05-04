<?php

namespace App\Support;

/**
 * کلیدهای دسترسی به بخش‌های پنل (برای نقش supervisor).
 * مدیر اصلی (admin) به همه دسترسی دارد و این محدودیت‌ها اعمال نمی‌شود.
 */
final class AdminPermissions
{
    public const DASHBOARD = 'dashboard';

    public const ORG_UNITS = 'org.units';

    public const ORG_POSITIONS = 'org.positions';

    public const ORG_PERSONNEL = 'org.personnel';

    public const ORG_SUPERVISORS = 'org.supervisors';

    public const SETTINGS = 'settings';

    public const SURVEYS = 'surveys';

    /**
     * عنوان فارسی هر کلید دسترسی (منبع اصلی برای نمایش در پنل).
     * هنگام افزودن بخش جدید، ثابت را بالا تعریف کنید و اینجا عنوان بگذارید؛
     * اگر آیتمی فقط در navigationTemplate() با permission اضافه شود، برای چک‌باکس‌ها از همان برچسب استفاده می‌شود.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::DASHBOARD => 'داشبورد',
            self::ORG_UNITS => 'واحدهای سازمانی',
            self::ORG_POSITIONS => 'چارت سمت‌ها',
            self::ORG_PERSONNEL => 'پرسنل سازمان',
            self::ORG_SUPERVISORS => 'سرپرستان واحدها',
            self::SETTINGS => 'تنظیمات سازمان',
            self::SURVEYS => 'نظرسنجی‌ها',
        ];
    }

    /**
     * نقشهٔ permission => عنوان از قالب منوی مدیریت (زیرآیتم‌ها و آیتم‌های تک‌سطح).
     *
     * @return array<string, string>
     */
    private static function permissionLabelsFromNavigation(): array
    {
        $map = [];
        $walk = function (array $node) use (&$map, &$walk): void {
            $p = $node['permission'] ?? null;
            if (is_string($p) && $p !== '') {
                $map[$p] = $node['label'] ?? $p;
            }
            foreach ($node['children'] ?? [] as $child) {
                $walk($child);
            }
        };
        foreach (self::navigationTemplate() as $item) {
            $walk($item);
        }

        return $map;
    }

    /**
     * فهرست چک‌باکس «بخش‌های قابل مشاهده» برای ناظر: ترکیب {@see labels()} با هر کلیدی که در منو تعریف شده و هنوز در labels نیست.
     * ترتیب بر اساس عنوان فارسی است تا با افزودن بخش جدید، بدون تغییر Blade لیست به‌روز شود.
     *
     * @return array<string, string>
     */
    public static function supervisorCheckboxDefinitions(): array
    {
        $merged = self::labels();
        foreach (self::permissionLabelsFromNavigation() as $key => $label) {
            if (!isset($merged[$key])) {
                $merged[$key] = $label;
            }
        }
        uasort($merged, static fn (string $a, string $b): int => strcmp($a, $b));

        return $merged;
    }

    /**
     * چک‌باکس‌های قابل تنظیم برای ناظر (بدون «داشبورد»؛ داشبورد برای همهٔ ناظران فعال است).
     *
     * @return array<string, string>
     */
    public static function supervisorPortalAssignableDefinitions(): array
    {
        $defs = self::supervisorCheckboxDefinitions();
        unset($defs[self::DASHBOARD]);

        return $defs;
    }

    /**
     * همهٔ کلیدهای معتبر برای اعتبارسنجی و middleware (شامل کلیدهای فقط‌منویی).
     *
     * @return list<string>
     */
    public static function allKeys(): array
    {
        return array_values(array_unique(array_merge(
            array_keys(self::labels()),
            array_keys(self::permissionLabelsFromNavigation())
        )));
    }

    /**
     * پیش‌فرض برای ناظری که تازه حساب پنل می‌گیرد.
     *
     * @return list<string>
     */
    public static function defaultSupervisorPermissions(): array
    {
        return [self::DASHBOARD, self::SURVEYS];
    }

    /**
     * پیش‌فرض تیک‌های مودال ناظر (بدون داشبورد که همیشه فعال است).
     *
     * @return list<string>
     */
    public static function defaultSupervisorPortalAssignablePermissions(): array
    {
        return array_values(array_filter(
            self::defaultSupervisorPermissions(),
            static fn (string $p) => $p !== self::DASHBOARD
        ));
    }

    /**
     * اولین صفحهٔ مجاز برای هدایت بعد از ورود، فرم لاگین وقتی قبلاً لاگین است، و ریدایرکت «بدون دسترسی».
     * برای مدیر: داشبورد. برای ناظر: داشبورد (خانهٔ پنل با کارت‌های متناسب با مجوزها).
     * اگر ناظر هیچ مجوزی نداشته باشد null برمی‌گردد.
     */
    public static function defaultLandingRouteName(\App\Models\AdminUser $admin): ?string
    {
        if ($admin->isAdmin()) {
            return 'admin.dashboard';
        }

        $perms = array_values(array_filter($admin->permissions ?? [], static fn ($p) => is_string($p) && $p !== ''));
        if ($perms === []) {
            return null;
        }

        return 'admin.dashboard';
    }

    /**
     * @return list<array{label: string, permission: string|null, href: string|null, route: string|null, icon: string, children?: list<array<string, mixed>>}>
     */
    public static function navigationTemplate(): array
    {
        return [
            [
                'label' => 'داشبورد',
                'permission' => null,
                'href' => route('admin.dashboard'),
                'icon' => 'M3 9.75l9-7.5 9 7.5V20a1 1 0 01-1 1h-5.5a1 1 0 01-1-1v-5h-4v5a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z',
                'route' => 'admin.dashboard',
            ],
            [
                'label' => 'تنظیمات سازمان',
                'permission' => null,
                'href' => null,
                'icon' => 'M4 7h16v10H4zM4 10h16',
                'route' => null,
                'children' => [
                    [
                        'label' => 'واحدهای سازمانی',
                        'permission' => self::ORG_UNITS,
                        'href' => route('admin.units.index'),
                        'icon' => 'M3 10h18v9H3zM7 10V5h10v5',
                        'route' => 'admin.units.index',
                    ],
                    [
                        'label' => 'چارت سمت‌ها',
                        'permission' => self::ORG_POSITIONS,
                        'href' => route('admin.positions.index'),
                        'icon' => 'M6 7h4v4H6zM14 7h4v4h-4zM10 13h4v4h-4zM8 9h8M12 13v-2',
                        'route' => 'admin.positions.index',
                    ],
                    [
                        'label' => 'پرسنل سازمان',
                        'permission' => self::ORG_PERSONNEL,
                        'href' => route('admin.personnel.index'),
                        'icon' => 'M8.5 7a3.5 3.5 0 117 0 3.5 3.5 0 01-7 0zM4 19.5c0-2.485 3.358-4.5 7.5-4.5s7.5 2.015 7.5 4.5V21H4z',
                        'route' => 'admin.personnel.index',
                    ],
                    [
                        'label' => 'سرپرستان واحدها',
                        'permission' => self::ORG_SUPERVISORS,
                        'href' => route('admin.unit-supervisors.index'),
                        'icon' => 'M12 6l2 3 3 .5-2.2 2.4.5 3.1L12 13l-3.3 1.9.5-3.1L7 9.5l3-.5zM5 20v-2c0-1.657 3.134-3 7-3s7 1.343 7 3v2',
                        'route' => 'admin.unit-supervisors.index',
                    ],
                ],
            ],
            [
                'label' => 'تنظیمات',
                'permission' => self::SETTINGS,
                'href' => route('admin.settings.index'),
                'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l.149.457a1 1 0 00.95.69h.48c.969 0 1.371 1.24.588 1.81l-.39.284a1 1 0 000 1.62l.39.284c.783.57.38 1.81-.588 1.81h-.48a1 1 0 00-.95.69l-.149.457c-.3.921-1.603.921-1.902 0l-.149-.457a1 1 0 00-.95-.69h-.48c-.969 0-1.371-1.24-.588-1.81l.39-.284a1 1 0 000-1.62l-.39-.284c-.783-.57-.38-1.81.588-1.81h.48a1 1 0 00.95-.69l.149-.457zM12 15.5a3 3 0 100 6 3 3 0 000-6z',
                'route' => 'admin.settings.index',
            ],
            [
                'label' => 'نظرسنجی‌ها',
                'permission' => self::SURVEYS,
                'href' => route('admin.surveys.index'),
                'icon' => 'M5 6h14v4H5zM5 12h14v4H5zM5 18h9',
                'route' => 'admin.surveys.index',
            ],
            [
                'label' => 'گزارشات',
                'href' => '#',
                'permission' => null,
                'icon' => 'M5 9h3v8H5zM10.5 5h3v12h-3zM16 11h3v6h-3z',
                'route' => null,
            ],
            [
                'label' => 'پروفایل کاربر',
                'href' => '#',
                'permission' => null,
                'icon' => 'M12 12a4 4 0 100-8 4 4 0 000 8zm-6 7c0-2.761 3.134-5 6-5s6 2.239 6 5v1H6z',
                'route' => null,
            ],
        ];
    }

    /**
     * @param  \App\Models\AdminUser|null  $admin
     * @return list<array<string, mixed>>
     */
    public static function navigationFor(?\App\Models\AdminUser $admin): array
    {
        if (!$admin) {
            return [];
        }

        $items = self::navigationTemplate();
        $out = [];

        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            if ($children !== []) {
                $filteredChildren = [];
                foreach ($children as $child) {
                    $p = $child['permission'] ?? null;
                    if ($p && !$admin->hasPermission($p)) {
                        continue;
                    }
                    $filteredChildren[] = $child;
                }
                if ($filteredChildren === []) {
                    continue;
                }
                $item['children'] = $filteredChildren;
                $out[] = $item;
                continue;
            }

            $p = $item['permission'] ?? null;
            if ($p && !$admin->hasPermission($p)) {
                continue;
            }
            $out[] = $item;
        }

        return $out;
    }
}
