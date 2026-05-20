<?php

namespace App\Services\Sms\Support;

/**
 * نرمال‌سازی شماره خط و موبایل برای API پنل 3300.ir.
 */
final class Panel3300Format
{
    public static function toLatinDigits(string $value): string
    {
        static $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        return strtr($value, $map);
    }

    /**
     * خط ارسال در پنل 3300 باید با پیش‌شماره 98 ارسال شود (مثلاً 9830001636).
     */
    public static function lineForApi(string $line): string
    {
        $digits = preg_replace('/\D+/', '', self::toLatinDigits(trim($line))) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '98') && strlen($digits) >= 10) {
            return $digits;
        }

        $digits = ltrim($digits, '0');

        return '98'.$digits;
    }

    /**
     * موبایل گیرنده — API هر سه فرمت 09، 9 و 989 را می‌پذیرد؛ خروجی یکدست 989xxxxxxxxx.
     */
    public static function mobileForApi(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', self::toLatinDigits(trim($mobile))) ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '98'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '98'.$digits;
        }

        return $digits;
    }
}
