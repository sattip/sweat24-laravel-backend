<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // ========== UNAUTHENTICATED REQUEST TESTS ==========

    public function test_unauthenticated_request_returns_401(): void
    {
        // Access a route protected by role middleware (packages management)
        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Test Package',
        ]);

        $response->assertStatus(401);
    }

    // ========== ADMIN ROLE TESTS ==========

    public function test_admin_can_access_admin_protected_routes(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        // Access admin analytics dashboard
        $response = $this->getJson('/api/v1/admin/analytics/dashboard');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_admin_trainer_routes(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        // Access a route that allows both admin and trainer
        $response = $this->getJson('/api/v1/admin/booking-requests');

        $response->assertStatus(200);
    }

    // ========== TRAINER ROLE TESTS ==========

    public function test_trainer_can_access_trainer_protected_routes(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        // Access a route that allows trainers
        $response = $this->getJson('/api/v1/admin/booking-requests');

        $response->assertStatus(200);
    }

    public function test_trainer_cannot_access_admin_only_routes(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        // Access admin-only analytics dashboard
        $response = $this->getJson('/api/v1/admin/analytics/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);
    }

    // ========== MEMBER ROLE TESTS ==========

    public function test_member_cannot_access_admin_routes(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        // Try to access admin-only route
        $response = $this->getJson('/api/v1/admin/analytics/dashboard');

        $response->assertStatus(403);
    }

    public function test_member_cannot_access_trainer_routes(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        // Try to access admin/trainer route
        $response = $this->getJson('/api/v1/admin/booking-requests');

        $response->assertStatus(403);
    }

    public function test_member_can_access_authenticated_routes(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        // Access authenticated user profile
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
    }

    // ========== MULTIPLE ROLES TESTS ==========

    public function test_route_allowing_multiple_roles_works_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        // Route allows admin,trainer
        $response = $this->getJson('/api/v1/admin/booking-requests');

        $response->assertStatus(200);
    }

    public function test_route_allowing_multiple_roles_works_for_trainer(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        // Route allows admin,trainer
        $response = $this->getJson('/api/v1/admin/booking-requests');

        $response->assertStatus(200);
    }

    public function test_route_allowing_multiple_roles_rejects_member(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        // Route allows admin,trainer but not member
        $response = $this->getJson('/api/v1/admin/booking-requests');

        $response->assertStatus(403);
    }

    // ========== PACKAGE MANAGEMENT ROLE TESTS ==========

    public function test_admin_can_create_packages(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Test Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Test description',
            'price' => 100.00,
            'credits' => 10,
            'active' => true,
        ]);

        // Should be allowed (201 or 200 depending on implementation)
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_trainer_can_create_packages(): void
    {
        $trainer = User::factory()->trainer()->create();
        Sanctum::actingAs($trainer);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Test Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Test description',
            'price' => 100.00,
            'credits' => 10,
            'active' => true,
        ]);

        // Should be allowed (201 or 200 depending on implementation)
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(401, $response->status());
    }

    public function test_member_cannot_create_packages(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Test Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Test description',
            'price' => 100.00,
            'credits' => 10,
            'active' => true,
        ]);

        $response->assertStatus(403);
    }
}
