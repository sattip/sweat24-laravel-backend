<?php

namespace Database\Factories;

use App\Models\EmployeeManual;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeManualFactory extends Factory
{
    protected $model = EmployeeManual::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraphs(5, true),
            'category' => $this->faker->randomElement(['general', 'rules', 'procedures', 'safety', 'customer_service', 'equipment', 'faq']),
            'is_active' => true,
            'order' => $this->faker->numberBetween(1, 20),
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

    public function general(): static
    {
        return $this->state(['category' => 'general']);
    }

    public function rules(): static
    {
        return $this->state(['category' => 'rules']);
    }

    public function procedures(): static
    {
        return $this->state(['category' => 'procedures']);
    }

    public function safety(): static
    {
        return $this->state(['category' => 'safety']);
    }

    public function customerService(): static
    {
        return $this->state(['category' => 'customer_service']);
    }
}
