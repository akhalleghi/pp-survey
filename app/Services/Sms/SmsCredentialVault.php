<?php

namespace App\Services\Sms;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * رمزنگاری اطلاعات حساس پنل پیامک (با APP_KEY لاراول).
 */
final class SmsCredentialVault
{
    public static function encrypt(string $plain): string
    {
        return Crypt::encryptString($plain);
    }

    public static function decrypt(string $encrypted): string
    {
        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException $e) {
            throw new RuntimeException('رمزگشایی اطلاعات پنل پیامک ممکن نیست. کلید برنامه (APP_KEY) را بررسی کنید.', 0, $e);
        }
    }

    public static function maskSecret(?string $plain, int $visibleTail = 0): string
    {
        if ($plain === null || $plain === '') {
            return '';
        }

        return str_repeat('•', 8).($visibleTail > 0 ? substr($plain, -$visibleTail) : '');
    }
}
