<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PaymentInstallment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentInstallmentFactory extends Factory
{
    protected $model = PaymentInstallment::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        return [
            'customer_id' => $user->id,
            'customer_name' => $user->name,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'installment_number' => 1,
            'total_installments' => 3,
            'amount' => $this->faker->randomFloat(2, 50, 200),
            'due_date' => $this->faker->dateTimeBetween('now', '+3 months'),
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
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'transfer']),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-3 months', '-1 day'),
        ]);
    }
}
