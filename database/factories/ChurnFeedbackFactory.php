<?php

namespace Database\Factories;

use App\Models\ChurnFeedback;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChurnFeedbackFactory extends Factory
{
    protected $model = ChurnFeedback::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_package_id' => UserPackage::factory(),
            'expired_at' => $this->faker->dateTimeBetween('-30 days', '+30 days'),
            'status' => 'pending',
            'survey_type' => $this->faker->randomElement(['quick', 'mini']),
            'reasons' => null,
            'reason_will_continue' => false,
            'reason_price_value' => false,
            'reason_financial_issue' => false,
            'reason_schedule' => false,
            'reason_program_mismatch' => false,
            'reason_trainer_mismatch' => false,
            'reason_distance' => false,
            'reason_health' => false,
            'reason_priorities' => false,
            'reason_other' => false,
            'comment' => null,
            'improvements' => null,
            'return_intent_score' => null,
            'future_return_intent' => null,
            'wants_alternative_package' => false,
            'winback_consent' => false,
            'winback_accepted' => false,
            'pause_response' => false,
            'opted_out' => false,
        ];
    }

    public function churned(): static
    {
        return $this->state([
            'status' => 'churn',
            'responded_at' => now(),
        ]);
    }

    public function paused(): static
    {
        return $this->state([
            'status' => 'pause',
            'responded_at' => now(),
            'pause_response' => true,
        ]);
    }

    public function renewed(): static
    {
        return $this->state([
            'status' => 'renewed',
            'responded_at' => now(),
        ]);
    }

    public function wouldReturn(): static
    {
        return $this->state([
            'future_return_intent' => 'yes',
            'return_intent_score' => $this->faker->numberBetween(7, 10),
        ]);
    }

    public function wouldNotReturn(): static
    {
        return $this->state([
            'future_return_intent' => 'no',
            'return_intent_score' => $this->faker->numberBetween(0, 3),
        ]);
    }
}
