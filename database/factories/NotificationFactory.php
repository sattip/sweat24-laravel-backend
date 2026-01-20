<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(),
            'type' => Notification::TYPE_INFO,
            'priority' => Notification::PRIORITY_MEDIUM,
            'channels' => ['push', 'in_app'],
            'filters' => null,
            'created_by' => User::factory(),
            'scheduled_at' => null,
            'sent_at' => null,
            'status' => 'draft',
            'total_recipients' => 0,
            'delivered_count' => 0,
            'read_count' => 0,
        ];
    }

    public function info(): static
    {
        return $this->state(['type' => Notification::TYPE_INFO]);
    }

    public function warning(): static
    {
        return $this->state(['type' => Notification::TYPE_WARNING]);
    }

    public function success(): static
    {
        return $this->state(['type' => Notification::TYPE_SUCCESS]);
    }

    public function error(): static
    {
        return $this->state(['type' => Notification::TYPE_ERROR]);
    }

    public function offer(): static
    {
        return $this->state(['type' => Notification::TYPE_OFFER]);
    }

    public function partyEvent(): static
    {
        return $this->state(['type' => Notification::TYPE_PARTY_EVENT]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => Notification::PRIORITY_HIGH]);
    }

    public function lowPriority(): static
    {
        return $this->state(['priority' => Notification::PRIORITY_LOW]);
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours(2),
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'status' => 'sent',
            'sent_at' => now(),
            'total_recipients' => $this->faker->numberBetween(10, 100),
            'delivered_count' => $this->faker->numberBetween(5, 50),
            'read_count' => $this->faker->numberBetween(1, 20),
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }
}
