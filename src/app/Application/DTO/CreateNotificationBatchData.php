<?php

namespace App\Application\DTO;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;

readonly class CreateNotificationBatchData
{
    /**
     * @param  array<int, int>  $recipientIds
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public NotificationChannel $channel,
        public NotificationType $type,
        public string $message,
        public array $recipientIds,
        public string $idempotencyKey,
        public array $metadata = [],
    ) {
    }

    public function toHashPayload(): array
    {
        return [
            'channel' => $this->channel->value,
            'type' => $this->type->value,
            'message' => $this->message,
            'recipient_ids' => $this->recipientIds,
            'idempotency_key' => $this->idempotencyKey,
            'metadata' => $this->metadata,
        ];
    }
}
