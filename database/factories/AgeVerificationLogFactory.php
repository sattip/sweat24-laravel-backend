<?php

namespace Database\Factories;

use App\Models\AgeVerificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgeVerificationLogFactory extends Factory
{
    protected $model = AgeVerificationLog::class;

    public function definition(): array
    {
        $age = $this->faker->numberBetween(16, 65);
        return [
            'birth_date' => now()->subYears($age)->format('Y-m-d'),
            'calculated_age' => $age,
            'is_minor' => $age < 18,
            'server_date' => now()->format('Y-m-d'),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function adult(): static
    {
        $age = $this->faker->numberBetween(18, 65);
        return $this->state([
            'birth_date' => now()->subYears($age)->format('Y-m-d'),
            'calculated_age' => $age,
            'is_minor' => false,
        ]);
    }

    public function minor(): static
    {
        $age = $this->faker->numberBetween(14, 17);
        return $this->state([
            'birth_date' => now()->subYears($age)->format('Y-m-d'),
            'calculated_age' => $age,
            'is_minor' => true,
        ]);
    }
}
