<?php

namespace Database\Factories;

use App\Models\NotificationFilter;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFilterFactory extends Factory
{
    protected $model = NotificationFilter::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'criteria' => [],
            'is_active' => true,
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

    public function withPackageFilter(array $packageTypes): static
    {
        return $this->state([
            'criteria' => ['package_types' => $packageTypes],
        ]);
    }

    public function withMembershipFilter(string $status): static
    {
        return $this->state([
            'criteria' => ['membership_status' => $status],
        ]);
    }
}
