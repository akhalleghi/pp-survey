<?php

namespace App\Services\Sms\Contracts;

use App\Services\Sms\SmsSendResult;

interface SmsDriverInterface
{
    /**
     * @param  array{username: string, password: string, send_number: string, api_url?: string|null}  $credentials
     */
    public function send(string $mobile, string $message, array $credentials): SmsSendResult;
}
