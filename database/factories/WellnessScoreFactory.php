<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WellnessScore;
use Illuminate\Database\Eloquent\Factories\Factory;

class WellnessScoreFactory extends Factory
{
    protected $model = WellnessScore::class;

    public function definition(): array
    {
        $caloriesConsumed = $this->faker->numberBetween(1200, 3000);
        $tdee = $this->faker->numberBetween(1800, 2500);

        return [
            'user_id' => User::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'sleep_hours' => $this->faker->randomFloat(2, 4, 10),
            'sleep_quality' => $this->faker->numberBetween(1, 10),
            'hydration_liters' => $this->faker->randomFloat(2, 0.5, 4),
            'calories_consumed' => $caloriesConsumed,
            'tdee' => $tdee,
            'calories_percentage' => round(($caloriesConsumed / $tdee) * 100, 2),
            'energy_level' => $this->faker->numberBetween(1, 10),
            'mood_level' => $this->faker->numberBetween(1, 10),
            'stress_level' => $this->faker->numberBetween(1, 10),
            'soreness_level' => $this->faker->numberBetween(1, 10),
            'hrv' => $this->faker->numberBetween(20, 100),
            'wellness_score' => $this->faker->numberBetween(40, 100),
            'sleep_alert' => WellnessScore::ALERT_GREEN,
            'hydration_alert' => WellnessScore::ALERT_GREEN,
            'calories_alert' => WellnessScore::ALERT_GREEN,
            'overall_alert' => WellnessScore::ALERT_GREEN,
            'notes' => null,
        ];
    }

    public function today(): static
    {
        return $this->state(['date' => now()->toDateString()]);
    }

    public function withGreenAlerts(): static
    {
        return $this->state([
            'sleep_hours' => 8,
            'hydration_liters' => 2.5,
            'calories_percentage' => 100,
            'sleep_alert' => WellnessScore::ALERT_GREEN,
            'hydration_alert' => WellnessScore::ALERT_GREEN,
            'calories_alert' => WellnessScore::ALERT_GREEN,
            'overall_alert' => WellnessScore::ALERT_GREEN,
        ]);
    }

    public function withOrangeAlert(): static
    {
        return $this->state([
            'sleep_hours' => 6,
            'sleep_alert' => WellnessScore::ALERT_ORANGE,
            'overall_alert' => WellnessScore::ALERT_ORANGE,
        ]);
    }

    public function withRedAlert(): static
    {
        return $this->state([
            'sleep_hours' => 4,
            'hydration_liters' => 0.5,
            'sleep_alert' => WellnessScore::ALERT_RED,
            'hydration_alert' => WellnessScore::ALERT_RED,
            'overall_alert' => WellnessScore::ALERT_RED,
        ]);
    }

    public function highWellness(): static
    {
        return $this->state([
            'wellness_score' => $this->faker->numberBetween(80, 100),
            'energy_level' => $this->faker->numberBetween(8, 10),
            'mood_level' => $this->faker->numberBetween(8, 10),
            'stress_level' => $this->faker->numberBetween(1, 3),
        ]);
    }

    public function lowWellness(): static
    {
        return $this->state([
            'wellness_score' => $this->faker->numberBetween(20, 40),
            'energy_level' => $this->faker->numberBetween(1, 3),
            'mood_level' => $this->faker->numberBetween(1, 3),
            'stress_level' => $this->faker->numberBetween(7, 10),
        ]);
    }
}
