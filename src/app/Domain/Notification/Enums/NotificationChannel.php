<?php

namespace App\Domain\Notification\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS',
        };
    }

    public function providerName(): string
    {
        return match ($this) {
            self::Email => 'mock_email',
            self::Sms => 'mock_sms',
        };
    }
}
