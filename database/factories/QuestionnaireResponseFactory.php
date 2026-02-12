<?php

namespace Database\Factories;

use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionnaireResponseFactory extends Factory
{
    protected $model = QuestionnaireResponse::class;

    public function definition(): array
    {
        return [
            'questionnaire_id' => Questionnaire::factory(),
            'user_id' => User::factory(),
            'responses' => [
                '1' => 5,
                '2' => 'Great experience!',
            ],
            'trigger_type' => $this->faker->randomElement(['after_lesson', 'daily', 'weekly', 'manual']),
            'completed_at' => now(),
            'session_id' => null,
        ];
    }

    public function recent(): static
    {
        return $this->state(['completed_at' => now()->subHours(1)]);
    }

    public function complete(): static
    {
        return $this->state(['completed_at' => now()]);
    }

    public function afterLesson(): static
    {
        return $this->state(['trigger_type' => 'after_lesson']);
    }

    public function daily(): static
    {
        return $this->state(['trigger_type' => 'daily']);
    }
}
