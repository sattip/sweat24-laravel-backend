<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkSessionFactory extends Factory
{
    protected $model = WorkSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'hours_worked' => 8.0,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'clock_in' => now(),
            'clock_out' => null,
            'hours_worked' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'clock_out' => now(),
            'hours_worked' => 8.0,
        ]);
    }

    public function today(): static
    {
        return $this->state([
            'clock_in' => now()->startOfDay()->addHours(9),
        ]);
    }
}
