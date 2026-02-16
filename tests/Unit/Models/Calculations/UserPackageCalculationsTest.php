<?php

namespace Tests\Unit\Models\Calculations;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class UserPackageCalculationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Days Until Expiry Calculations
    // ==========================================

    public function test_calculates_days_until_expiry_correctly(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'expiry_date' => now()->addDays(10)->startOfDay(),
        ]);

        $daysUntilExpiry = $userPackage->getDaysUntilExpiry();

        // Allow for slight variation due to time differences
        $this->assertGreaterThanOrEqual(9, $daysUntilExpiry);
        $this->assertLessThanOrEqual(10, $daysUntilExpiry);
    }

    public function test_days_until_expiry_negative_when_expired(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'expiry_date' => now()->subDays(5)->startOfDay(),
        ]);

        $daysUntilExpiry = $userPackage->getDaysUntilExpiry();

        // Allow for slight variation - should be negative
        $this->assertLessThan(0, $daysUntilExpiry);
        $this->assertGreaterThanOrEqual(-6, $daysUntilExpiry);
    }

    public function test_days_until_expiry_zero_when_expires_today(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'expiry_date' => now()->startOfDay(),
        ]);

        $daysUntilExpiry = $userPackage->getDaysUntilExpiry();

        // Should be 0 when expires today
        $this->assertEqualsWithDelta(0, $daysUntilExpiry, 1);
    }

    // ==========================================
    // Expiring Soon Tests
    // ==========================================

    public function test_is_expiring_soon_within_7_days(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $expiringPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'expiry_date' => now()->addDays(5),
        ]);

        $this->assertTrue($expiringPackage->isExpiringSoon());
    }

    public function test_is_not_expiring_soon_more_than_7_days(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $notExpiringPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'expiry_date' => now()->addDays(15),
        ]);

        $this->assertFalse($notExpiringPackage->isExpiringSoon());
    }

    public function test_is_not_expiring_soon_when_already_expired(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $expiredPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'expiry_date' => now()->subDays(1),
        ]);

        $this->assertFalse($expiredPackage->isExpiringSoon());
    }

    // ==========================================
    // Expired Status Tests
    // ==========================================

    public function test_is_expired_when_past_expiry(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $expiredPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'expiry_date' => now()->subDays(1),
        ]);

        $this->assertTrue($expiredPackage->isExpired());
    }

    public function test_is_not_expired_when_future_expiry(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $activePackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'expiry_date' => now()->addDays(10),
        ]);

        $this->assertFalse($activePackage->isExpired());
    }

    // ==========================================
    // Can Be Used Tests
    // ==========================================

    public function test_can_be_used_when_active_and_has_sessions(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'is_frozen' => false,
            'remaining_sessions' => 5,
            'expiry_date' => now()->addDays(30),
        ]);

        $this->assertTrue($userPackage->canBeUsed());
    }

    public function test_cannot_be_used_when_frozen(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'is_frozen' => true,
            'remaining_sessions' => 5,
            'expiry_date' => now()->addDays(30),
        ]);

        $this->assertFalse($userPackage->canBeUsed());
    }

    public function test_cannot_be_used_when_expired(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'is_frozen' => false,
            'remaining_sessions' => 5,
            'expiry_date' => now()->subDays(1),
        ]);

        $this->assertFalse($userPackage->canBeUsed());
    }

    public function test_cannot_be_used_when_no_sessions_remaining(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'is_frozen' => false,
            'remaining_sessions' => 0,
            'expiry_date' => now()->addDays(30),
        ]);

        $this->assertFalse($userPackage->canBeUsed());
    }

    public function test_cannot_be_used_when_status_not_active(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'expired',
            'is_frozen' => false,
            'remaining_sessions' => 5,
            'expiry_date' => now()->addDays(30),
        ]);

        $this->assertFalse($userPackage->canBeUsed());
    }

    // ==========================================
    // Custom Package Calculations
    // ==========================================

    public function test_effective_price_uses_custom_when_custom_package(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'is_custom_package' => true,
            'custom_price' => 80,
        ]);

        $this->assertEquals(80, $userPackage->getEffectivePrice());
    }

    public function test_effective_price_uses_package_when_not_custom(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'is_custom_package' => false,
        ]);

        $this->assertEquals(100, $userPackage->getEffectivePrice());
    }

    public function test_calculates_savings_for_custom_package(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 150]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'is_custom_package' => true,
            'custom_price' => 120,
        ]);

        // Savings = Original - Custom = 150 - 120 = 30
        $this->assertEquals(30, $userPackage->getSavings());
    }

    public function test_savings_zero_for_regular_package(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'is_custom_package' => false,
        ]);

        $this->assertEquals(0, $userPackage->getSavings());
    }

    public function test_effective_sessions_uses_custom_when_custom_package(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['sessions' => 10]);

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'is_custom_package' => true,
            'custom_sessions' => 15,
        ]);

        $this->assertEquals(15, $userPackage->getEffectiveSessions());
    }

    // ==========================================
    // Status Lifecycle Tests
    // ==========================================

    public function test_update_lifecycle_status_to_frozen(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'is_frozen' => true,
            'expiry_date' => now()->addDays(30),
        ]);

        $userPackage->updateLifecycleStatus();

        $this->assertEquals(UserPackage::STATUS_FROZEN, $userPackage->status);
    }

    public function test_update_lifecycle_status_to_expired(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'is_frozen' => false,
            'expiry_date' => now()->subDays(1),
        ]);

        $userPackage->updateLifecycleStatus();

        $this->assertEquals(UserPackage::STATUS_EXPIRED, $userPackage->status);
    }

    public function test_update_lifecycle_status_to_expiring_soon(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'active',
            'is_frozen' => false,
            'expiry_date' => now()->addDays(5),
        ]);

        $userPackage->updateLifecycleStatus();

        $this->assertEquals(UserPackage::STATUS_EXPIRING_SOON, $userPackage->status);
    }

    public function test_update_lifecycle_status_to_active(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();

        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'status' => 'expiring_soon',
            'is_frozen' => false,
            'expiry_date' => now()->addDays(15),
        ]);

        $userPackage->updateLifecycleStatus();

        $this->assertEquals(UserPackage::STATUS_ACTIVE, $userPackage->status);
    }
}
