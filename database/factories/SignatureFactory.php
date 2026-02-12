<?php

namespace Database\Factories;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SignatureFactory extends Factory
{
    protected $model = Signature::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'signature_data' => 'data:image/png;base64,' . base64_encode($this->faker->text(100)),
            'ip_address' => $this->faker->ipv4(),
            'signed_at' => now(),
            'document_type' => 'terms_and_conditions',
            'document_version' => '1.0',
        ];
    }

    public function termsAndConditions(): static
    {
        return $this->state(['document_type' => 'terms_and_conditions']);
    }

    public function privacyPolicy(): static
    {
        return $this->state(['document_type' => 'privacy_policy']);
    }

    public function liability(): static
    {
        return $this->state(['document_type' => 'liability_waiver']);
    }
}
