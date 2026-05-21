<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+7999'.fake()->unique()->numerify('#######'),
            'name' => fake()->name(),
            'is_active' => true,
        ];
    }
}
