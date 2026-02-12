<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\PayrollAgreement;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollAgreementFactory extends Factory
{
    protected $model = PayrollAgreement::class;

    public function definition(): array
    {
        $instructor = Instructor::factory()->create();

        return [
            'instructor_id' => $instructor->id,
            'instructor_name' => $instructor->name,
            'description' => $this->faker->sentence(),
            'type' => 'hourly_rate',
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'is_recurring' => false,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_active' => true,
        ];
    }

    public function hourlyRate(): static
    {
        return $this->state(['type' => 'hourly_rate']);
    }

    public function bonus(): static
    {
        return $this->state(['type' => 'bonus']);
    }

    public function deduction(): static
    {
        return $this->state(['type' => 'deduction']);
    }

    public function specialRate(): static
    {
        return $this->state(['type' => 'special_rate']);
    }

    public function recurring(): static
    {
        return $this->state(['is_recurring' => true]);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withEndDate(): static
    {
        return $this->state([
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->subMonth()->toDateString(),
            'is_active' => false,
        ]);
    }
}
