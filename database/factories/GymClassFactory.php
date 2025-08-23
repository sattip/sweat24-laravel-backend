<?php

namespace Database\Factories;

use App\Models\GymClass;
use App\Models\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

class GymClassFactory extends Factory
{
    protected $model = GymClass::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Morning Yoga', 'Evening Pilates', 'HIIT Training', 'CrossFit', 'Zumba']),
            'type' => fake()->randomElement(['Yoga', 'Pilates', 'HIIT', 'CrossFit', 'Zumba', 'Boxing']),
            'instructor' => function () {
                return Instructor::factory()->create()->id;
            },
            'date' => fake()->dateTimeBetween('now', '+30 days'),
            'time' => fake()->time('H:i:s'),
            'duration' => fake()->randomElement([30, 45, 60, 90]),
            'max_participants' => fake()->numberBetween(10, 30),
            'current_participants' => fake()->numberBetween(0, 10),
            'location' => fake()->randomElement(['Studio A', 'Studio B', 'Main Hall', 'Outdoor Area']),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}