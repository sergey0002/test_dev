<?php

namespace App\Domain\Notification\Providers;

use App\Domain\Notification\Enums\ProviderAttemptResult;

readonly class ProviderResult
{
    public function __construct(
        public ProviderAttemptResult $result,
        public ?string $providerMessageId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public int $durationMs = 0,
    ) {
    }
}
