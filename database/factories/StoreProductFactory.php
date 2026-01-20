<?php

namespace Database\Factories;

use App\Models\StoreProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StoreProductFactory extends Factory
{
    protected $model = StoreProduct::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'name' => $name,
            'price' => $this->faker->randomFloat(2, 5, 100),
            'description' => $this->faker->paragraph(),
            'image_url' => null,
            'category' => $this->faker->randomElement(['supplements', 'apparel', 'accessories', 'equipment']),
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->randomNumber(5),
            'is_active' => true,
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'original_price' => null,
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function supplements(): static
    {
        return $this->state(['category' => 'supplements']);
    }

    public function apparel(): static
    {
        return $this->state(['category' => 'apparel']);
    }

    public function accessories(): static
    {
        return $this->state(['category' => 'accessories']);
    }

    public function equipment(): static
    {
        return $this->state(['category' => 'equipment']);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function preorder(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }

    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'original_price' => $attributes['price'] * 1.2,
            ];
        });
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }

    public function inStock(): static
    {
        return $this->state(['stock_quantity' => $this->faker->numberBetween(10, 100)]);
    }
}
