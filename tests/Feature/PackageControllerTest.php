<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PackageControllerTest extends TestCase
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

    public function test_anyone_can_view_active_packages()
    {
        Package::factory()->count(3)->create(['status' => 'active']);
        Package::factory()->count(2)->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/packages');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_anyone_can_view_specific_package()
    {
        $package = Package::factory()->create();

        $response = $this->getJson("/api/packages/{$package->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $package->id,
                'name' => $package->name,
                'price' => $package->price
            ]);
    }

    public function test_admin_can_create_package()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Premium Package',
            'type' => 'unlimited',
            'duration' => 30,
            'description' => 'Unlimited access for 30 days',
            'price' => 150.00,
            'sessions' => null,
            'status' => 'active'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'type',
                'duration',
                'price',
                'credits',
                'active'
            ]);

        $this->assertDatabaseHas('packages', [
            'name' => 'Premium Package',
            'type' => 'unlimited'
        ]);
    }

    public function test_regular_user_cannot_create_package()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Unauthorized Package',
            'type' => 'sessions',
            'duration' => 30,
            'description' => 'Should fail',
            'price' => 100.00,
            'sessions' => 10,
            'status' => 'active'
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_package()
    {
        Sanctum::actingAs($this->admin);
        $package = Package::factory()->create();

        $response = $this->putJson("/api/packages/{$package->id}", [
            'name' => 'Updated Package Name',
            'price' => 200.00
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Package Name',
                'price' => 200.00
            ]);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'name' => 'Updated Package Name'
        ]);
    }

    public function test_admin_can_deactivate_package()
    {
        Sanctum::actingAs($this->admin);
        $package = Package::factory()->create(['status' => 'active']);

        $response = $this->putJson("/api/packages/{$package->id}", [
            'active' => false
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'active' => 0
            ]);
    }

    public function test_admin_can_delete_package()
    {
        Sanctum::actingAs($this->admin);
        $package = Package::factory()->create();

        $response = $this->deleteJson("/api/packages/{$package->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('packages', [
            'id' => $package->id
        ]);
    }

    public function test_user_can_purchase_package()
    {
        Sanctum::actingAs($this->user);
        $package = Package::factory()->create([
            'type' => 'sessions',
            'sessions' => 10,
            'duration' => 30,
            'status' => 'active'
        ]);

        $response = $this->postJson('/api/v1/user-packages', [
            'package_id' => $package->id
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'package_id',
                'sessions_remaining',
                'expires_at',
                'active'
            ]);

        $this->assertDatabaseHas('user_packages', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'sessions_remaining' => 10,
            'status' => 'active'
        ]);
    }

    public function test_user_cannot_purchase_inactive_package()
    {
        Sanctum::actingAs($this->user);
        $package = Package::factory()->create(['status' => 'inactive']);

        $response = $this->postJson('/api/v1/user-packages', [
            'package_id' => $package->id
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Package is not available'
            ]);
    }

    public function test_user_can_view_own_active_packages()
    {
        Sanctum::actingAs($this->user);
        
        $package = Package::factory()->create();
        UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'sessions_remaining' => 5,
            'status' => 'active',
            'expires_at' => now()->addDays(30)
        ]);

        $response = $this->getJson('/api/v1/user-packages');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'package_id',
                    'sessions_remaining',
                    'expires_at',
                    'active'
                ]
            ]);
    }

    public function test_expired_packages_are_marked_inactive()
    {
        Sanctum::actingAs($this->user);
        
        $package = Package::factory()->create();
        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'sessions_remaining' => 5,
            'status' => 'active',
            'expires_at' => now()->subDay() // Expired yesterday
        ]);

        $response = $this->getJson('/api/v1/user-packages');

        $response->assertStatus(200);
        
        $userPackage->refresh();
        $this->assertFalse($userPackage->active);
    }
}