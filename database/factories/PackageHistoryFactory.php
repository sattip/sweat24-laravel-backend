<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PackageHistory;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageHistoryFactory extends Factory
{
    protected $model = PackageHistory::class;

    public function definition(): array
    {
        return [
            'user_package_id' => UserPackage::factory(),
            'user_id' => User::factory(),
            'action' => 'purchased',
            'previous_status' => null,
            'new_status' => 'active',
            'sessions_before' => null,
            'sessions_after' => $this->faker->numberBetween(5, 20),
            'expiry_date_before' => null,
            'expiry_date_after' => now()->addMonths(1),
            'notes' => null,
            'performed_by' => User::factory(),
        ];
    }

    public function purchased(): static
    {
        return $this->state([
            'action' => 'purchased',
            'previous_status' => null,
            'new_status' => 'active',
        ]);
    }

    public function renewed(): static
    {
        return $this->state([
            'action' => 'renewed',
            'previous_status' => 'active',
            'new_status' => 'active',
            'expiry_date_before' => now()->subDays(30),
            'expiry_date_after' => now()->addMonths(1),
        ]);
    }

    public function frozen(): static
    {
        return $this->state([
            'action' => 'frozen',
            'previous_status' => 'active',
            'new_status' => 'frozen',
            'notes' => json_encode(['reason' => 'User request']),
        ]);
    }

    public function unfrozen(): static
    {
        return $this->state([
            'action' => 'unfrozen',
            'previous_status' => 'frozen',
            'new_status' => 'active',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'action' => 'cancelled',
            'previous_status' => 'active',
            'new_status' => 'cancelled',
            'notes' => json_encode(['reason' => 'User requested cancellation']),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'action' => 'expired',
            'previous_status' => 'active',
            'new_status' => 'expired',
        ]);
    }
}
