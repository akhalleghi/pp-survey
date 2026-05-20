<?php

namespace App\Services\Sms;

use App\Models\SmsProvider;
use App\Models\SmsProviderConfig;
use App\Services\Sms\Contracts\SmsDriverInterface;
use App\Services\Sms\Drivers\Panel3300Driver;
use InvalidArgumentException;

final class SmsDriverFactory
{
    public static function makeFromConfig(SmsProvider $provider, SmsProviderConfig $config): SmsDriverInterface
    {
        $username = SmsCredentialVault::decrypt($config->username_encrypted);
        $password = SmsCredentialVault::decrypt($config->password_encrypted);

        if ($username === null || $password === null) {
            throw new InvalidArgumentException('رمزنگاری اطلاعات پنل پیامک قابل خواندن نیست. APP_KEY را بررسی کنید.');
        }

        return match ($provider->driver) {
            'panel_3300' => new Panel3300Driver(
                username: $username,
                password: $password,
                sendNumber: $config->send_number,
                apiUrl: $provider->default_api_url ?: (string) config('sms.drivers.panel_3300.api_url'),
                lifeTime: (int) config('sms.drivers.panel_3300.life_time', 60),
            ),
            default => throw new InvalidArgumentException('درایور پنل پیامک پشتیبانی نمی‌شود: '.$provider->driver),
        };
    }
}
