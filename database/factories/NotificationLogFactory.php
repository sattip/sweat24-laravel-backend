<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'notification_id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'push_token' => $this->faker->sha256(),
            'platform' => $this->faker->randomElement(['ios', 'android', 'web']),
            'status' => $this->faker->randomElement(['success', 'failed', 'invalid_token']),
            'response_data' => null,
            'error_message' => null,
        ];
    }

    public function success(): static
    {
        return $this->state([
            'status' => 'success',
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
        ]);
    }

    public function invalidToken(): static
    {
        return $this->state([
            'status' => 'invalid_token',
            'error_message' => 'Invalid push token',
        ]);
    }

    public function ios(): static
    {
        return $this->state(['platform' => 'ios']);
    }

    public function android(): static
    {
        return $this->state(['platform' => 'android']);
    }

    public function web(): static
    {
        return $this->state(['platform' => 'web']);
    }
}
