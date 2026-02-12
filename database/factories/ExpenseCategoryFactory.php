<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'category_type' => 'main',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
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

    public function withParent(): static
    {
        return $this->state([
            'category_type' => 'subcategory',
            'parent_id' => ExpenseCategory::factory(),
        ]);
    }

    public function main(): static
    {
        return $this->state(['category_type' => 'main']);
    }

    public function subcategory(): static
    {
        return $this->state(['category_type' => 'subcategory']);
    }
}
