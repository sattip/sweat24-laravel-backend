<?php

namespace Database\Factories;

use App\Models\ProgressPhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgressPhotoFactory extends Factory
{
    protected $model = ProgressPhoto::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'image_path' => 'progress/' . $this->faker->uuid() . '.jpg',
            'caption' => $this->faker->optional()->sentence(),
            'uploaded_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function withCaption(): static
    {
        return $this->state(['caption' => $this->faker->sentence()]);
    }

    public function today(): static
    {
        return $this->state(['uploaded_at' => now()]);
    }
}
