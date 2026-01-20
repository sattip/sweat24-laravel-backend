<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\WorkTimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkTimeEntryFactory extends Factory
{
    protected $model = WorkTimeEntry::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(8, 14);
        $endHour = $startHour + $this->faker->numberBetween(2, 8);
        $hoursWorked = $endHour - $startHour;

        return [
            'instructor_id' => Instructor::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $endHour),
            'hours_worked' => $hoursWorked,
            'description' => $this->faker->sentence(),
            'approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'approved' => true,
            'approved_by' => $this->faker->name(),
            'approved_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'approved' => false,
        ]);
    }

    public function today(): static
    {
        return $this->state([
            'date' => now()->toDateString(),
        ]);
    }
}
