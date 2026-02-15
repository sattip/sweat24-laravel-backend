<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\ClassEvaluation;
use App\Models\GymClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClassEvaluationFactory extends Factory
{
    protected $model = ClassEvaluation::class;

    public function definition(): array
    {
        return [
            'class_id' => GymClass::factory(),
            'booking_id' => Booking::factory(),
            'evaluation_token' => Str::uuid()->toString(),
            'overall_rating' => $this->faker->numberBetween(1, 5),
            'instructor_rating' => $this->faker->numberBetween(1, 5),
            'facility_rating' => $this->faker->numberBetween(1, 5),
            'comments' => $this->faker->optional()->paragraph(),
            'tags' => null,
            'would_recommend' => $this->faker->boolean(80),
            'is_submitted' => false,
            'sent_at' => null,
            'submitted_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function excellent(): static
    {
        return $this->state([
            'overall_rating' => 5,
            'instructor_rating' => 5,
            'facility_rating' => 5,
            'would_recommend' => true,
        ]);
    }

    public function poor(): static
    {
        return $this->state([
            'overall_rating' => 1,
            'instructor_rating' => 1,
            'facility_rating' => 1,
            'would_recommend' => false,
        ]);
    }

    public function submitted(): static
    {
        return $this->state([
            'is_submitted' => true,
            'submitted_at' => now(),
        ]);
    }

    public function withComments(): static
    {
        return $this->state(['comments' => $this->faker->paragraph()]);
    }

    public function withTags(): static
    {
        return $this->state([
            'tags' => ['friendly', 'engaging', 'well-structured'],
        ]);
    }
}
