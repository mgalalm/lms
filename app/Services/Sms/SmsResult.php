<?php

namespace App\Services\Sms;

/**
 * The outcome of one SmsProvider::send() call, shaped so callers never
 * need to know which provider produced it.
 */
final readonly class SmsResult
{
    private function __construct(
        public bool $successful,
        public ?string $providerMessageId,
        public string $status,
        public ?string $errorDetail,
    ) {}

    public static function success(string $providerMessageId, string $status = 'queued'): self
    {
        return new self(successful: true, providerMessageId: $providerMessageId, status: $status, errorDetail: null);
    }

    public static function failure(string $errorDetail, string $status = 'failed'): self
    {
        return new self(successful: false, providerMessageId: null, status: $status, errorDetail: $errorDetail);
    }
}
