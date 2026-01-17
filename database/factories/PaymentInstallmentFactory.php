<?php

namespace Database\Factories;

use App\Models\PaymentInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentInstallmentFactory extends Factory
{
    protected $model = PaymentInstallment::class;

    public function definition(): array
    {
        return [
            'customer_id' => fake()->randomNumber(5),
            'customer_name' => fake()->name(),
            'package_id' => fake()->randomNumber(3),
            'package_name' => fake()->randomElement(['Basic', 'Premium', 'Gold']) . ' Package',
            'installment_number' => 1,
            'total_installments' => 3,
            'amount' => fake()->randomFloat(2, 50, 200),
            'due_date' => fake()->dateTimeBetween('now', '+3 months'),
            'paid_date' => null,
            'payment_method' => null,
            'status' => 'pending',
            'notes' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => fake()->randomElement(['cash', 'card']),
        ]);
    }
}
