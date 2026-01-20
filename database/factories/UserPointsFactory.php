<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPoints;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPointsFactory extends Factory
{
    protected $model = UserPoints::class;

    public function definition(): array
    {
        $totalEarned = $this->faker->numberBetween(0, 5000);
        $totalSpent = $this->faker->numberBetween(0, min($totalEarned, 3000));

        return [
            'user_id' => User::factory(),
            'points_balance' => $totalEarned - $totalSpent,
            'total_earned' => $totalEarned,
            'total_spent' => $totalSpent,
            'lifetime_rank' => $this->faker->optional()->numberBetween(1, 100),
        ];
    }

    public function withBalance(int $balance): static
    {
        return $this->state([
            'points_balance' => $balance,
            'total_earned' => $balance + $this->faker->numberBetween(0, 1000),
        ]);
    }

    public function noPoints(): static
    {
        return $this->state([
            'points_balance' => 0,
            'total_earned' => 0,
            'total_spent' => 0,
        ]);
    }

    public function highRank(): static
    {
        return $this->state([
            'points_balance' => $this->faker->numberBetween(5000, 10000),
            'total_earned' => $this->faker->numberBetween(10000, 20000),
            'lifetime_rank' => $this->faker->numberBetween(1, 10),
        ]);
    }
}
