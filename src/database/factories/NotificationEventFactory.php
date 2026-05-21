<?php

namespace Database\Factories;

use App\Domain\Notification\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'from_status' => null,
            'to_status' => NotificationStatus::Queued,
            'reason' => 'created',
            'meta' => [],
            'created_at' => now(),
        ];
    }
}
