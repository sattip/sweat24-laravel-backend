<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Security Headers Test
 *
 * Verifies that security headers middleware is working correctly.
 */
class SecurityHeadersTest extends TestCase
{
    /** @test */
    public function response_includes_x_content_type_options_header(): void
    {
        $response = $this->get('/api/v1/classes');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** @test */
    public function response_includes_x_frame_options_header(): void
    {
        $response = $this->get('/api/v1/classes');

        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    /** @test */
    public function response_includes_x_xss_protection_header(): void
    {
        $response = $this->get('/api/v1/classes');

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    /** @test */
    public function response_includes_referrer_policy_header(): void
    {
        $response = $this->get('/api/v1/classes');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /** @test */
    public function response_includes_permissions_policy_header(): void
    {
        $response = $this->get('/api/v1/classes');

        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }
}
