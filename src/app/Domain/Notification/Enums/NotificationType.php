<?php

namespace App\Domain\Notification\Enums;

enum NotificationType: string
{
    case Transactional = 'transactional';
    case Marketing = 'marketing';

    public function priority(): int
    {
        return match ($this) {
            self::Transactional => 100,
            self::Marketing => 10,
        };
    }

    public function routingKey(): string
    {
        return match ($this) {
            self::Transactional => config('notifications.routing.high', 'notification.high'),
            self::Marketing => config('notifications.routing.normal', 'notification.normal'),
        };
    }
}
