<?php

use Carbon\Carbon;
use Carbon\CarbonInterface;

if (!function_exists('jalali_date')) {
    /**
     * Format a date using the Jalali calendar while preserving time tokens.
     */
    function jalali_date(CarbonInterface|string|null $date, string $format = 'Y/m/d'): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            $carbon = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        } catch (\Throwable) {
            return '';
        }

        [$jy, $jm, $jd] = gregorian_to_jalali(
            (int) $carbon->format('Y'),
            (int) $carbon->format('n'),
            (int) $carbon->format('j')
        );

        $map = [
            'Y' => str_pad((string) $jy, 4, '0', STR_PAD_LEFT),
            'y' => substr(str_pad((string) $jy, 4, '0', STR_PAD_LEFT), -2),
            'm' => str_pad((string) $jm, 2, '0', STR_PAD_LEFT),
            'n' => (string) $jm,
            'd' => str_pad((string) $jd, 2, '0', STR_PAD_LEFT),
            'j' => (string) $jd,
        ];

        $result = '';
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $result .= $format[++$i];
                continue;
            }

            if (array_key_exists($char, $map)) {
                $result .= $map[$char];
                continue;
            }

            $result .= $carbon->format($char);
        }

        return $result;
    }
}

if (!function_exists('gregorian_to_jalali')) {
    /**
     * Convert a Gregorian date to Jalali.
     *
     * @return array<int, int>
     */
    function gregorian_to_jalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $jDaysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }

        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = (365 * $gy)
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            - 80
            + $gd;

        for ($i = 0; $i < $gm - 1; $i++) {
            $days += $gDaysInMonth[$i];
        }

        if ($gm > 2 && (($gy2 % 4 === 0 && $gy2 % 100 !== 0) || ($gy2 % 400 === 0))) {
            $days++;
        }

        $jy += 33 * intdiv($days, 12053);
        $days %= 12053;

        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        for ($i = 0; $i < 11 && $days >= $jDaysInMonth[$i]; $i++) {
            $days -= $jDaysInMonth[$i];
        }

        $jm = $i + 1;
        $jd = $days + 1;

        return [$jy, $jm, $jd];
    }
}

if (!function_exists('jalali_to_gregorian')) {
    /**
     * Convert a Jalali date to Gregorian.
     *
     * @return array<int, int>
     */
    function jalali_to_gregorian(int $jy, int $jm, int $jd): array
    {
        $gDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $jDaysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        $jy -= 979;
        $gy = 1600;
        $days = (365 * $jy)
            + intdiv($jy, 33) * 8
            + intdiv(($jy % 33) + 3, 4)
            + $jd
            - 1;

        for ($i = 0; $i < $jm - 1; $i++) {
            $days += $jDaysInMonth[$i];
        }

        $gy += 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gm = 0;
        while ($gm < 12) {
            $leapAdd = ($gm === 1 && (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0))) ? 1 : 0;
            $monthLength = $gDaysInMonth[$gm] + $leapAdd;
            if ($days < $monthLength) {
                break;
            }
            $days -= $monthLength;
            $gm++;
        }

        $gd = $days + 1;

        return [$gy, $gm + 1, $gd];
    }
}
