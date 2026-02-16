<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_debug_endpoints_removed(): void
    {
        $response = $this->getJson('/api/v1/debug/auth');
        $response->assertStatus(404);
    }

    public function test_public_test_endpoints_removed(): void
    {
        $response = $this->getJson('/api/v1/test/referral-tiers');
        $response->assertStatus(404);
    }

    public function test_public_booking_test_endpoints_removed(): void
    {
        $response = $this->getJson('/api/v1/bookings/test');
        $this->assertTrue(
            in_array($response->getStatusCode(), [401, 404, 405]),
            "Expected 401, 404 or 405, got {$response->getStatusCode()}"
        );
    }

    public function test_admin_points_rewards_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/admin/points/rewards');
        $response->assertStatus(401);
    }

    public function test_admin_points_rewards_requires_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/points/rewards');

        $response->assertStatus(403);
    }

    public function test_churn_feedback_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/churn-feedback/pending');
        $response->assertStatus(401);
    }

    public function test_wellness_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/wellness/today');
        $response->assertStatus(401);
    }

    public function test_user_model_mass_assignment_protection(): void
    {
        $user = new User([
            'role' => 'admin',
            'status' => 'active',
            'registration_status' => 'completed',
            'approved_at' => now(),
            'has_priority_booking' => true,
        ]);

        $this->assertNotEquals('admin', $user->role);
        $this->assertNotEquals('active', $user->status);
        $this->assertNotEquals('completed', $user->registration_status);
        $this->assertNull($user->approved_at);
        $this->assertNotTrue($user->has_priority_booking);
    }

    public function test_order_controller_rejects_origin_based_admin(): void
    {
        $response = $this->withHeader('Origin', 'http://localhost:5174')
            ->getJson('/api/v1/orders');

        $response->assertStatus(401);
    }
}
