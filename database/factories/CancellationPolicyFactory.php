<?php

namespace Database\Factories;

use App\Models\CancellationPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class CancellationPolicyFactory extends Factory
{
    protected $model = CancellationPolicy::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Policy',
            'description' => $this->faker->sentence(),
            'hours_before' => $this->faker->randomElement([12, 24, 48]),
            'penalty_percentage' => $this->faker->randomElement([0, 25, 50, 100]),
            'allow_reschedule' => true,
            'reschedule_hours_before' => 24,
            'max_reschedules_per_month' => 2,
            'is_active' => true,
            'priority' => $this->faker->numberBetween(1, 10),
            'applicable_to' => null,
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

    public function noReschedule(): static
    {
        return $this->state(['allow_reschedule' => false]);
    }

    public function strict(): static
    {
        return $this->state([
            'hours_before' => 48,
            'penalty_percentage' => 100,
            'allow_reschedule' => false,
        ]);
    }

    public function lenient(): static
    {
        return $this->state([
            'hours_before' => 2,
            'penalty_percentage' => 0,
            'allow_reschedule' => true,
            'max_reschedules_per_month' => 5,
        ]);
    }

    public function forClassTypes(array $classTypes): static
    {
        return $this->state([
            'applicable_to' => ['class_types' => $classTypes],
        ]);
    }

    public function forPackages(array $packageIds): static
    {
        return $this->state([
            'applicable_to' => ['package_ids' => $packageIds],
        ]);
    }
}
