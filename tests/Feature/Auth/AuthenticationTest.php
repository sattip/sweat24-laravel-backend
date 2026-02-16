<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ========== LOGIN TESTS ==========

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'status',
                ],
                'token',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
    }

    public function test_login_returns_correct_user_data_structure(): void
    {
        $user = User::factory()->member()->active()->create([
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
            'phone' => '1234567890',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'role',
                    'status',
                    'registration_status',
                    'membership_type',
                    'join_date',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_pending_approval_user_cannot_login(): void
    {
        $user = User::factory()->pendingApproval()->create([
            'email' => 'pending@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pending@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ========== ADMIN LOGIN TESTS ==========

    public function test_admin_can_login_via_admin_endpoint(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'user',
                'token',
            ])
            ->assertJson([
                'success' => true,
                'user' => [
                    'role' => 'admin',
                ],
            ]);
    }

    public function test_trainer_can_login_via_admin_endpoint(): void
    {
        $trainer = User::factory()->trainer()->create([
            'email' => 'trainer@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/admin/login', [
            'email' => 'trainer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'role' => 'trainer',
                ],
            ]);
    }

    public function test_member_cannot_login_via_admin_endpoint(): void
    {
        $member = User::factory()->member()->active()->create([
            'email' => 'member@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/admin/login', [
            'email' => 'member@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_login_returns_token(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('token'));
    }

    // ========== LOGOUT TESTS ==========

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->active()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
    }

    public function test_token_is_revoked_after_logout(): void
    {
        $user = User::factory()->active()->create();
        $token = $user->createToken('test-token');

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        // Token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    // ========== PROFILE (/me) TESTS ==========

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->member()->active()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'role' => 'member',
                ],
            ]);
    }

    public function test_profile_returns_complete_user_data_structure(): void
    {
        $user = User::factory()->member()->active()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'date_of_birth',
                    'gender',
                    'weight',
                    'height',
                    'emergency_contact',
                    'emergency_phone',
                    'membership_type',
                    'role',
                    'status',
                    'registration_status',
                    'remaining_sessions',
                    'total_sessions',
                    'join_date',
                    'last_visit',
                    'medical_history',
                    'notes',
                    'has_signed_terms',
                    'terms_accepted_at',
                    'has_priority_booking',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    // ========== VALIDATION TESTS ==========

    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
