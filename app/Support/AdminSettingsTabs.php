<?php

namespace App\Support;

/**
 * تب‌های مدال تنظیمات سامانه (منبع واحد برای ناوبری و breadcrumb).
 */
final class AdminSettingsTabs
{
    /**
     * @return list<array{id: string, label: string, subtitle: string, icon: string, disabled?: bool, group?: string}>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 'password',
                'label' => 'رمز عبور',
                'subtitle' => 'به‌روزرسانی رمز مدیر',
                'icon' => 'fa-key',
                'group' => 'account',
            ],
            [
                'id' => 'branding',
                'label' => 'هویت سامانه',
                'subtitle' => 'نام، لوگو و فوتر',
                'icon' => 'fa-id-card',
                'group' => 'appearance',
            ],
            [
                'id' => 'appearance',
                'label' => 'ظاهر برنامه',
                'subtitle' => 'فونت و اندازهٔ متن',
                'icon' => 'fa-text-height',
                'group' => 'appearance',
            ],
            [
                'id' => 'colors',
                'label' => 'رنگ و پس‌زمینه',
                'subtitle' => 'رنگ‌بندی و تصویر پنل',
                'icon' => 'fa-palette',
                'group' => 'appearance',
            ],
            [
                'id' => 'login_page',
                'label' => 'صفحه ورود',
                'subtitle' => 'متن، کپچا و بک‌گراند',
                'icon' => 'fa-right-to-bracket',
                'group' => 'appearance',
            ],
            [
                'id' => 'security',
                'label' => 'امنیت ورود',
                'subtitle' => 'قفل، نشست و گزارش',
                'icon' => 'fa-shield-halved',
                'group' => 'security',
            ],
            [
                'id' => 'profile',
                'label' => 'پروفایل مدیر',
                'subtitle' => 'به‌زودی',
                'icon' => 'fa-user',
                'disabled' => true,
                'group' => 'future',
            ],
            [
                'id' => 'notifications',
                'label' => 'اعلان‌ها',
                'subtitle' => 'به‌زودی',
                'icon' => 'fa-bell',
                'disabled' => true,
                'group' => 'future',
            ],
        ];
    }

    public static function labelFor(string $id): string
    {
        foreach (self::all() as $tab) {
            if ($tab['id'] === $id) {
                return $tab['label'];
            }
        }

        return 'تنظیمات';
    }
}
