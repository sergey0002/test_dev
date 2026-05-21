<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Enums\NotificationStatus;
use App\Domain\Notification\Exceptions\InvalidStatusTransitionException;
use App\Models\Notification;

class StatusTransitionService
{
    public function transition(Notification $notification, NotificationStatus $to, string $reason, array $meta = []): void
    {
        $from = $notification->status;

        if ($from instanceof NotificationStatus && ! $from->canTransitionTo($to)) {
            throw new InvalidStatusTransitionException($from, $to);
        }

        // Перед сменой статуса проставляем профильный timestamp,
        // чтобы в БД сохранялась прозрачная история жизненного цикла.
        $this->applyTimestamp($notification, $to);
        $notification->status = $to;
        $notification->save();

        $notification->events()->create([
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'meta' => $meta,
        ]);
    }

    private function applyTimestamp(Notification $notification, NotificationStatus $to): void
    {
        $now = now();

        match ($to) {
            NotificationStatus::Queued => $notification->queued_at ??= $now,
            NotificationStatus::Sent => $notification->sent_at = $now,
            NotificationStatus::Delivered => $notification->delivered_at = $now,
            NotificationStatus::Dropped => $notification->dropped_at = $now,
        };
    }
}
