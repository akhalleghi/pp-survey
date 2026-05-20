<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriverInterface;
use App\Services\Sms\Http\SmsHttpClient;
use App\Services\Sms\SmsSendResult;
use App\Services\Sms\Support\Panel3300Format;
use Illuminate\Support\Facades\Log;

/**
 * درایور پنل پیامک 3300.ir (REST — wsSend.ashx).
 *
 * @see http://3300.ir/#webservice
 */
final class Panel3300Driver implements SmsDriverInterface
{
    public function __construct(
        private readonly SmsHttpClient $http = new SmsHttpClient,
    ) {}

    /**
     * @param  array{username: string, password: string, send_number: string, api_url?: string|null}  $credentials
     */
    public function send(string $mobile, string $message, array $credentials): SmsSendResult
    {
        $apiUrl = $credentials['api_url'] ?? config('sms.drivers.panel_3300.api_url');
        $lifeTime = (int) config('sms.drivers.panel_3300.life_time', 60);

        $line = Panel3300Format::lineForApi($credentials['send_number']);
        $mobile = Panel3300Format::mobileForApi($mobile);

        if ($line === '') {
            return new SmsSendResult(false, 'شماره خط ارسال‌کننده نامعتبر است.');
        }

        $params = [
            'username='.$credentials['username'],
            'password='.urlencode($credentials['password']),
            'line='.$line,
            'mobile='.$mobile,
            'message='.urlencode($message),
            'life_time='.$lifeTime,
        ];

        $result = $this->http->get($apiUrl, implode('&', $params));
        $httpCode = $result['responseCode'];
        $body = $result['body'];
        $status = is_array($body) ? ($body['status'] ?? $body['Status'] ?? null) : null;

        if ($this->isSuccessful($httpCode, $status)) {
            return new SmsSendResult(
                success: true,
                message: 'پیامک با موفقیت ارسال شد.',
                providerStatus: is_numeric($status) ? (int) $status : null,
                httpCode: $httpCode,
            );
        }

        $providerMessage = is_array($body)
            ? (string) ($body['msg'] ?? $body['message'] ?? $body['Message'] ?? '')
            : '';

        if ($providerMessage === '' && $result['rawBody'] !== '') {
            $providerMessage = mb_substr(trim($result['rawBody']), 0, 200);
        }

        Log::warning('sms.panel_3300.send_failed', [
            'http_code' => $httpCode,
            'provider_status' => $status,
            'provider_message' => $providerMessage !== '' ? $providerMessage : null,
        ]);

        return new SmsSendResult(
            success: false,
            message: $providerMessage !== ''
                ? 'ارسال ناموفق: '.$providerMessage
                : 'ارسال پیامک ناموفق بود. پاسخ سرویس‌دهنده نامعتبر یا خطای شبکه.',
            providerStatus: is_numeric($status) ? (int) $status : null,
            httpCode: $httpCode,
        );
    }

    /**
     * منطق موفقیت مطابق پیاده‌سازی قبلی پروژه (status منفی = موفق در API این پنل).
     */
    private function isSuccessful(int $httpCode, mixed $status): bool
    {
        if ($httpCode !== 200 || ! is_numeric($status)) {
            return false;
        }

        return (int) $status < 0;
    }
}
