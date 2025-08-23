<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a default package for new users
        Package::create([
            'name' => 'Basic Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Basic package for testing',
            'price' => 50.00,
            'credits' => 10,
            'active' => true
        ]);
    }

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
            'date_of_birth' => '1990-01-01'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'created_at',
                    'updated_at'
                ],
                'token'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);
    }

    public function test_user_cannot_register_with_existing_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
            'date_of_birth' => '1990-01-01'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email'
                ],
                'token'
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);
    }

    public function test_unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}