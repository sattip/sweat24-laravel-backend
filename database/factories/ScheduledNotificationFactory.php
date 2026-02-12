<?php

namespace Database\Factories;

use App\Models\ScheduledNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ScheduledNotificationFactory extends Factory
{
    protected $model = ScheduledNotification::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['package_expiry_week', 'package_expiry_2days', 'appointment_reminder']),
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'scheduled_for' => now()->addHours($this->faker->numberBetween(1, 48)),
            'related_id' => null,
            'data' => null,
            'is_sent' => false,
            'sent_at' => null,
        ];
    }

    public function packageExpiryWeek(): static
    {
        return $this->state(['type' => 'package_expiry_week']);
    }

    public function packageExpiry2Days(): static
    {
        return $this->state(['type' => 'package_expiry_2days']);
    }

    public function appointmentReminder(): static
    {
        return $this->state(['type' => 'appointment_reminder']);
    }

    public function pending(): static
    {
        return $this->state([
            'is_sent' => false,
            'sent_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    public function forRelated(int $relatedId): static
    {
        return $this->state(['related_id' => $relatedId]);
    }

    public function withData(array $data): static
    {
        return $this->state(['data' => $data]);
    }
}
