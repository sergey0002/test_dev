<?php

namespace App\Domain\Notification\Enums;

enum NotificationStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Dropped = 'dropped';

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Dropped], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Queued => in_array($next, [self::Queued, self::Sent, self::Dropped], true),
            self::Sent => in_array($next, [self::Delivered, self::Dropped], true),
            self::Delivered, self::Dropped => false,
        };
    }
}
