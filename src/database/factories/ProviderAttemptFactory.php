<?php

namespace Database\Factories;

use App\Domain\Notification\Enums\ProviderAttemptResult;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'attempt_no' => 1,
            'provider' => 'mock_email',
            'result' => ProviderAttemptResult::Success,
            'provider_message_id' => fake()->uuid(),
            'duration_ms' => 10,
            'created_at' => now(),
        ];
    }
}
