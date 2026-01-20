<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_type' => ActivityLog::TYPE_LOGIN,
            'model_type' => null,
            'model_id' => null,
            'action' => 'User logged in',
            'properties' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function login(): static
    {
        return $this->state([
            'activity_type' => ActivityLog::TYPE_LOGIN,
            'action' => 'User logged in',
        ]);
    }

    public function logout(): static
    {
        return $this->state([
            'activity_type' => ActivityLog::TYPE_LOGOUT,
            'action' => 'User logged out',
        ]);
    }

    public function registration(): static
    {
        return $this->state([
            'activity_type' => ActivityLog::TYPE_REGISTRATION,
            'action' => 'New user registered',
        ]);
    }

    public function booking(): static
    {
        return $this->state([
            'activity_type' => ActivityLog::TYPE_BOOKING,
            'action' => 'Class booked',
        ]);
    }

    public function bookingCancellation(): static
    {
        return $this->state([
            'activity_type' => ActivityLog::TYPE_BOOKING_CANCELLATION,
            'action' => 'Booking cancelled',
        ]);
    }

    public function payment(): static
    {
        return $this->state([
            'activity_type' => ActivityLog::TYPE_PAYMENT,
            'action' => 'Payment processed',
        ]);
    }

    public function packagePurchase(): static
    {
        return $this->state([
            'activity_type' => ActivityLog::TYPE_PACKAGE_PURCHASE,
            'action' => 'Package purchased',
        ]);
    }

    public function withProperties(array $properties): static
    {
        return $this->state(['properties' => $properties]);
    }

    public function forModel(string $modelType, int $modelId): static
    {
        return $this->state([
            'model_type' => $modelType,
            'model_id' => $modelId,
        ]);
    }
}
