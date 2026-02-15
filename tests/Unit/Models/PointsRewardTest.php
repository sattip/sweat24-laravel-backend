<?php

namespace Tests\Unit\Models;

use App\Models\PointsReward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsRewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_points_reward(): void
    {
        $reward = PointsReward::factory()->create();
        $this->assertDatabaseHas('points_rewards', ['id' => $reward->id]);
    }

    public function test_active_state(): void
    {
        $reward = PointsReward::factory()->active()->create();
        $this->assertTrue($reward->is_active);
    }

    public function test_inactive_state(): void
    {
        $reward = PointsReward::factory()->inactive()->create();
        $this->assertFalse($reward->is_active);
    }

    public function test_with_stock_state(): void
    {
        $reward = PointsReward::factory()->withStock(50)->create();
        $this->assertEquals(50, $reward->max_redemptions);
    }

    public function test_discount_state(): void
    {
        $reward = PointsReward::factory()->discount()->create();
        $this->assertEquals('discount', $reward->reward_type);
    }

    public function test_free_session_state(): void
    {
        $reward = PointsReward::factory()->freeSession()->create();

        $this->assertEquals('free_session', $reward->reward_type);
        $this->assertEquals('1 μάθημα', $reward->reward_value);
    }

    public function test_points_cost_is_positive(): void
    {
        $reward = PointsReward::factory()->create();
        $this->assertGreaterThan(0, $reward->points_cost);
    }

    public function test_reward_type_is_valid(): void
    {
        $reward = PointsReward::factory()->create();
        $validTypes = ['gift_card', 'free_session', 'product', 'discount', 'premium', 'merchandise'];

        $this->assertContains($reward->reward_type, $validTypes);
    }
}
