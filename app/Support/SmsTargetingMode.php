<?php

namespace App\Support;

final class SmsTargetingMode
{
    public const SURVEY_ELIGIBLE = 'survey_eligible';

    public const ALL_PERSONNEL = 'all_personnel';

    public const CUSTOM_FILTERS = 'custom_filters';

    public const SELECTED_PERSONNEL = 'selected_personnel';

    public const FREE_NUMBERS = 'free_numbers';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SURVEY_ELIGIBLE => 'فقط افراد مجاز به شرکت در نظرسنجی انتخاب‌شده',
            self::ALL_PERSONNEL => 'ارسال به کل پرسنل دارای شماره موبایل',
            self::CUSTOM_FILTERS => 'فیلتر سفارشی پرسنل (واحد، سمت، جنسیت و …)',
            self::SELECTED_PERSONNEL => 'انتخاب آزاد از فهرست پرسنل',
            self::FREE_NUMBERS => 'ارسال آزاد به شماره‌های دلخواه',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @return array<string, string> mode => Font Awesome class (fa-solid …)
     */
    public static function icons(): array
    {
        return [
            self::SURVEY_ELIGIBLE => 'fa-user-check',
            self::ALL_PERSONNEL => 'fa-users',
            self::CUSTOM_FILTERS => 'fa-filter',
            self::SELECTED_PERSONNEL => 'fa-user-plus',
            self::FREE_NUMBERS => 'fa-phone-volume',
        ];
    }
}
