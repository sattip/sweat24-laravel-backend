<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_admin_can_get_all_users()
    {
        User::factory()->count(5)->create();
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_get_users_list()
    {
        // Currently the API allows any authenticated user to access users list
        // This test verifies the current behavior
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/users');

        // The route requires authentication but doesn't have role restrictions
        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_get_users()
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_admin_can_view_specific_user()
    {
        Sanctum::actingAs($this->admin);
        $targetUser = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email
            ]);
    }

    public function test_user_can_view_own_profile()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/users/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email
            ]);
    }

    public function test_admin_can_create_user()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'phone' => '9876543210',
            'role' => 'member'
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com'
        ]);
    }

    public function test_user_can_update_own_profile()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/users/{$this->user->id}", [
            'name' => 'Updated Name',
            'phone' => '1111111111'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Name',
                'phone' => '1111111111'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_admin_can_delete_user()
    {
        Sanctum::actingAs($this->admin);
        $targetUser = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$targetUser->id}");

        // Controller returns 200 with JSON message
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User deleted successfully'
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id
        ]);
    }

    public function test_can_search_user_by_phone()
    {
        Sanctum::actingAs($this->admin);
        $targetUser = User::factory()->create(['phone' => '1234567890']);

        $response = $this->getJson('/api/v1/users/search/by-phone?phone=1234567890');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'phone']
            ]);
    }

    public function test_search_returns_null_for_nonexistent_phone()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/users/search/by-phone?phone=9999999999');

        $response->assertStatus(200)
            ->assertJson(['user' => null]);
    }

    public function test_admin_can_view_user_packages_via_user_packages_endpoint()
    {
        Sanctum::actingAs($this->admin);

        $package = Package::factory()->create();
        UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        // User packages are accessed via /api/v1/user-packages endpoint with user_id filter
        $response = $this->getJson("/api/v1/user-packages/user/{$this->user->id}");

        $response->assertStatus(200);
    }
}
