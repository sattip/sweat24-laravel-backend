<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_authenticated_user_can_get_users()
    {
        User::factory()->count(3)->create();
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/users');

        // Returns paginated response
        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_unauthenticated_user_cannot_get_users()
    {
        $response = $this->getJson('/api/v1/users');
        $response->assertStatus(401);
    }

    public function test_can_view_specific_user()
    {
        Sanctum::actingAs($this->admin);
        $targetUser = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
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
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_can_update_user()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/users/{$this->user->id}", [
            'name' => 'Updated Name',
            'phone' => '1111111111',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_user()
    {
        Sanctum::actingAs($this->admin);
        $targetUser = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$targetUser->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_cannot_create_user_with_duplicate_email()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Duplicate User',
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_search_users_by_name()
    {
        Sanctum::actingAs($this->admin);
        User::factory()->create(['name' => 'Unique Test Name']);

        $response = $this->getJson('/api/v1/users?search=Unique Test Name');

        $response->assertStatus(200);
    }

    public function test_cannot_update_user_unauthenticated()
    {
        $response = $this->putJson("/api/v1/users/{$this->user->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_show_returns_packages()
    {
        Sanctum::actingAs($this->admin);
        $targetUser = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'packages',
            ]);
    }
}
