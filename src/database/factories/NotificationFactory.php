<?php

namespace Database\Factories;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationStatus;
use App\Domain\Notification\Enums\NotificationType;
use App\Models\NotificationBatch;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        $type = NotificationType::Transactional;

        return [
            'batch_id' => NotificationBatch::factory(),
            'subscriber_id' => Subscriber::factory(),
            'channel' => NotificationChannel::Email,
            'type' => $type,
            'message_snapshot' => 'Тестовое сообщение',
            'status' => NotificationStatus::Queued,
            'priority' => $type->priority(),
            'attempts_count' => 0,
            'max_attempts' => 3,
            'queued_at' => now(),
        ];
    }
}
