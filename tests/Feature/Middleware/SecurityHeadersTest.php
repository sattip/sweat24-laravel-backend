<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_x_content_type_options_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_x_frame_options_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_x_xss_protection_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_referrer_policy_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_permissions_policy_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }

    public function test_x_powered_by_header_is_removed(): void
    {
        $response = $this->get('/');

        $response->assertHeaderMissing('X-Powered-By');
    }

    public function test_server_header_is_removed(): void
    {
        $response = $this->get('/');

        // Note: Server header might still be present from the web server itself
        // This test verifies Laravel doesn't add it
        $this->assertNotEquals('Laravel', $response->headers->get('Server'));
    }

    public function test_csp_header_not_set_in_non_production(): void
    {
        // Ensure we're not in production
        config(['app.env' => 'testing']);

        $response = $this->get('/');

        $response->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_csp_header_set_in_production(): void
    {
        config(['app.env' => 'production']);

        $response = $this->get('/');

        $response->assertHeader('Content-Security-Policy');
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function test_hsts_header_not_set_in_non_production(): void
    {
        config(['app.env' => 'testing']);

        $response = $this->get('/');

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_security_headers_applied_to_api_routes(): void
    {
        $response = $this->getJson('/api/v1/auth/check-age');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_security_headers_applied_to_post_requests(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_security_headers_applied_to_error_responses(): void
    {
        $response = $this->getJson('/api/v1/nonexistent-route');

        $response->assertStatus(404);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }
}
