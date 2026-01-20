<?php

namespace Database\Factories;

use App\Models\ParentConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParentConsentFactory extends Factory
{
    protected $model = ParentConsent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_full_name' => $this->faker->name(),
            'father_first_name' => $this->faker->firstName('male'),
            'father_last_name' => $this->faker->lastName(),
            'mother_first_name' => $this->faker->firstName('female'),
            'mother_last_name' => $this->faker->lastName(),
            'parent_birth_date' => $this->faker->dateTimeBetween('-60 years', '-30 years'),
            'parent_id_number' => $this->faker->unique()->numerify('??######'),
            'parent_phone' => $this->faker->phoneNumber(),
            'parent_location' => $this->faker->city(),
            'parent_street' => $this->faker->streetName(),
            'parent_street_number' => $this->faker->buildingNumber(),
            'parent_postal_code' => $this->faker->postcode(),
            'parent_email' => $this->faker->email(),
            'consent_accepted' => true,
            'signature' => 'data:image/png;base64,' . base64_encode($this->faker->text(100)),
            'consent_text' => $this->faker->paragraphs(3, true),
            'consent_version' => '1.0',
        ];
    }

    public function withConsent(): static
    {
        return $this->state([
            'consent_accepted' => true,
        ]);
    }

    public function withoutConsent(): static
    {
        return $this->state([
            'consent_accepted' => false,
        ]);
    }
}
