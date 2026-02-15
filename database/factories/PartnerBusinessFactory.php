<?php

namespace Database\Factories;

use App\Models\PartnerBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerBusinessFactory extends Factory
{
    protected $model = PartnerBusiness::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'logo_url' => null,
            'contact_email' => $this->faker->email(),
            'contact_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(0, 100),
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
}
