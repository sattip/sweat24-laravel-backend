<?php

namespace Database\Factories;

use App\Models\NewMemberInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewMemberInfoFactory extends Factory
{
    protected $model = NewMemberInfo::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraphs(3, true),
            'category' => $this->faker->randomElement(['general', 'rules', 'benefits', 'schedule', 'equipment', 'faq']),
            'is_active' => true,
            'order' => $this->faker->numberBetween(0, 20),
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

    public function benefits(): static
    {
        return $this->state(['category' => 'benefits']);
    }

    public function faq(): static
    {
        return $this->state(['category' => 'faq']);
    }
}
