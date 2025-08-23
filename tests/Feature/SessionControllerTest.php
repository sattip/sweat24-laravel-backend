<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Session;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SessionControllerTest extends TestCase
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

    public function test_user_can_view_own_sessions()
    {
        Sanctum::actingAs($this->user);
        
        Session::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/v1/sessions');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_admin_can_view_all_sessions()
    {
        Sanctum::actingAs($this->admin);
        
        Session::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/sessions');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    public function test_admin_can_create_session()
    {
        Sanctum::actingAs($this->admin);

        $package = Package::create([
            'name' => 'Test Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Test',
            'price' => 100,
            'credits' => 10,
            'active' => true
        ]);

        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'sessions_remaining' => 10,
            'active' => true,
            'expires_at' => now()->addDays(30)
        ]);

        $response = $this->postJson('/api/v1/sessions', [
            'user_id' => $this->user->id,
            'user_package_id' => $userPackage->id,
            'session_date' => now()->format('Y-m-d'),
            'session_time' => '10:00:00',
            'duration' => 60,
            'status' => 'scheduled',
            'notes' => 'Test session'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'session_date',
                'session_time',
                'status'
            ]);
    }

    public function test_session_deduction_on_completion()
    {
        Sanctum::actingAs($this->admin);

        $package = Package::create([
            'name' => 'Test Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Test',
            'price' => 100,
            'credits' => 10,
            'active' => true
        ]);

        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'sessions_remaining' => 5,
            'active' => true,
            'expires_at' => now()->addDays(30)
        ]);

        $session = Session::create([
            'user_id' => $this->user->id,
            'user_package_id' => $userPackage->id,
            'session_date' => now(),
            'session_time' => '10:00:00',
            'duration' => 60,
            'status' => 'scheduled',
            'notes' => 'Test'
        ]);

        $response = $this->putJson("/api/sessions/{$session->id}", [
            'status' => 'completed'
        ]);

        $response->assertStatus(200);

        // Check session was deducted
        $userPackage->refresh();
        $this->assertEquals(4, $userPackage->sessions_remaining);
    }

    public function test_user_can_view_own_session_details()
    {
        Sanctum::actingAs($this->user);
        
        $session = Session::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $session->id,
                'user_id' => $this->user->id
            ]);
    }

    public function test_user_cannot_view_other_user_session()
    {
        Sanctum::actingAs($this->user);
        $otherUser = User::factory()->create();
        
        $session = Session::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson("/api/sessions/{$session->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_update_session()
    {
        Sanctum::actingAs($this->admin);
        
        $session = Session::factory()->create();

        $response = $this->putJson("/api/sessions/{$session->id}", [
            'status' => 'cancelled',
            'notes' => 'Cancelled due to weather'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'cancelled',
                'notes' => 'Cancelled due to weather'
            ]);
    }

    public function test_admin_can_delete_session()
    {
        Sanctum::actingAs($this->admin);
        
        $session = Session::factory()->create();

        $response = $this->deleteJson("/api/sessions/{$session->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('sessions', [
            'id' => $session->id
        ]);
    }
}