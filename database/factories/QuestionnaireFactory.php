<?php

namespace Database\Factories;

use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionnaireFactory extends Factory
{
    protected $model = Questionnaire::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'triggers' => [
                'type' => $this->faker->randomElement(['after_lesson', 'daily', 'weekly', 'manual']),
            ],
            'frequency_settings' => null,
            'questions' => [
                ['id' => 1, 'question' => 'How satisfied are you?', 'type' => 'rating'],
                ['id' => 2, 'question' => 'Any feedback?', 'type' => 'text'],
            ],
            'is_active' => true,
            'created_by' => User::factory(),
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

    public function feedback(): static
    {
        return $this->state([
            'triggers' => ['type' => 'after_lesson'],
        ]);
    }

    public function survey(): static
    {
        return $this->state([
            'triggers' => ['type' => 'manual'],
        ]);
    }
}
