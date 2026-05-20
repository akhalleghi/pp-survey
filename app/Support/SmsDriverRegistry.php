<?php

namespace App\Support;

use App\Services\Sms\Contracts\SmsDriverInterface;
use App\Services\Sms\Drivers\Panel3300Driver;
use InvalidArgumentException;

final class SmsDriverRegistry
{
    /**
     * @return class-string<SmsDriverInterface>
     */
    public static function resolveClass(string $driver): string
    {
        return match ($driver) {
            'panel_3300' => Panel3300Driver::class,
            default => throw new InvalidArgumentException("درایور پیامک «{$driver}» پشتیبانی نمی‌شود."),
        };
    }

    public static function make(string $driver): SmsDriverInterface
    {
        $class = self::resolveClass($driver);
        $instance = app($class);

        if (! $instance instanceof SmsDriverInterface) {
            throw new InvalidArgumentException("کلاس درایور پیامک نامعتبر است: {$class}");
        }

        return $instance;
    }
}
