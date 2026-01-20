<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Personal Training',
            'Group Fitness',
            'Yoga',
            'Pilates',
            'CrossFit',
            'Swimming',
            'Boxing',
            'EMS Training',
            'Nutrition Coaching',
            'Physiotherapy',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'icon' => 'fitness',
            'trial_price' => $this->faker->randomFloat(2, 10, 50),
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(0, 100),
            'allows_trial' => true,
            'max_trial_per_user' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function noTrial(): static
    {
        return $this->state([
            'allows_trial' => false,
            'trial_price' => null,
        ]);
    }

    public function withMultipleTrials(int $count = 3): static
    {
        return $this->state(['max_trial_per_user' => $count]);
    }
}
