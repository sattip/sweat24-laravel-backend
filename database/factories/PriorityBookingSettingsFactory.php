<?php

namespace Database\Factories;

use App\Models\PriorityBookingSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriorityBookingSettingsFactory extends Factory
{
    protected $model = PriorityBookingSettings::class;

    public function definition(): array
    {
        return [
            'priority_booking_window_days' => 30,
            'regular_booking_window_days' => 14,
            'priority_seats_release_hours' => 48,
            'default_priority_seats' => 5,
            'priority_advance_hours' => 48,
            'auto_release_enabled' => true,
            'priority_system_enabled' => true,
            'priority_packages' => [],
        ];
    }

    public function enabled(): static
    {
        return $this->state([
            'priority_system_enabled' => true,
            'auto_release_enabled' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state([
            'priority_system_enabled' => false,
            'auto_release_enabled' => false,
        ]);
    }

    public function withPriorityPackages(array $packageIds): static
    {
        return $this->state(['priority_packages' => $packageIds]);
    }
}
