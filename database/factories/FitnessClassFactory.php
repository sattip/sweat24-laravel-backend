<?php

namespace Database\Factories;

use App\Models\FitnessClass;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class FitnessClassFactory extends Factory
{
    protected $model = FitnessClass::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Morning Yoga', 'Evening Pilates', 'HIIT Training', 'CrossFit', 'Zumba']),
            'type' => $this->faker->randomElement(['yoga', 'pilates', 'hiit', 'crossfit', 'zumba', 'boxing']),
            'instructor' => $this->faker->name(),
            'date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'time' => $this->faker->time('H:i:s'),
            'duration' => $this->faker->randomElement([30, 45, 60, 90]),
            'max_participants' => $this->faker->numberBetween(10, 30),
            'current_participants' => 0,
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'active',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
        ]);
    }

    public function full(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'current_participants' => $attributes['max_participants'] ?? 20,
            ];
        });
    }

    public function withStore(): static
    {
        return $this->state([
            'store_id' => Store::factory(),
        ]);
    }

    public function recurring(): static
    {
        return $this->state([
            'is_recurring' => true,
            'recurrence_pattern' => 'weekly',
            'recurrence_interval' => 1,
            'recurrence_end_date' => now()->addMonths(3)->format('Y-m-d'),
        ]);
    }

    public function past(): static
    {
        return $this->state([
            'date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state([
            'date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
        ]);
    }
}
