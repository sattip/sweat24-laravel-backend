<?php

namespace Database\Factories;

use App\Models\PointsTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointsTransactionFactory extends Factory
{
    protected $model = PointsTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->numberBetween(10, 500),
            'type' => 'earned',
            'source' => $this->faker->randomElement(['purchase', 'referral', 'bonus', 'booking']),
            'description' => $this->faker->sentence(),
            'balance_after' => $this->faker->numberBetween(10, 1000),
        ];
    }

    public function earned(int $amount = null): static
    {
        return $this->state([
            'type' => 'earned',
            'amount' => $amount ?? $this->faker->numberBetween(10, 500),
        ]);
    }

    public function spent(int $amount = null): static
    {
        return $this->state([
            'type' => 'spent',
            'amount' => $amount ?? $this->faker->numberBetween(-500, -10),
        ]);
    }

    public function fromSource(string $source): static
    {
        return $this->state([
            'source' => $source,
        ]);
    }

    public function withMetadata(array $metadata): static
    {
        return $this->state([
            'metadata' => $metadata,
        ]);
    }
}
