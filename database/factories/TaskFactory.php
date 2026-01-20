<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'deadline' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'created_by' => User::factory(),
            'assigned_to' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'creation_date' => now()->format('Y-m-d'),
            'completion_date' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'completion_date' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => 'in_progress',
            'completion_date' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'completion_date' => now()->format('Y-m-d'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
            'completion_date' => null,
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(['priority' => 'low']);
    }

    public function mediumPriority(): static
    {
        return $this->state(['priority' => 'medium']);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => 'high']);
    }

    public function overdue(): static
    {
        return $this->state([
            'deadline' => now()->subDays(3),
            'status' => 'pending',
        ]);
    }

    public function dueToday(): static
    {
        return $this->state([
            'deadline' => now(),
            'status' => 'pending',
        ]);
    }
}
