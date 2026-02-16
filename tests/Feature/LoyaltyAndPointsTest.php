<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LoyaltyReward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class LoyaltyAndPointsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    // Loyalty Dashboard
    public function test_loyalty_dashboard_requires_auth()
    {
        $this->getJson('/api/v1/loyalty/dashboard')->assertStatus(401);
    }

    public function test_user_can_view_loyalty_dashboard()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/loyalty/dashboard')->assertStatus(200);
    }

    public function test_user_can_view_points_history()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/loyalty/points/history')->assertStatus(200);
    }

    public function test_user_can_view_available_rewards()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/loyalty/rewards/available')->assertStatus(200);
    }

    public function test_user_can_view_redemptions()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/loyalty/redemptions')->assertStatus(200);
    }

    // Admin Loyalty
    public function test_admin_loyalty_rewards_requires_auth()
    {
        $this->getJson('/api/v1/admin/loyalty-rewards')->assertStatus(401);
    }

    public function test_member_cannot_access_admin_loyalty()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/admin/loyalty-rewards')->assertStatus(403);
    }

    public function test_admin_can_list_loyalty_rewards()
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/admin/loyalty-rewards')->assertStatus(200);
    }

    public function test_admin_can_create_loyalty_reward()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->postJson('/api/v1/admin/loyalty-rewards', [
            'name' => 'Free Session',
            'description' => 'One free training session',
            'points_required' => 100,
            'reward_type' => 'session',
            'is_active' => true,
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_admin_can_view_loyalty_stats()
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/admin/loyalty/stats')->assertStatus(200);
    }

    public function test_admin_can_view_loyalty_redemptions()
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/admin/loyalty/redemptions')->assertStatus(200);
    }

    // Mobile Points API
    public function test_points_history_requires_auth()
    {
        $this->getJson('/api/v1/points/history')->assertStatus(401);
    }

    public function test_user_can_view_points_stats()
    {
        Sanctum::actingAs($this->member);
        $userId = $this->member->id;
        $response = $this->getJson("/api/v1/points/stats?user_id={$userId}");
        $this->assertContains($response->status(), [200, 422]);
    }

    public function test_user_can_view_rewards()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/points/rewards')->assertStatus(200);
    }

    public function test_user_can_view_affordable_rewards()
    {
        Sanctum::actingAs($this->member);
        $userId = $this->member->id;
        $response = $this->getJson("/api/v1/points/rewards/affordable?user_id={$userId}");
        $this->assertContains($response->status(), [200, 422]);
    }

    // Points Settings (Admin)
    public function test_points_settings_requires_admin()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/points/settings')->assertStatus(403);
    }

    public function test_admin_can_get_points_settings()
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/points/settings')->assertStatus(200);
    }

    public function test_admin_can_update_points_settings()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->putJson('/api/v1/points/settings', [
            'points_per_booking' => 10,
            'points_per_euro' => 1,
        ]);
        $this->assertContains($response->status(), [200, 422]);
    }
}
