<?php

namespace Tests\Unit\Models;

use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyRedemptionTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_loyalty_redemption(): void
    {
        $redemption = LoyaltyRedemption::factory()->create([
            'points_used' => 500,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('loyalty_redemptions', [
            'id' => $redemption->id,
            'points_used' => 500,
            'status' => 'pending',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_redeemed_at_is_datetime(): void
    {
        $redemption = LoyaltyRedemption::factory()->create([
            'redeemed_at' => '2026-01-20 10:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $redemption->redeemed_at);
    }

    public function test_expires_at_is_datetime(): void
    {
        $redemption = LoyaltyRedemption::factory()->create([
            'expires_at' => '2026-02-20 23:59:59',
        ]);

        $this->assertInstanceOf(Carbon::class, $redemption->expires_at);
    }

    public function test_used_at_is_datetime(): void
    {
        $redemption = LoyaltyRedemption::factory()->used()->create();

        $this->assertInstanceOf(Carbon::class, $redemption->used_at);
    }

    public function test_reward_snapshot_is_array(): void
    {
        $redemption = LoyaltyRedemption::factory()->create([
            'reward_snapshot' => ['name' => 'Free Session', 'points_cost' => 500],
        ]);

        $this->assertIsArray($redemption->reward_snapshot);
        $this->assertEquals('Free Session', $redemption->reward_snapshot['name']);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $redemption = LoyaltyRedemption::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('Test User', $redemption->user->name);
    }

    public function test_belongs_to_loyalty_reward(): void
    {
        $reward = LoyaltyReward::factory()->create(['name' => 'Free Yoga']);
        $redemption = LoyaltyRedemption::factory()->create([
            'loyalty_reward_id' => $reward->id,
        ]);

        $this->assertEquals('Free Yoga', $redemption->loyaltyReward->name);
    }

    // ==========================================
    // Auto-generated Redemption Code Tests
    // ==========================================

    public function test_redemption_code_auto_generated_on_create(): void
    {
        $redemption = LoyaltyRedemption::factory()->create([
            'redemption_code' => null,
        ]);

        $this->assertNotNull($redemption->redemption_code);
        $this->assertStringStartsWith('LYL-', $redemption->redemption_code);
    }

    public function test_redemption_code_format(): void
    {
        $redemption = LoyaltyRedemption::factory()->create();

        // Format: LYL-XXXXXXXX (LYL- + 8 uppercase chars)
        $this->assertMatchesRegularExpression('/^LYL-[A-Z0-9]{8}$/', $redemption->redemption_code);
    }

    public function test_custom_redemption_code_not_overwritten(): void
    {
        $redemption = LoyaltyRedemption::factory()->create([
            'redemption_code' => 'CUSTOM-CODE-123',
        ]);

        $this->assertEquals('CUSTOM-CODE-123', $redemption->redemption_code);
    }

    // ==========================================
    // isExpired Tests
    // ==========================================

    public function test_is_expired_when_past_expires_at(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $redemption = LoyaltyRedemption::factory()->expired()->create();

        $this->assertTrue($redemption->isExpired());

        Carbon::setTestNow();
    }

    public function test_is_not_expired_when_before_expires_at(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $redemption = LoyaltyRedemption::factory()->create([
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $this->assertFalse($redemption->isExpired());

        Carbon::setTestNow();
    }

    public function test_is_not_expired_when_no_expires_at(): void
    {
        $redemption = LoyaltyRedemption::factory()->neverExpires()->create();

        $this->assertFalse($redemption->isExpired());
    }

    // ==========================================
    // isActive Tests
    // ==========================================

    public function test_is_active_when_pending_and_not_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $redemption = LoyaltyRedemption::factory()->pending()->create([
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $this->assertTrue($redemption->isActive());

        Carbon::setTestNow();
    }

    public function test_is_active_when_approved_and_not_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $redemption = LoyaltyRedemption::factory()->approved()->create([
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $this->assertTrue($redemption->isActive());

        Carbon::setTestNow();
    }

    public function test_is_not_active_when_used(): void
    {
        $redemption = LoyaltyRedemption::factory()->used()->create();

        $this->assertFalse($redemption->isActive());
    }

    public function test_is_not_active_when_cancelled(): void
    {
        $redemption = LoyaltyRedemption::factory()->cancelled()->create();

        $this->assertFalse($redemption->isActive());
    }

    public function test_is_not_active_when_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $redemption = LoyaltyRedemption::factory()->pending()->expired()->create();

        $this->assertFalse($redemption->isActive());

        Carbon::setTestNow();
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        // Active pending redemption
        LoyaltyRedemption::factory()->pending()->create([
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Active approved redemption
        LoyaltyRedemption::factory()->approved()->create([
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Used redemption (not active)
        LoyaltyRedemption::factory()->used()->create();

        // Expired redemption (not active)
        LoyaltyRedemption::factory()->expired()->create();

        $activeRedemptions = LoyaltyRedemption::active()->get();

        $this->assertCount(2, $activeRedemptions);

        Carbon::setTestNow();
    }

    public function test_scope_expiring_soon(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        // Expiring in 3 days (within default 7 days)
        LoyaltyRedemption::factory()->expiringSoon(3)->create();

        // Expiring in 10 days (outside default 7 days)
        LoyaltyRedemption::factory()->approved()->create([
            'expires_at' => Carbon::now()->addDays(10),
        ]);

        // Already expired
        LoyaltyRedemption::factory()->expired()->create();

        // Used (wrong status)
        LoyaltyRedemption::factory()->used()->create([
            'expires_at' => Carbon::now()->addDays(3),
        ]);

        $expiringSoon = LoyaltyRedemption::expiringSoon()->get();

        $this->assertCount(1, $expiringSoon);

        Carbon::setTestNow();
    }

    public function test_scope_expiring_soon_with_custom_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        // Expiring in 3 days
        LoyaltyRedemption::factory()->expiringSoon(3)->create();

        // Expiring in 10 days
        LoyaltyRedemption::factory()->approved()->create([
            'expires_at' => Carbon::now()->addDays(10),
        ]);

        $expiringSoon = LoyaltyRedemption::expiringSoon(14)->get();

        $this->assertCount(2, $expiringSoon);

        Carbon::setTestNow();
    }
}
