<?php

namespace Database\Factories;

use App\Models\PackageNotificationLog;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageNotificationLogFactory extends Factory
{
    protected $model = PackageNotificationLog::class;

    public function definition(): array
    {
        return [
            'user_package_id' => UserPackage::factory(),
            'user_id' => User::factory(),
            'notification_type' => $this->faker->randomElement(['expiry_warning', 'expired', 'renewal_reminder']),
            'channel' => $this->faker->randomElement(['push', 'email', 'sms']),
            'sent_successfully' => true,
            'error_message' => null,
            'days_until_expiry' => $this->faker->numberBetween(0, 30),
        ];
    }

    public function successful(): static
    {
        return $this->state([
            'sent_successfully' => true,
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'sent_successfully' => false,
            'error_message' => $this->faker->sentence(),
        ]);
    }

    public function expiryWarning(): static
    {
        return $this->state(['notification_type' => 'expiry_warning']);
    }

    public function expired(): static
    {
        return $this->state([
            'notification_type' => 'expired',
            'days_until_expiry' => 0,
        ]);
    }

    public function renewalReminder(): static
    {
        return $this->state(['notification_type' => 'renewal_reminder']);
    }

    public function viaPush(): static
    {
        return $this->state(['channel' => 'push']);
    }

    public function viaEmail(): static
    {
        return $this->state(['channel' => 'email']);
    }

    public function viaSms(): static
    {
        return $this->state(['channel' => 'sms']);
    }
}
