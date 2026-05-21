<?php

namespace Database\Factories;

use App\Domain\Notification\Enums\OutboxStatus;
use App\Domain\Notification\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutboxMessageFactory extends Factory
{
    public function definition(): array
    {
        $notification = Notification::factory()->create();
        $type = NotificationType::Transactional;

        return [
            'aggregate_type' => 'notification',
            'aggregate_id' => $notification->id,
            'exchange' => config('notifications.exchange', 'notifications.direct'),
            'routing_key' => $type->routingKey(),
            'payload' => [
                'notification_id' => $notification->id,
                'batch_id' => $notification->batch_id,
                'attempt' => 1,
            ],
            'status' => OutboxStatus::Pending,
            'attempts' => 0,
            'available_at' => now(),
        ];
    }
}
