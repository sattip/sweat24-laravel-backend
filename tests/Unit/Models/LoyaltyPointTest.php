<?php

namespace Tests\Unit\Models;

use App\Models\LoyaltyPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyPointTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_loyalty_point(): void
    {
        $point = LoyaltyPoint::factory()->create([
            'type' => 'earned',
            'source' => 'purchase',
        ]);

        $this->assertDatabaseHas('loyalty_points', [
            'id' => $point->id,
            'type' => 'earned',
            'source' => 'purchase',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_amount_is_decimal(): void
    {
        $point = LoyaltyPoint::factory()->create([
            'amount' => 50.75,
        ]);

        $this->assertEquals('50.75', $point->amount);
    }

    public function test_balance_after_is_decimal(): void
    {
        $point = LoyaltyPoint::factory()->create([
            'balance_after' => 125.50,
        ]);

        $this->assertEquals('125.50', $point->balance_after);
    }

    public function test_expires_at_is_datetime(): void
    {
        $point = LoyaltyPoint::factory()->create([
            'expires_at' => '2026-12-31 23:59:59',
        ]);

        $this->assertInstanceOf(Carbon::class, $point->expires_at);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $point = LoyaltyPoint::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('Test User', $point->user->name);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_expiring_soon(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $user = User::factory()->create();

        // Point expiring in 15 days (within default 30 days)
        LoyaltyPoint::factory()->expiringSoon(15)->create(['user_id' => $user->id]);

        // Point expiring in 45 days (outside default 30 days)
        LoyaltyPoint::factory()->expiringSoon(45)->create(['user_id' => $user->id]);

        // Point already expired
        LoyaltyPoint::factory()->expired()->create(['user_id' => $user->id]);

        // Spent point (should not appear - scope filters by type='earned')
        LoyaltyPoint::factory()->spent()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(10),
        ]);

        $expiringSoon = LoyaltyPoint::expiringSoon()->get();

        $this->assertCount(1, $expiringSoon);

        Carbon::setTestNow();
    }

    public function test_scope_expiring_soon_with_custom_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $user = User::factory()->create();

        // Point expiring in 5 days
        LoyaltyPoint::factory()->expiringSoon(5)->create(['user_id' => $user->id]);

        // Point expiring in 15 days
        LoyaltyPoint::factory()->expiringSoon(15)->create(['user_id' => $user->id]);

        $expiringSoon = LoyaltyPoint::expiringSoon(7)->get();

        $this->assertCount(1, $expiringSoon);

        Carbon::setTestNow();
    }

    public function test_scope_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $user = User::factory()->create();

        // Active point with future expiry
        LoyaltyPoint::factory()->expiringSoon(30)->create(['user_id' => $user->id]);

        // Active point with no expiry
        LoyaltyPoint::factory()->neverExpires()->create(['user_id' => $user->id]);

        // Expired point
        LoyaltyPoint::factory()->expired()->create(['user_id' => $user->id]);

        $activePoints = LoyaltyPoint::active()->get();

        $this->assertCount(2, $activePoints);

        Carbon::setTestNow();
    }

    // ==========================================
    // Polymorphic Reference Tests
    // ==========================================

    public function test_can_store_reference_type_and_id(): void
    {
        $point = LoyaltyPoint::factory()->create([
            'reference_type' => 'App\\Models\\Booking',
            'reference_id' => 123,
        ]);

        $this->assertEquals('App\\Models\\Booking', $point->reference_type);
        $this->assertEquals(123, $point->reference_id);
    }

    // ==========================================
    // Type Tests
    // ==========================================

    public function test_earned_type(): void
    {
        $point = LoyaltyPoint::factory()->earned()->create();

        $this->assertEquals('earned', $point->type);
    }

    public function test_spent_type(): void
    {
        $point = LoyaltyPoint::factory()->spent()->create();

        $this->assertEquals('spent', $point->type);
    }
}
