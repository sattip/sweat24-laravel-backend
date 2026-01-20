<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Debug Endpoints Removed Test
 *
 * Verifies that debug endpoints have been removed for security.
 */
class DebugEndpointsRemovedTest extends TestCase
{
    /** @test */
    public function debug_auth_endpoint_is_not_accessible(): void
    {
        $response = $this->get('/api/v1/debug/auth');

        $response->assertStatus(404);
    }

    /** @test */
    public function debug_admin_requests_endpoint_is_not_accessible(): void
    {
        $response = $this->get('/api/debug/admin-requests');

        $response->assertStatus(404);
    }

    /** @test */
    public function debug_pusher_endpoint_is_not_accessible(): void
    {
        $response = $this->get('/api/v1/debug/pusher');

        $response->assertStatus(404);
    }

    /** @test */
    public function public_test_referral_tiers_endpoint_is_not_accessible(): void
    {
        $response = $this->get('/api/v1/test/referral-tiers');

        $response->assertStatus(404);
    }

    /** @test */
    public function public_test_loyalty_rewards_endpoint_is_not_accessible(): void
    {
        $response = $this->get('/api/v1/test/loyalty-rewards');

        $response->assertStatus(404);
    }

    /** @test */
    public function public_test_admin_auth_endpoint_is_not_accessible(): void
    {
        $response = $this->get('/api/v1/test/admin-auth');

        $response->assertStatus(404);
    }

    /** @test */
    public function test_history_endpoint_is_not_accessible(): void
    {
        $response = $this->get('/api/test-history');

        $response->assertStatus(404);
    }

    /** @test */
    public function referrals_test_dashboard_endpoint_is_not_accessible(): void
    {
        $response = $this->get('/api/v1/referrals/test-dashboard/1');

        $response->assertStatus(404);
    }

    /** @test */
    public function simple_booking_test_endpoint_is_not_accessible(): void
    {
        $response = $this->post('/api/v1/bookings/simple', []);

        // Should return 404 (not found) or 405 (method not allowed) - either means the endpoint is not accessible
        $this->assertTrue(
            in_array($response->status(), [404, 405]),
            "Expected 404 or 405, got {$response->status()}"
        );
    }
}
