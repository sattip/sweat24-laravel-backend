<?php

namespace Tests\Unit\Models\Calculations;

use App\Models\LoyaltyPoint;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyPointsCalculationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create loyalty point with required fields
     */
    private function createLoyaltyPoint(array $data): LoyaltyPoint
    {
        $defaults = [
            'description' => 'Test points',
            'balance_after' => 0,
        ];

        return LoyaltyPoint::create(array_merge($defaults, $data));
    }

    // ==========================================
    // Balance Calculation Tests
    // ==========================================

    public function test_calculates_loyalty_points_balance_correctly(): void
    {
        $user = User::factory()->create();

        // Add earned points
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Points for booking',
            'balance_after' => 100,
        ]);

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'referral',
            'description' => 'Referral bonus',
            'balance_after' => 150,
        ]);

        // Add redeemed points (negative)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => -30,
            'type' => 'redeemed',
            'source' => 'redemption',
            'description' => 'Points redeemed',
            'balance_after' => 120,
        ]);

        $user->refresh();

        // Balance should be 100 + 50 - 30 = 120
        $this->assertEquals(120, $user->loyalty_points_balance);
    }

    public function test_excludes_expired_points_from_balance(): void
    {
        $user = User::factory()->create();

        // Active points (no expiry)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Active points',
            'balance_after' => 100,
            'expires_at' => null,
        ]);

        // Active points (future expiry)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Future expiry points',
            'balance_after' => 150,
            'expires_at' => now()->addMonths(6),
        ]);

        // Expired points (should not count)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 75,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Expired points',
            'balance_after' => 225,
            'expires_at' => now()->subDay(),
        ]);

        $user->refresh();

        // Balance should only include active: 100 + 50 = 150
        $this->assertEquals(150, $user->loyalty_points_balance);
    }

    public function test_balance_is_zero_for_new_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(0, $user->loyalty_points_balance);
    }

    public function test_balance_can_be_zero_after_redemptions(): void
    {
        $user = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Earned points',
            'balance_after' => 100,
        ]);

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => -100,
            'type' => 'redeemed',
            'source' => 'redemption',
            'description' => 'Full redemption',
            'balance_after' => 0,
        ]);

        $user->refresh();

        $this->assertEquals(0, $user->loyalty_points_balance);
    }

    // ==========================================
    // Has Enough Points Tests
    // ==========================================

    public function test_has_enough_loyalty_points_returns_true(): void
    {
        $user = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Test points',
            'balance_after' => 100,
        ]);

        $user->refresh();

        $this->assertTrue($user->hasEnoughLoyaltyPoints(50));
        $this->assertTrue($user->hasEnoughLoyaltyPoints(100));
    }

    public function test_has_enough_loyalty_points_returns_false(): void
    {
        $user = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Test points',
            'balance_after' => 50,
        ]);

        $user->refresh();

        $this->assertFalse($user->hasEnoughLoyaltyPoints(100));
    }

    // ==========================================
    // Add Loyalty Points Tests
    // ==========================================

    public function test_add_loyalty_points_creates_entry(): void
    {
        $user = User::factory()->create();

        $entry = $user->addLoyaltyPoints(100, 'Test points', 'manual');

        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'manual',
            'description' => 'Test points',
        ]);

        $user->refresh();
        $this->assertEquals(100, $user->loyalty_points_balance);
    }

    public function test_add_negative_points_for_redemption(): void
    {
        $user = User::factory()->create();

        // First add some points
        $user->addLoyaltyPoints(100, 'Initial points', 'booking');

        // Then redeem (subtract)
        $user->addLoyaltyPoints(-30, 'Reward redemption', 'redemption');

        $user->refresh();
        $this->assertEquals(70, $user->loyalty_points_balance);
    }

    public function test_add_loyalty_points_tracks_balance(): void
    {
        $user = User::factory()->create();

        $user->addLoyaltyPoints(100, 'First points', 'booking');
        $user->addLoyaltyPoints(50, 'Second points', 'referral');
        $user->addLoyaltyPoints(-20, 'Redemption', 'redemption');

        $user->refresh();
        $this->assertEquals(130, $user->loyalty_points_balance);
    }

    // ==========================================
    // Loyalty Point Scopes Tests
    // ==========================================

    public function test_expiring_soon_scope(): void
    {
        $user = User::factory()->create();

        // Expiring in 15 days (within 30 day window)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Expiring soon',
            'balance_after' => 100,
            'expires_at' => now()->addDays(15),
        ]);

        // Expiring in 45 days (outside 30 day window)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Future expiry',
            'balance_after' => 150,
            'expires_at' => now()->addDays(45),
        ]);

        // Already expired (should not be in scope)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 25,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Already expired',
            'balance_after' => 175,
            'expires_at' => now()->subDay(),
        ]);

        $expiringSoon = LoyaltyPoint::expiringSoon(30)->get();

        $this->assertCount(1, $expiringSoon);
        $this->assertEquals(100, $expiringSoon->first()->amount);
    }

    public function test_active_scope(): void
    {
        $user = User::factory()->create();

        // Active (no expiry)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'No expiry',
            'balance_after' => 100,
            'expires_at' => null,
        ]);

        // Active (future expiry)
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Future expiry',
            'balance_after' => 150,
            'expires_at' => now()->addMonth(),
        ]);

        // Expired
        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 25,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Expired',
            'balance_after' => 175,
            'expires_at' => now()->subDay(),
        ]);

        $activePoints = LoyaltyPoint::active()->get();

        $this->assertCount(2, $activePoints);
    }

    // ==========================================
    // Loyalty Reward Availability Tests
    // ==========================================

    public function test_reward_is_available_when_active(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        $this->assertTrue($reward->is_available);
    }

    public function test_reward_not_available_when_inactive(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => false,
        ]);

        $this->assertFalse($reward->is_available);
    }

    public function test_reward_not_available_before_valid_from(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Future Reward',
            'description' => 'Future reward description',
            'points_cost' => 100,
            'is_active' => true,
            'valid_from' => now()->addDays(7),
        ]);

        $this->assertFalse($reward->is_available);
    }

    public function test_reward_not_available_after_valid_until(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Expired Reward',
            'description' => 'Expired reward description',
            'points_cost' => 100,
            'is_active' => true,
            'valid_until' => now()->subDay(),
        ]);

        $this->assertFalse($reward->is_available);
    }

    public function test_reward_not_available_when_max_redemptions_reached(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Limited Reward',
            'description' => 'Limited reward description',
            'points_cost' => 100,
            'is_active' => true,
            'max_redemptions' => 10,
            'current_redemptions' => 10,
        ]);

        $this->assertFalse($reward->is_available);
    }

    public function test_reward_available_when_redemptions_remaining(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Limited Reward',
            'description' => 'Limited reward description',
            'points_cost' => 100,
            'is_active' => true,
            'max_redemptions' => 10,
            'current_redemptions' => 5,
        ]);

        $this->assertTrue($reward->is_available);
    }

    // ==========================================
    // Redemptions Remaining Calculation Tests
    // ==========================================

    public function test_redemptions_remaining_calculation(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Limited Reward',
            'description' => 'Limited reward description',
            'points_cost' => 100,
            'is_active' => true,
            'max_redemptions' => 10,
            'current_redemptions' => 3,
        ]);

        $this->assertEquals(7, $reward->redemptions_remaining);
    }

    public function test_redemptions_remaining_zero_when_exhausted(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Exhausted Reward',
            'description' => 'Exhausted reward description',
            'points_cost' => 100,
            'is_active' => true,
            'max_redemptions' => 5,
            'current_redemptions' => 5,
        ]);

        $this->assertEquals(0, $reward->redemptions_remaining);
    }

    public function test_redemptions_remaining_null_when_unlimited(): void
    {
        $reward = LoyaltyReward::create([
            'name' => 'Unlimited Reward',
            'description' => 'Unlimited reward description',
            'points_cost' => 100,
            'is_active' => true,
            'max_redemptions' => null,
        ]);

        $this->assertNull($reward->redemptions_remaining);
    }

    // ==========================================
    // Can Be Redeemed By User Tests
    // ==========================================

    public function test_can_be_redeemed_by_user_with_enough_points(): void
    {
        $user = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 200,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Test points',
            'balance_after' => 200,
        ]);

        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        $user->refresh();

        $this->assertTrue($reward->canBeRedeemedBy($user));
    }

    public function test_cannot_be_redeemed_by_user_without_enough_points(): void
    {
        $user = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 50,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Test points',
            'balance_after' => 50,
        ]);

        $reward = LoyaltyReward::create([
            'name' => 'Expensive Reward',
            'description' => 'Expensive reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        $user->refresh();

        $this->assertFalse($reward->canBeRedeemedBy($user));
    }

    public function test_cannot_be_redeemed_when_reward_unavailable(): void
    {
        $user = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 200,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Test points',
            'balance_after' => 200,
        ]);

        $reward = LoyaltyReward::create([
            'name' => 'Inactive Reward',
            'description' => 'Inactive reward description',
            'points_cost' => 100,
            'is_active' => false,
        ]);

        $user->refresh();

        $this->assertFalse($reward->canBeRedeemedBy($user));
    }

    // ==========================================
    // Loyalty Redemption Tests
    // ==========================================

    public function test_redemption_generates_unique_code(): void
    {
        $user = User::factory()->create();
        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        $redemption = LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'pending',
            'redeemed_at' => now(),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        $this->assertNotNull($redemption->redemption_code);
        $this->assertStringStartsWith('LYL-', $redemption->redemption_code);
        $this->assertEquals(12, strlen($redemption->redemption_code)); // LYL- + 8 chars
    }

    public function test_redemption_is_expired_when_past_expiry(): void
    {
        $user = User::factory()->create();
        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        $redemption = LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'approved',
            'redeemed_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        $this->assertTrue($redemption->isExpired());
    }

    public function test_redemption_not_expired_when_future_expiry(): void
    {
        $user = User::factory()->create();
        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        $redemption = LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'approved',
            'redeemed_at' => now(),
            'expires_at' => now()->addDays(7),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        $this->assertFalse($redemption->isExpired());
    }

    public function test_redemption_is_active(): void
    {
        $user = User::factory()->create();
        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        $activeRedemption = LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'approved',
            'redeemed_at' => now(),
            'expires_at' => now()->addDays(7),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        $usedRedemption = LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'used',
            'redeemed_at' => now()->subDays(5),
            'expires_at' => now()->addDays(7),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        $this->assertTrue($activeRedemption->isActive());
        $this->assertFalse($usedRedemption->isActive());
    }

    public function test_redemption_active_scope(): void
    {
        $user = User::factory()->create();
        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        // Active redemption
        LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'approved',
            'redeemed_at' => now(),
            'expires_at' => now()->addDays(7),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        // Used redemption
        LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'used',
            'redeemed_at' => now()->subDays(5),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        // Expired redemption
        LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'approved',
            'redeemed_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        $activeRedemptions = LoyaltyRedemption::active()->get();

        $this->assertCount(1, $activeRedemptions);
    }

    public function test_redemption_expiring_soon_scope(): void
    {
        $user = User::factory()->create();
        $reward = LoyaltyReward::create([
            'name' => 'Test Reward',
            'description' => 'Test reward description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        // Expiring in 3 days (within 7 day window)
        LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'approved',
            'redeemed_at' => now(),
            'expires_at' => now()->addDays(3),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        // Expiring in 14 days (outside 7 day window)
        LoyaltyRedemption::create([
            'user_id' => $user->id,
            'loyalty_reward_id' => $reward->id,
            'points_used' => 100,
            'status' => 'approved',
            'redeemed_at' => now(),
            'expires_at' => now()->addDays(14),
            'reward_snapshot' => ['name' => 'Test Reward', 'points_cost' => 100],
        ]);

        $expiringSoon = LoyaltyRedemption::expiringSoon(7)->get();

        $this->assertCount(1, $expiringSoon);
    }

    // ==========================================
    // Multi-User Points Isolation Tests
    // ==========================================

    public function test_points_are_isolated_per_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user1->id,
            'amount' => 100,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'User 1 points',
            'balance_after' => 100,
        ]);

        $this->createLoyaltyPoint([
            'user_id' => $user2->id,
            'amount' => 200,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'User 2 points',
            'balance_after' => 200,
        ]);

        $user1->refresh();
        $user2->refresh();

        $this->assertEquals(100, $user1->loyalty_points_balance);
        $this->assertEquals(200, $user2->loyalty_points_balance);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    public function test_handles_large_point_amounts(): void
    {
        $user = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 1000000,
            'type' => 'earned',
            'source' => 'promotion',
            'description' => 'Large bonus',
            'balance_after' => 1000000,
        ]);

        $user->refresh();

        $this->assertEquals(1000000, $user->loyalty_points_balance);
    }

    public function test_handles_decimal_point_amounts(): void
    {
        $user = User::factory()->create();

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 99.99,
            'type' => 'earned',
            'source' => 'booking',
            'description' => 'Decimal points 1',
            'balance_after' => 99.99,
        ]);

        $this->createLoyaltyPoint([
            'user_id' => $user->id,
            'amount' => 0.01,
            'type' => 'earned',
            'source' => 'bonus',
            'description' => 'Decimal points 2',
            'balance_after' => 100,
        ]);

        $user->refresh();

        $this->assertEquals(100, $user->loyalty_points_balance);
    }

    public function test_reward_available_scope(): void
    {
        // Available reward
        LoyaltyReward::create([
            'name' => 'Available Reward',
            'description' => 'Available description',
            'points_cost' => 100,
            'is_active' => true,
        ]);

        // Inactive reward
        LoyaltyReward::create([
            'name' => 'Inactive Reward',
            'description' => 'Inactive description',
            'points_cost' => 100,
            'is_active' => false,
        ]);

        // Future reward
        LoyaltyReward::create([
            'name' => 'Future Reward',
            'description' => 'Future description',
            'points_cost' => 100,
            'is_active' => true,
            'valid_from' => now()->addDays(7),
        ]);

        // Expired reward
        LoyaltyReward::create([
            'name' => 'Expired Reward',
            'description' => 'Expired description',
            'points_cost' => 100,
            'is_active' => true,
            'valid_until' => now()->subDay(),
        ]);

        // Exhausted reward
        LoyaltyReward::create([
            'name' => 'Exhausted Reward',
            'description' => 'Exhausted description',
            'points_cost' => 100,
            'is_active' => true,
            'max_redemptions' => 5,
            'current_redemptions' => 5,
        ]);

        $availableRewards = LoyaltyReward::available()->get();

        $this->assertCount(1, $availableRewards);
        $this->assertEquals('Available Reward', $availableRewards->first()->name);
    }
}
