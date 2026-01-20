<?php

namespace Tests\Unit\Models;

use App\Models\ReferralReward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralRewardTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_referral_reward(): void
    {
        $user = User::factory()->create();

        $reward = ReferralReward::create([
            'user_id' => $user->id,
            'name' => 'Free Month Pass',
            'description' => 'Get a free month when you refer 5 friends',
            'type' => 'free_month',
            'value' => 50.00,
            'status' => 'available',
            'earned_at' => now(),
            'referrals_required' => 5,
        ]);

        $this->assertDatabaseHas('referral_rewards', [
            'name' => 'Free Month Pass',
            'type' => 'free_month',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_value_is_decimal(): void
    {
        $reward = ReferralReward::factory()->create([
            'value' => 25.75,
        ]);

        $this->assertEquals('25.75', $reward->value);
    }

    public function test_earned_at_is_datetime(): void
    {
        $reward = ReferralReward::factory()->create([
            'earned_at' => '2026-01-20 10:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $reward->earned_at);
    }

    public function test_expires_at_is_datetime(): void
    {
        $reward = ReferralReward::factory()->create([
            'expires_at' => '2026-12-31 23:59:59',
        ]);

        $this->assertInstanceOf(Carbon::class, $reward->expires_at);
    }

    public function test_redeemed_at_is_datetime(): void
    {
        $reward = ReferralReward::factory()->redeemed()->create();

        $this->assertInstanceOf(Carbon::class, $reward->redeemed_at);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $reward = ReferralReward::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('Test User', $reward->user->name);
    }

    // ==========================================
    // Status State Tests (matching migration enum values)
    // ==========================================

    public function test_available_state(): void
    {
        $reward = ReferralReward::factory()->available()->create();

        $this->assertEquals('available', $reward->status);
    }

    public function test_redeemed_state(): void
    {
        $reward = ReferralReward::factory()->redeemed()->create();

        $this->assertEquals('redeemed', $reward->status);
        $this->assertNotNull($reward->redeemed_at);
    }

    public function test_expired_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $reward = ReferralReward::factory()->expired()->create();

        $this->assertEquals('expired', $reward->status);
        $this->assertTrue($reward->expires_at->isPast());

        Carbon::setTestNow();
    }

    // ==========================================
    // Requirements Tests
    // ==========================================

    public function test_with_requirements(): void
    {
        $reward = ReferralReward::factory()->withRequirements(10)->create();

        $this->assertEquals(10, $reward->referrals_required);
    }

    // ==========================================
    // Type Tests (matching migration enum values)
    // ==========================================

    public function test_discount_type(): void
    {
        $reward = ReferralReward::factory()->discount(25.00)->create();

        $this->assertEquals('discount', $reward->type);
        $this->assertEquals('25.00', $reward->value);
    }

    public function test_free_month_type(): void
    {
        $reward = ReferralReward::factory()->freeMonth()->create();

        $this->assertEquals('free_month', $reward->type);
    }

    public function test_personal_training_type(): void
    {
        $reward = ReferralReward::factory()->personalTraining()->create();

        $this->assertEquals('personal_training', $reward->type);
    }

    public function test_custom_type(): void
    {
        $reward = ReferralReward::factory()->create(['type' => 'custom']);

        $this->assertEquals('custom', $reward->type);
    }
}
