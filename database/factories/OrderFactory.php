<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 5, 100);
        $tax = $subtotal * 0.24; // 24% VAT

        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'is_preorder' => false,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'customer_phone' => $this->faker->phoneNumber(),
            'notes' => null,
            'points_applied' => false,
            'points_awarded' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing']);
    }

    public function readyForPickup(): static
    {
        return $this->state([
            'status' => 'ready_for_pickup',
            'ready_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'ready_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function preorder(): static
    {
        return $this->state(['is_preorder' => true]);
    }

    public function withPoints(): static
    {
        return $this->state([
            'points_applied' => true,
            'points_awarded' => $this->faker->randomFloat(2, 1, 10),
            'points_applied_at' => now(),
        ]);
    }
}
