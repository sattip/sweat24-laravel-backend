<?php

namespace Tests\Unit\Models;

use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\UserPoints;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user_points(): void
    {
        $userPoints = UserPoints::factory()->create();

        $this->assertDatabaseHas('users_points', [
            'id' => $userPoints->id,
            'user_id' => $userPoints->user_id,
        ]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $userPoints = UserPoints::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $userPoints->user);
        $this->assertEquals($user->id, $userPoints->user->id);
    }

    public function test_has_many_transactions(): void
    {
        $user = User::factory()->create();
        $userPoints = UserPoints::factory()->create(['user_id' => $user->id]);
        PointsTransaction::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $userPoints->transactions);
        $this->assertInstanceOf(PointsTransaction::class, $userPoints->transactions->first());
    }

    public function test_can_afford_returns_true_when_balance_sufficient(): void
    {
        $userPoints = UserPoints::factory()->create(['points_balance' => 100]);

        $this->assertTrue($userPoints->canAfford(50));
        $this->assertTrue($userPoints->canAfford(100));
    }

    public function test_can_afford_returns_false_when_balance_insufficient(): void
    {
        $userPoints = UserPoints::factory()->create(['points_balance' => 50]);

        $this->assertFalse($userPoints->canAfford(100));
    }

    public function test_calculate_rank(): void
    {
        // Create several user points records with different totals
        UserPoints::factory()->create(['total_earned' => 500]);
        UserPoints::factory()->create(['total_earned' => 1000]);
        $userPoints = UserPoints::factory()->create(['total_earned' => 750]);

        // With 750 total earned, there's 1 user with more (1000), so rank = 2
        $this->assertEquals(2, $userPoints->calculateRank());
    }

    public function test_integer_casts(): void
    {
        $userPoints = UserPoints::factory()->create([
            'points_balance' => 100,
            'total_earned' => 500,
            'total_spent' => 400,
        ]);

        $this->assertIsInt($userPoints->points_balance);
        $this->assertIsInt($userPoints->total_earned);
        $this->assertIsInt($userPoints->total_spent);
    }

    public function test_with_balance_state(): void
    {
        $userPoints = UserPoints::factory()->withBalance(500)->create();

        $this->assertEquals(500, $userPoints->points_balance);
    }

    public function test_no_points_state(): void
    {
        $userPoints = UserPoints::factory()->noPoints()->create();

        $this->assertEquals(0, $userPoints->points_balance);
        $this->assertEquals(0, $userPoints->total_earned);
        $this->assertEquals(0, $userPoints->total_spent);
    }

    public function test_high_rank_state(): void
    {
        $userPoints = UserPoints::factory()->highRank()->create();

        $this->assertGreaterThanOrEqual(5000, $userPoints->points_balance);
        $this->assertNotNull($userPoints->lifetime_rank);
        $this->assertLessThanOrEqual(10, $userPoints->lifetime_rank);
    }

    public function test_uses_correct_table(): void
    {
        $userPoints = new UserPoints();

        $this->assertEquals('users_points', $userPoints->getTable());
    }
}
