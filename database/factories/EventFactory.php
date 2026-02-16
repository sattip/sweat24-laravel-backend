<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'time' => $this->faker->time('H:i:s'),
            'location' => $this->faker->address(),
            'image_url' => null,
            'type' => $this->faker->randomElement(['social', 'educational', 'fitness', 'other']),
            'details' => null,
            'is_active' => true,
            'max_attendees' => $this->faker->numberBetween(20, 100),
            'current_attendees' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function social(): static
    {
        return $this->state(['type' => 'social']);
    }

    public function educational(): static
    {
        return $this->state(['type' => 'educational']);
    }

    public function fitness(): static
    {
        return $this->state(['type' => 'fitness']);
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

    public function full(): static
    {
        return $this->state(function (array $attributes) {
            $max = $attributes['max_attendees'] ?? 50;
            return ['current_attendees' => $max];
        });
    }
}
