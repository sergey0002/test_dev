<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Enums\NotificationType;

class RoutingKeyResolver
{
    public function exchange(): string
    {
        return config('notifications.exchange', 'notifications.direct');
    }

    public function routingKey(NotificationType $type): string
    {
        return $type->routingKey();
    }

    public function priority(NotificationType $type): int
    {
        return $type->priority();
    }
}
