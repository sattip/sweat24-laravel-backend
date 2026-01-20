<?php

namespace Database\Factories;

use App\Models\OwnerNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OwnerNotificationFactory extends Factory
{
    protected $model = OwnerNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'general',
            'title' => $this->faker->sentence(4),
            'message' => $this->faker->paragraph(),
            'trainer_name' => $this->faker->name(),
            'customer_name' => $this->faker->name(),
            'booking_id' => null,
            'package_id' => null,
            'related_model_type' => null,
            'related_model_id' => null,
            'metadata' => null,
            'is_read' => false,
            'priority' => 'medium',
        ];
    }

    public function read(): static
    {
        return $this->state(['is_read' => true]);
    }

    public function unread(): static
    {
        return $this->state(['is_read' => false]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => 'high']);
    }

    public function lowPriority(): static
    {
        return $this->state(['priority' => 'low']);
    }

    public function gracefulCancellation(): static
    {
        return $this->state(['type' => 'graceful_cancellation']);
    }

    public function packageExtension(): static
    {
        return $this->state(['type' => 'package_extension']);
    }

    public function specialPrice(): static
    {
        return $this->state(['type' => 'special_price']);
    }

    public function bookingRequest(): static
    {
        return $this->state(['type' => 'booking_request']);
    }

    public function contactMessage(): static
    {
        return $this->state(['type' => 'contact_message']);
    }

    public function general(): static
    {
        return $this->state(['type' => 'general']);
    }
}
