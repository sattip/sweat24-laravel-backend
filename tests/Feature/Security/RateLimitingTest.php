<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Rate Limiting Test
 *
 * Verifies that rate limiting is properly configured for sensitive endpoints.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear rate limiters before each test
        RateLimiter::clear('auth');
        RateLimiter::clear('registration');
        RateLimiter::clear('password-reset');
    }

    /** @test */
    public function login_endpoint_is_rate_limited(): void
    {
        // Make 6 requests (limit is 5 per minute)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many login attempts. Please try again later.',
        ]);
    }

    /** @test */
    public function registration_endpoint_is_rate_limited(): void
    {
        // Make 6 requests (limit is 5 per minute)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/register', [
                'name' => 'Test User',
                'email' => "test{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test6@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many registration attempts. Please try again later.',
        ]);
    }

    /** @test */
    public function password_reset_endpoint_is_rate_limited(): void
    {
        // Make 4 requests (limit is 3 per minute)
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'test@example.com',
            ]);
        }

        // 4th request should be rate limited
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many password reset attempts. Please try again later.',
        ]);
    }

    /** @test */
    public function successful_login_is_still_allowed_within_limit(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }

    /** @test */
    public function admin_login_endpoint_is_rate_limited(): void
    {
        // Make 6 requests (limit is 5 per minute)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }
}
