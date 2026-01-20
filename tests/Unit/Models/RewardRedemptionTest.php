<?php

namespace Tests\Unit\Models;

use App\Models\PointsReward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardRedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_reward_redemption(): void
    {
        $redemption = RewardRedemption::factory()->create();
        $this->assertDatabaseHas('reward_redemptions', ['id' => $redemption->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $redemption = RewardRedemption::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $redemption->user);
        $this->assertEquals($user->id, $redemption->user->id);
    }

    public function test_belongs_to_reward(): void
    {
        $reward = PointsReward::factory()->create();
        $redemption = RewardRedemption::factory()->create(['reward_id' => $reward->id]);

        $this->assertInstanceOf(PointsReward::class, $redemption->reward);
        $this->assertEquals($reward->id, $redemption->reward->id);
    }

    public function test_pending_state(): void
    {
        $redemption = RewardRedemption::factory()->pending()->create();
        $this->assertEquals('pending', $redemption->status);
    }

    public function test_approved_state(): void
    {
        $redemption = RewardRedemption::factory()->approved()->create();
        $this->assertEquals('active', $redemption->status);
    }

    public function test_used_state(): void
    {
        $redemption = RewardRedemption::factory()->used()->create();

        $this->assertEquals('used', $redemption->status);
        $this->assertNotNull($redemption->used_at);
    }

    public function test_expired_state(): void
    {
        $redemption = RewardRedemption::factory()->expired()->create();

        $this->assertEquals('expired', $redemption->status);
        $this->assertTrue($redemption->expires_at->isPast());
    }

    public function test_has_reward_code(): void
    {
        $redemption = RewardRedemption::factory()->create();
        $this->assertNotNull($redemption->reward_code);
        $this->assertMatchesRegularExpression('/^RWD[0-9A-Z]+$/', $redemption->reward_code);
    }
}
