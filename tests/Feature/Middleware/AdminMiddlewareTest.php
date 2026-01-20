<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // The admin middleware checks membership_type === 'Admin'
    // It's used on routes like /admin/users/{id}/approve

    public function test_admin_membership_type_can_access(): void
    {
        $admin = User::factory()->create([
            'membership_type' => 'Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);
        Sanctum::actingAs($admin);

        // Create a pending user to approve
        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/admin/users/{$pendingUser->id}/approve");

        // Should not be 401 or 403 (might be 200 or other success code)
        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    public function test_non_admin_membership_type_returns_403(): void
    {
        $user = User::factory()->create([
            'membership_type' => 'Basic',
            'role' => 'member',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        // Create a pending user
        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/admin/users/{$pendingUser->id}/approve");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);
    }

    public function test_trainer_membership_type_returns_403(): void
    {
        $trainer = User::factory()->trainer()->create([
            'membership_type' => 'Trainer', // Not 'Admin'
        ]);
        Sanctum::actingAs($trainer);

        // Create a pending user
        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/admin/users/{$pendingUser->id}/approve");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        // Create a pending user
        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/admin/users/{$pendingUser->id}/approve");

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    // ========== ADDITIONAL ADMIN MIDDLEWARE ROUTE TESTS ==========

    public function test_admin_can_access_user_full_profile(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->member()->create();

        $response = $this->getJson("/api/admin/users/{$user->id}/full-profile");

        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    public function test_member_cannot_access_user_full_profile(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $otherUser = User::factory()->member()->create();

        $response = $this->getJson("/api/admin/users/{$otherUser->id}/full-profile");

        $response->assertStatus(403);
    }

    public function test_admin_can_reject_user(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/admin/users/{$pendingUser->id}/reject", [
            'reason' => 'Test rejection reason',
        ]);

        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(403, $response->status());
    }

    public function test_member_cannot_reject_user(): void
    {
        $member = User::factory()->member()->create();
        Sanctum::actingAs($member);

        $pendingUser = User::factory()->pendingApproval()->create();

        $response = $this->postJson("/api/admin/users/{$pendingUser->id}/reject", [
            'reason' => 'Test rejection reason',
        ]);

        $response->assertStatus(403);
    }
}
