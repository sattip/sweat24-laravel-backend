<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\WorkoutMuscleGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkoutMuscleGroupFactory extends Factory
{
    protected $model = WorkoutMuscleGroup::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'user_id' => User::factory(),
            'muscle_groups' => $this->faker->randomElements(['chest', 'back', 'legs', 'shoulders', 'arms', 'core'], 3),
        ];
    }

    public function upperBody(): static
    {
        return $this->state([
            'muscle_groups' => ['chest', 'back', 'shoulders', 'arms'],
        ]);
    }

    public function lowerBody(): static
    {
        return $this->state([
            'muscle_groups' => ['legs', 'glutes'],
        ]);
    }

    public function fullBody(): static
    {
        return $this->state([
            'muscle_groups' => ['chest', 'back', 'legs', 'shoulders', 'arms', 'core'],
        ]);
    }
}
