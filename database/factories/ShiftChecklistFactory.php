<?php

namespace Database\Factories;

use App\Models\ShiftChecklist;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftChecklistFactory extends Factory
{
    protected $model = ShiftChecklist::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'work_session_id' => null,
            'type' => $this->faker->randomElement(['opening', 'closing']),
            'cash_counted' => $this->faker->randomElement(['yes', 'no', 'na']),
            'cash_amount' => $this->faker->randomFloat(2, 0, 500),
            'towels_checked' => 'yes',
            'towels_count' => $this->faker->numberBetween(10, 100),
            'water_checked' => 'yes',
            'water_count' => $this->faker->numberBetween(10, 50),
            'equipment_checked' => 'yes',
            'equipment_notes' => null,
            'area_tidy' => 'yes',
            'locker_rooms_checked' => 'yes',
            'showers_checked' => 'yes',
            'doors_locked' => null,
            'lights_off' => null,
            'ac_off' => null,
            'alarm_set' => null,
            'notes' => null,
            'issues_reported' => null,
            'completed_at' => now(),
        ];
    }

    public function opening(): static
    {
        return $this->state([
            'type' => 'opening',
            'doors_locked' => null,
            'lights_off' => null,
            'ac_off' => null,
            'alarm_set' => null,
        ]);
    }

    public function closing(): static
    {
        return $this->state([
            'type' => 'closing',
            'doors_locked' => 'yes',
            'lights_off' => 'yes',
            'ac_off' => 'yes',
            'alarm_set' => 'yes',
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'completed_at' => now(),
            'cash_counted' => 'yes',
            'towels_checked' => 'yes',
            'water_checked' => 'yes',
            'equipment_checked' => 'yes',
            'area_tidy' => 'yes',
            'locker_rooms_checked' => 'yes',
            'showers_checked' => 'yes',
        ]);
    }
}
