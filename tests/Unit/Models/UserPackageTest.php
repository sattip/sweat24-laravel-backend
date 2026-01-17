<?php

namespace Tests\Unit\Models;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_package_has_fillable_attributes(): void
    {
        $userPackage = new UserPackage();
        $fillable = $userPackage->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('package_id', $fillable);
        $this->assertContains('remaining_sessions', $fillable);
        $this->assertContains('status', $fillable);
    }

    public function test_user_package_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $userPackage = UserPackage::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
        ]);

        $this->assertInstanceOf(User::class, $userPackage->user);
        $this->assertEquals($user->id, $userPackage->user->id);
    }

    public function test_user_package_belongs_to_package(): void
    {
        $package = Package::factory()->create();
        $userPackage = UserPackage::factory()->create(['package_id' => $package->id]);

        $this->assertInstanceOf(Package::class, $userPackage->package);
        $this->assertEquals($package->id, $userPackage->package->id);
    }

    public function test_user_package_can_check_if_expired(): void
    {
        $expiredPackage = UserPackage::factory()->create([
            'expiry_date' => now()->subDay(),
        ]);

        $activePackage = UserPackage::factory()->create([
            'expiry_date' => now()->addWeek(),
        ]);

        $this->assertTrue($expiredPackage->isExpired());
        $this->assertFalse($activePackage->isExpired());
    }

    public function test_user_package_can_check_if_expiring_soon(): void
    {
        $expiringSoon = UserPackage::factory()->create([
            'expiry_date' => now()->addDays(3),
        ]);

        $notExpiringSoon = UserPackage::factory()->create([
            'expiry_date' => now()->addMonth(),
        ]);

        $this->assertTrue($expiringSoon->isExpiringSoon());
        $this->assertFalse($notExpiringSoon->isExpiringSoon());
    }

    public function test_user_package_can_calculate_days_until_expiry(): void
    {
        $userPackage = UserPackage::factory()->create([
            'expiry_date' => now()->addDays(10)->startOfDay(),
        ]);

        $days = $userPackage->getDaysUntilExpiry();
        $this->assertGreaterThanOrEqual(9, $days);
        $this->assertLessThanOrEqual(10, $days);
    }

    public function test_user_package_has_status_constants(): void
    {
        $this->assertEquals('active', UserPackage::STATUS_ACTIVE);
        $this->assertEquals('expired', UserPackage::STATUS_EXPIRED);
    }

    public function test_user_package_can_be_created_with_factory(): void
    {
        $userPackage = UserPackage::factory()->create();

        $this->assertInstanceOf(UserPackage::class, $userPackage);
        $this->assertDatabaseHas('user_packages', ['id' => $userPackage->id]);
    }
}
