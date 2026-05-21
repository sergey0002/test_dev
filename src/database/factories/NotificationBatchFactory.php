<?php

namespace Database\Factories;

use App\Domain\Notification\Enums\NotificationBatchStatus;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'idempotency_key' => fake()->uuid(),
            'payload_hash' => hash('sha256', fake()->uuid()),
            'channel' => NotificationChannel::Email,
            'type' => NotificationType::Transactional,
            'message' => 'Тестовое сообщение',
            'requested_count' => 1,
            'accepted_count' => 1,
            'status' => NotificationBatchStatus::Accepted,
            'metadata' => ['source_service' => 'tests'],
        ];
    }
}
