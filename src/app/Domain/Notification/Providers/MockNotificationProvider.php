<?php

namespace App\Domain\Notification\Providers;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\ProviderAttemptResult;
use App\Models\Notification;

class MockNotificationProvider
{
    public function send(Notification $notification): ProviderResult
    {
        $startedAt = microtime(true);
        $notification->loadMissing('subscriber');
        $contact = (string) $notification->subscriber?->contactFor($notification->channel);

        $result = match ($notification->channel) {
            NotificationChannel::Email => $this->emailResult($notification, $contact),
            NotificationChannel::Sms => $this->smsResult($notification, $contact),
        };

        return new ProviderResult(
            result: $result->result,
            providerMessageId: $result->providerMessageId,
            errorCode: $result->errorCode,
            errorMessage: $result->errorMessage,
            durationMs: max(1, (int) round((microtime(true) - $startedAt) * 1000)),
        );
    }

    private function emailResult(Notification $notification, string $email): ProviderResult
    {
        if (str_ends_with($email, '@invalid.test')) {
            return new ProviderResult(
                result: ProviderAttemptResult::PermanentError,
                errorCode: 'email_invalid',
                errorMessage: 'Mock provider rejected invalid email.',
            );
        }

        if (str_ends_with($email, '@temporary-error.test')) {
            return new ProviderResult(
                result: ProviderAttemptResult::TemporaryError,
                errorCode: 'email_temporary_error',
                errorMessage: 'Mock provider temporary email failure.',
            );
        }

        return $this->success($notification, 'mock-email');
    }

    private function smsResult(Notification $notification, string $phone): ProviderResult
    {
        if (str_ends_with($phone, '999')) {
            return new ProviderResult(
                result: ProviderAttemptResult::PermanentError,
                errorCode: 'sms_invalid',
                errorMessage: 'Mock provider rejected phone.',
            );
        }

        if (str_ends_with($phone, '000')) {
            return new ProviderResult(
                result: ProviderAttemptResult::TemporaryError,
                errorCode: 'sms_temporary_error',
                errorMessage: 'Mock provider temporary sms failure.',
            );
        }

        return $this->success($notification, 'mock-sms');
    }

    private function success(Notification $notification, string $provider): ProviderResult
    {
        return new ProviderResult(
            result: ProviderAttemptResult::Success,
            providerMessageId: $provider.'-'.$notification->id.'-'.bin2hex(random_bytes(4)),
        );
    }
}
