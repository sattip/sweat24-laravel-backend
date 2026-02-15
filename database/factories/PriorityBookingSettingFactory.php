<?php

namespace Database\Factories;

use App\Models\PriorityBookingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriorityBookingSettingFactory extends Factory
{
    protected $model = PriorityBookingSetting::class;

    public function definition(): array
    {
        return [
            'priority_booking_window_days' => 30,
            'regular_booking_window_days' => 14,
            'default_priority_seats' => 5,
            'priority_advance_hours' => 48,
            'priority_seats_release_hours' => 24,
            'auto_release_enabled' => true,
            'priority_system_enabled' => true,
            'priority_packages' => null,
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

    public function withBookingWindows(int $priority, int $regular): static
    {
        return $this->state([
            'priority_booking_window_days' => $priority,
            'regular_booking_window_days' => $regular,
        ]);
    }
}
