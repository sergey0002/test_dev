<?php

namespace App\Domain\Notification\Enums;

enum ProviderAttemptResult: string
{
    case Success = 'success';
    case TemporaryError = 'temporary_error';
    case PermanentError = 'permanent_error';

    public function shouldRetry(): bool
    {
        return $this === self::TemporaryError;
    }

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }
}
