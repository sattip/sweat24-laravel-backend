<?php

namespace Database\Factories;

use App\Models\WellnessThreshold;
use Illuminate\Database\Eloquent\Factories\Factory;

class WellnessThresholdFactory extends Factory
{
    protected $model = WellnessThreshold::class;

    public function definition(): array
    {
        // Use unique metric name to avoid conflicts with seeded data
        $uniqueMetric = 'test_metric_' . $this->faker->unique()->word();

        return [
            'metric' => $uniqueMetric,
            'level' => $this->faker->randomElement(['green', 'orange', 'red']),
            'min_value' => $this->faker->optional()->randomFloat(2, 0, 10),
            'max_value' => $this->faker->optional()->randomFloat(2, 5, 20),
            'label_el' => $this->faker->word(),
            'label_en' => $this->faker->word(),
            'tooltip_el' => $this->faker->optional()->sentence(),
            'tooltip_en' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function sleep(): static
    {
        return $this->state(['metric' => 'sleep']);
    }

    public function hydration(): static
    {
        return $this->state(['metric' => 'hydration']);
    }

    public function calories(): static
    {
        return $this->state(['metric' => 'calories']);
    }

    public function green(): static
    {
        return $this->state(['level' => 'green']);
    }

    public function orange(): static
    {
        return $this->state(['level' => 'orange']);
    }

    public function red(): static
    {
        return $this->state(['level' => 'red']);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
