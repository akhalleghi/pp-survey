<?php

namespace App\Services\Sms\Http;

/**
 * کلاینت HTTP ساده برای وب‌سرویس پنل‌های پیامک (مبتنی بر cURL).
 */
final class SmsHttpClient
{
    /**
     * @return array{responseCode: int, body: array<string, mixed>|null, rawBody: string}
     */
    public function get(string $url, string $queryString = ''): array
    {
        if ($queryString !== '') {
            $url = sprintf('%s?%s', $url, $queryString);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($curl);
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            return [
                'responseCode' => 0,
                'body' => null,
                'rawBody' => $error,
            ];
        }

        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $rawBody = substr($response, $headerSize);
        $decoded = json_decode($rawBody, true);

        return [
            'responseCode' => $httpCode,
            'body' => is_array($decoded) ? $decoded : null,
            'rawBody' => $rawBody,
        ];
    }
}
