<?php

namespace Database\Factories;

use App\Models\PointsReward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardRedemptionFactory extends Factory
{
    protected $model = RewardRedemption::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reward_id' => PointsReward::factory(),
            'points_spent' => $this->faker->numberBetween(100, 500),
            'reward_code' => strtoupper($this->faker->unique()->bothify('RWD##????')),
            'status' => 'active',
            'instructions' => null,
            'expires_at' => now()->addMonths(3),
            'used_at' => null,
            'used_by_staff_id' => null,
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function used(): static
    {
        return $this->state([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
