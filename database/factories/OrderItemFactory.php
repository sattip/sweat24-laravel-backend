<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StoreProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 1, 20);
        $quantity = $this->faker->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'product_id' => StoreProduct::factory(),
            'product_name' => $this->faker->words(3, true),
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
            'is_preorder' => false,
        ];
    }

    public function preorder(): static
    {
        return $this->state(['is_preorder' => true]);
    }

    public function withQuantity(int $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            return [
                'quantity' => $quantity,
                'subtotal' => $attributes['price'] * $quantity,
            ];
        });
    }
}
