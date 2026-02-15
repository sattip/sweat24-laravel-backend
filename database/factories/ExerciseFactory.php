<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExerciseFactory extends Factory
{
    protected $model = Exercise::class;

    public function definition(): array
    {
        return [
            'name_en' => $this->faker->unique()->words(3, true),
            'name_gr' => $this->faker->unique()->words(3, true),
            'muscle_group' => $this->faker->randomElement(['chest', 'back', 'legs', 'shoulders', 'arms', 'core', 'full_body']),
            'category' => $this->faker->randomElement(['strength', 'cardio', 'flexibility', 'balance']),
            'equipment' => $this->faker->randomElements(['barbell', 'dumbbell', 'machine', 'bodyweight', 'cable', 'kettlebell'], 2),
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'description' => $this->faker->paragraph(),
            'video_url' => null,
            'image_url' => null,
            'is_active' => true,
            'created_by' => User::factory(),
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

    public function beginner(): static
    {
        return $this->state(['difficulty_level' => 'beginner']);
    }

    public function intermediate(): static
    {
        return $this->state(['difficulty_level' => 'intermediate']);
    }

    public function advanced(): static
    {
        return $this->state(['difficulty_level' => 'advanced']);
    }

    public function chest(): static
    {
        return $this->state(['muscle_group' => 'chest']);
    }

    public function back(): static
    {
        return $this->state(['muscle_group' => 'back']);
    }

    public function legs(): static
    {
        return $this->state(['muscle_group' => 'legs']);
    }

    public function bodyweight(): static
    {
        return $this->state(['equipment' => ['bodyweight']]);
    }
}
