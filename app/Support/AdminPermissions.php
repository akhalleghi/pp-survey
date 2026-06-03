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

    public const ORG_COMPANIES = 'org.companies';

    public const SETTINGS = 'settings';

    public const SURVEYS = 'surveys';

    /** گزارشات جامع (داشبورد تحلیلی) — مستقل از لیست نظرسنجی‌ها */
    public const REPORTS = 'reports';

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
            self::ORG_COMPANIES => 'شرکت‌ها',
            self::SETTINGS => 'تنظیمات سازمان',
            self::SURVEYS => 'نظرسنجی‌ها',
            self::REPORTS => 'گزارشات',
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
            if (! isset($merged[$key])) {
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
     * کلیدهایی که هنگام ساخت/ویرایش حساب ناظر قابل اختصاص هستند (بدون دسترسی‌های حساس مثل تنظیمات).
     *
     * @return list<string>
     */
    public static function assignableSupervisorPermissionKeys(): array
    {
        return array_values(array_keys(self::supervisorPortalAssignableDefinitions()));
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
                'icon' => 'fa-gauge-high',
                'route' => 'admin.dashboard',
            ],
            [
                'label' => 'تنظیمات سازمان',
                'permission' => null,
                'href' => null,
                'icon' => 'fa-building',
                'route' => null,
                'children' => [
                    [
                        'label' => 'واحدهای سازمانی',
                        'permission' => self::ORG_UNITS,
                        'href' => route('admin.units.index'),
                        'icon' => 'fa-sitemap',
                        'route' => 'admin.units.index',
                    ],
                    [
                        'label' => 'چارت سمت‌ها',
                        'permission' => self::ORG_POSITIONS,
                        'href' => route('admin.positions.index'),
                        'icon' => 'fa-diagram-project',
                        'route' => 'admin.positions.index',
                    ],
                    [
                        'label' => 'پرسنل سازمان',
                        'permission' => self::ORG_PERSONNEL,
                        'href' => route('admin.personnel.index'),
                        'icon' => 'fa-users',
                        'route' => 'admin.personnel.index',
                    ],
                    [
                        'label' => 'سرپرستان واحدها',
                        'permission' => self::ORG_SUPERVISORS,
                        'href' => route('admin.unit-supervisors.index'),
                        'icon' => 'fa-user-shield',
                        'route' => 'admin.unit-supervisors.index',
                    ],
                    [
                        'label' => 'شرکت‌ها',
                        'permission' => self::ORG_COMPANIES,
                        'href' => route('admin.companies.index'),
                        'icon' => 'fa-briefcase',
                        'route' => 'admin.companies.index',
                    ],
                ],
            ],
            [
                'label' => 'گزارش ورود',
                'permission' => self::SETTINGS,
                'href' => route('admin.login-audit.index'),
                'icon' => 'fa-shield-halved',
                'route' => 'admin.login-audit.index',
            ],
            [
                'label' => 'مدیریت پیامک',
                'permission' => null,
                'admin_only' => true,
                'href' => route('admin.sms.index'),
                'icon' => 'fa-comment-sms',
                'route' => 'admin.sms.index',
            ],
            [
                'label' => 'نظرسنجی‌ها',
                'permission' => self::SURVEYS,
                'href' => route('admin.surveys.index'),
                'icon' => 'fa-clipboard-list',
                'route' => 'admin.surveys.index',
            ],
            [
                'label' => 'گزارشات',
                'permission' => self::REPORTS,
                'href' => route('admin.reports.index'),
                'icon' => 'fa-chart-column',
                'route' => 'admin.reports.index',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function navigationFor(?\App\Models\AdminUser $admin): array
    {
        if (! $admin) {
            return [];
        }

        $items = self::navigationTemplate();
        $out = [];

        foreach ($items as $item) {
            if (! empty($item['admin_only']) && ! $admin->isAdmin()) {
                continue;
            }

            $children = $item['children'] ?? [];
            if ($children !== []) {
                $filteredChildren = [];
                foreach ($children as $child) {
                    $p = $child['permission'] ?? null;
                    if ($p && ! $admin->hasPermission($p)) {
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
            if ($p && ! $admin->hasPermission($p)) {
                continue;
            }
            $out[] = $item;
        }

        return $out;
    }
}
