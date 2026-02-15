<?php

namespace Database\Factories;

use App\Models\BusinessExpense;
use App\Models\ExpenseCategory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessExpenseFactory extends Factory
{
    protected $model = BusinessExpense::class;

    public function definition(): array
    {
        return [
            'category' => $this->faker->randomElement(['utilities', 'supplies', 'maintenance', 'marketing']),
            'subcategory' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'vendor' => $this->faker->company(),
            'receipt' => null,
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'bank_transfer']),
            'approved' => false,
            'approved_by' => null,
            'notes' => null,
            'store_id' => Store::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'approved' => true,
            'approved_by' => User::factory(),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'approved' => false,
            'approved_by' => null,
        ]);
    }

    public function withReceipt(): static
    {
        return $this->state([
            'receipt' => 'receipts/' . $this->faker->uuid() . '.pdf',
        ]);
    }

    public function cash(): static
    {
        return $this->state(['payment_method' => 'cash']);
    }

    public function card(): static
    {
        return $this->state(['payment_method' => 'card']);
    }
}
