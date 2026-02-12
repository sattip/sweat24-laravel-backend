<?php

namespace Tests\Unit\Models;

use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyRewardTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_loyalty_reward(): void
    {
        $reward = LoyaltyReward::factory()->create([
            'name' => 'Free Yoga Session',
            'points_cost' => 500,
        ]);

        $this->assertDatabaseHas('loyalty_rewards', [
            'name' => 'Free Yoga Session',
            'points_cost' => 500,
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_discount_percentage_is_decimal(): void
    {
        $reward = LoyaltyReward::factory()->discountPercentage(15.50)->create();

        $this->assertEquals('15.50', $reward->discount_percentage);
    }

    public function test_discount_amount_is_decimal(): void
    {
        $reward = LoyaltyReward::factory()->discountAmount(25.75)->create();

        $this->assertEquals('25.75', $reward->discount_amount);
    }

    public function test_is_active_is_boolean(): void
    {
        $reward = LoyaltyReward::factory()->create(['is_active' => 1]);

        $this->assertTrue($reward->is_active);
        $this->assertIsBool($reward->is_active);
    }

    public function test_valid_from_is_datetime(): void
    {
        $reward = LoyaltyReward::factory()->create([
            'valid_from' => '2026-01-01 00:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $reward->valid_from);
    }

    public function test_valid_until_is_datetime(): void
    {
        $reward = LoyaltyReward::factory()->create([
            'valid_until' => '2026-12-31 23:59:59',
        ]);

        $this->assertInstanceOf(Carbon::class, $reward->valid_until);
    }

    public function test_terms_conditions_is_array(): void
    {
        $reward = LoyaltyReward::factory()->create([
            'terms_conditions' => ['Must book in advance', 'Valid weekdays only'],
        ]);

        $this->assertIsArray($reward->terms_conditions);
        $this->assertContains('Must book in advance', $reward->terms_conditions);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_has_many_redemptions(): void
    {
        $reward = LoyaltyReward::factory()->create();

        LoyaltyRedemption::factory()->count(3)->create([
            'loyalty_reward_id' => $reward->id,
        ]);

        $this->assertCount(3, $reward->redemptions);
    }

    // ==========================================
    // is_available Accessor Tests
    // ==========================================

    public function test_is_available_when_active_and_no_date_limits(): void
    {
        $reward = LoyaltyReward::factory()->create([
            'is_active' => true,
            'valid_from' => null,
            'valid_until' => null,
            'max_redemptions' => null,
        ]);

        $this->assertTrue($reward->is_available);
    }

    public function test_is_not_available_when_inactive(): void
    {
        $reward = LoyaltyReward::factory()->inactive()->create();

        $this->assertFalse($reward->is_available);
    }

    public function test_is_not_available_before_valid_from(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $reward = LoyaltyReward::factory()->notYetValid()->create();

        $this->assertFalse($reward->is_available);

        Carbon::setTestNow();
    }

    public function test_is_not_available_after_valid_until(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $reward = LoyaltyReward::factory()->expired()->create();

        $this->assertFalse($reward->is_available);

        Carbon::setTestNow();
    }

    public function test_is_not_available_when_fully_redeemed(): void
    {
        $reward = LoyaltyReward::factory()->fullyRedeemed()->create();

        $this->assertFalse($reward->is_available);
    }

    public function test_is_available_when_redemptions_remaining(): void
    {
        $reward = LoyaltyReward::factory()->withLimit(10)->create([
            'current_redemptions' => 5,
        ]);

        $this->assertTrue($reward->is_available);
    }

    // ==========================================
    // redemptions_remaining Accessor Tests
    // ==========================================

    public function test_redemptions_remaining_with_limit(): void
    {
        $reward = LoyaltyReward::factory()->create([
            'max_redemptions' => 10,
            'current_redemptions' => 3,
        ]);

        $this->assertEquals(7, $reward->redemptions_remaining);
    }

    public function test_redemptions_remaining_null_when_unlimited(): void
    {
        $reward = LoyaltyReward::factory()->create([
            'max_redemptions' => null,
        ]);

        $this->assertNull($reward->redemptions_remaining);
    }

    public function test_redemptions_remaining_zero_when_fully_redeemed(): void
    {
        $reward = LoyaltyReward::factory()->fullyRedeemed()->create();

        $this->assertEquals(0, $reward->redemptions_remaining);
    }

    // ==========================================
    // canBeRedeemedBy Tests
    // ==========================================

    public function test_can_be_redeemed_by_user_with_enough_points(): void
    {
        $user = User::factory()->create();

        // Create loyalty points for the user (balance comes from accessor)
        \App\Models\LoyaltyPoint::factory()->create([
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'earned',
            'expires_at' => null,
        ]);

        $reward = LoyaltyReward::factory()->active()->create([
            'points_cost' => 300,
        ]);

        $this->assertTrue($reward->canBeRedeemedBy($user));
    }

    public function test_cannot_be_redeemed_by_user_with_insufficient_points(): void
    {
        $user = User::factory()->create();

        // Create only 100 loyalty points
        \App\Models\LoyaltyPoint::factory()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'expires_at' => null,
        ]);

        $reward = LoyaltyReward::factory()->active()->create([
            'points_cost' => 300,
        ]);

        $this->assertFalse($reward->canBeRedeemedBy($user));
    }

    public function test_cannot_be_redeemed_when_not_available(): void
    {
        $user = User::factory()->create();

        // Create 1000 loyalty points
        \App\Models\LoyaltyPoint::factory()->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'type' => 'earned',
            'expires_at' => null,
        ]);

        $reward = LoyaltyReward::factory()->inactive()->create([
            'points_cost' => 300,
        ]);

        $this->assertFalse($reward->canBeRedeemedBy($user));
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_available(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        // Available reward
        LoyaltyReward::factory()->active()->create();

        // Inactive reward
        LoyaltyReward::factory()->inactive()->create();

        // Expired reward
        LoyaltyReward::factory()->expired()->create();

        // Not yet valid reward
        LoyaltyReward::factory()->notYetValid()->create();

        // Fully redeemed reward
        LoyaltyReward::factory()->fullyRedeemed()->create();

        $availableRewards = LoyaltyReward::available()->get();

        $this->assertCount(1, $availableRewards);

        Carbon::setTestNow();
    }
}
