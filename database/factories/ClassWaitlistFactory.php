<?php

namespace Database\Factories;

use App\Models\ClassWaitlist;
use App\Models\GymClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassWaitlistFactory extends Factory
{
    protected $model = ClassWaitlist::class;

    public function definition(): array
    {
        return [
            'class_id' => GymClass::factory(),
            'user_id' => User::factory(),
            'position' => $this->faker->numberBetween(1, 10),
            'status' => 'waiting',
            'notified_at' => null,
            'expires_at' => null,
        ];
    }

    public function waiting(): static
    {
        return $this->state(['status' => 'waiting']);
    }

    public function notified(): static
    {
        return $this->state([
            'status' => 'notified',
            'notified_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => 'expired',
            'notified_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(['position' => $position]);
    }
}
