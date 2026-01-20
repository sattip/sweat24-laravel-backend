<?php

namespace Database\Factories;

use App\Models\CashRegisterSession;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashRegisterSessionFactory extends Factory
{
    protected $model = CashRegisterSession::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'opened_by' => User::factory(),
            'closed_by' => null,
            'opening_amount' => $this->faker->randomFloat(2, 100, 500),
            'expected_closing_amount' => null,
            'actual_closing_amount' => null,
            'discrepancy' => null,
            'opening_notes' => null,
            'closing_notes' => null,
            'status' => 'open',
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state([
            'status' => 'open',
            'closed_at' => null,
            'closed_by' => null,
        ]);
    }

    public function closed(): static
    {
        $openingAmount = $this->faker->randomFloat(2, 100, 500);
        $closingAmount = $openingAmount + $this->faker->randomFloat(2, 50, 300);

        return $this->state([
            'status' => 'closed',
            'opening_amount' => $openingAmount,
            'expected_closing_amount' => $closingAmount,
            'actual_closing_amount' => $closingAmount,
            'discrepancy' => 0,
            'closed_by' => User::factory(),
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);
    }

    public function withDiscrepancy(): static
    {
        $openingAmount = $this->faker->randomFloat(2, 100, 500);
        $expectedClosing = $openingAmount + $this->faker->randomFloat(2, 50, 300);
        $actualClosing = $expectedClosing - $this->faker->randomFloat(2, 5, 20);

        return $this->state([
            'status' => 'closed',
            'opening_amount' => $openingAmount,
            'expected_closing_amount' => $expectedClosing,
            'actual_closing_amount' => $actualClosing,
            'discrepancy' => $actualClosing - $expectedClosing,
            'closed_by' => User::factory(),
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);
    }
}
