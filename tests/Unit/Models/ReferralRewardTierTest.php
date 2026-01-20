<?php

namespace Tests\Unit\Models;

use App\Models\ReferralRewardTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralRewardTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_referral_reward_tier(): void
    {
        $tier = ReferralRewardTier::factory()->create();
        $this->assertDatabaseHas('referral_reward_tiers', ['id' => $tier->id]);
    }

    public function test_active_state(): void
    {
        $tier = ReferralRewardTier::factory()->active()->create();
        $this->assertTrue($tier->is_active);
    }

    public function test_inactive_state(): void
    {
        $tier = ReferralRewardTier::factory()->inactive()->create();
        $this->assertFalse($tier->is_active);
    }

    public function test_bronze_state(): void
    {
        $tier = ReferralRewardTier::factory()->bronze()->create();

        $this->assertEquals('1η Σύσταση', $tier->name);
        $this->assertEquals(1, $tier->referrals_required);
        $this->assertEquals('discount', $tier->reward_type);
        $this->assertEquals(10.00, $tier->discount_percentage);
    }

    public function test_silver_state(): void
    {
        $tier = ReferralRewardTier::factory()->silver()->create();

        $this->assertEquals('3η Σύσταση', $tier->name);
        $this->assertEquals(3, $tier->referrals_required);
        $this->assertEquals(25.00, $tier->discount_percentage);
    }

    public function test_gold_state(): void
    {
        $tier = ReferralRewardTier::factory()->gold()->create();

        $this->assertEquals('5η Σύσταση', $tier->name);
        $this->assertEquals(5, $tier->referrals_required);
        $this->assertEquals('free_month', $tier->reward_type);
    }

    public function test_referrals_required_is_positive(): void
    {
        $tier = ReferralRewardTier::factory()->create();
        $this->assertGreaterThan(0, $tier->referrals_required);
    }

    public function test_reward_type_is_valid(): void
    {
        $tier = ReferralRewardTier::factory()->create();
        $validTypes = ['discount', 'free_month', 'personal_training', 'custom'];

        $this->assertContains($tier->reward_type, $validTypes);
    }

    public function test_has_validity_days(): void
    {
        $tier = ReferralRewardTier::factory()->create();
        $this->assertEquals(90, $tier->validity_days);
    }
}
