<?php

namespace App\Services\Sms;

final class SmsSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?int $providerStatus = null,
        public readonly ?int $httpCode = null,
    ) {}
}
