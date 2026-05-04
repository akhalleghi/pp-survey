<?php

namespace App\Support;

use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

final class PersianCalendar
{
    public static function normalizeDigits(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace(array_merge($persian, $arabic), array_merge($latin, $latin), $value);
    }

    public static function parseDateStart(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = self::normalizeDigits(trim($value));
        $padded = self::padJalaliDateString($normalized);
        if ($padded === null) {
            return null;
        }

        try {
            $tz = config('app.timezone');
            $zone = $tz ? new \DateTimeZone($tz) : null;
            $j = Jalalian::fromFormat('Y/m/d', $padded, $zone);

            return $j->toCarbon()->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function parseDateEnd(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = self::normalizeDigits(trim($value));
        $padded = self::padJalaliDateString($normalized);
        if ($padded === null) {
            return null;
        }

        try {
            $tz = config('app.timezone');
            $zone = $tz ? new \DateTimeZone($tz) : null;
            $j = Jalalian::fromFormat('Y/m/d', $padded, $zone);

            return $j->toCarbon()->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function formatDateTime(Carbon $carbon): string
    {
        return Jalalian::fromCarbon($carbon->copy()->timezone(config('app.timezone')))->format('Y/m/d H:i:s');
    }

    private static function padJalaliDateString(string $normalized): ?string
    {
        if (! preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $normalized)) {
            return null;
        }

        $parts = explode('/', $normalized);
        $y = (int) $parts[0];
        $m = (int) $parts[1];
        $d = (int) $parts[2];

        return sprintf('%04d/%02d/%02d', $y, $m, $d);
    }
}
