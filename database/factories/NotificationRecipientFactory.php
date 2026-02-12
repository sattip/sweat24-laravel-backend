<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationRecipientFactory extends Factory
{
    protected $model = NotificationRecipient::class;

    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'user_id' => User::factory(),
            'delivery_channels' => ['push', 'in_app'],
            'delivered_at' => null,
            'read_at' => null,
            'delivery_status' => 'pending',
            'failure_reason' => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state([
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function read(): static
    {
        return $this->state([
            'delivery_status' => 'delivered',
            'delivered_at' => now()->subHour(),
            'read_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'delivery_status' => 'failed',
            'failure_reason' => 'Device token invalid',
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'delivery_status' => 'pending',
            'delivered_at' => null,
        ]);
    }
}
