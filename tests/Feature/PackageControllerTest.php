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

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_anyone_can_view_all_packages()
    {
        Package::factory()->count(3)->create(['status' => 'active']);
        Package::factory()->count(2)->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/packages');

        $response->assertStatus(200);
        // The index returns all packages, not just active ones
        $this->assertCount(5, $response->json());
    }

    public function test_anyone_can_view_specific_package()
    {
        $package = Package::factory()->create();

        $response = $this->getJson("/api/v1/packages/{$package->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $package->id,
                'name' => $package->name,
            ]);
    }

    public function test_admin_can_create_package()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Premium Package',
            'duration' => 30,
            'description' => 'Premium access for 30 days',
            'price' => 150.00,
            'sessions' => 10,
            'status' => 'active'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'duration',
                'price',
                'sessions',
                'status'
            ]);

        $this->assertDatabaseHas('packages', [
            'name' => 'Premium Package',
            'duration' => 30
        ]);
    }

    public function test_regular_user_cannot_create_package()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Unauthorized Package',
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

        $response = $this->putJson("/api/v1/packages/{$package->id}", [
            'name' => 'Updated Package Name',
            'price' => 200.00
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Package Name',
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

        $response = $this->putJson("/api/v1/packages/{$package->id}", [
            'status' => 'inactive'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'inactive'
            ]);
    }

    public function test_admin_can_delete_package()
    {
        Sanctum::actingAs($this->admin);
        $package = Package::factory()->create();

        $response = $this->deleteJson("/api/v1/packages/{$package->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Package deleted successfully'
            ]);

        $this->assertDatabaseMissing('packages', [
            'id' => $package->id
        ]);
    }

    public function test_user_can_view_own_packages()
    {
        Sanctum::actingAs($this->user);

        $package = Package::factory()->create();
        UserPackage::factory()->create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'remaining_sessions' => 5,
            'total_sessions' => 10,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/user-packages?user_id=' . $this->user->id);

        $response->assertStatus(200);
    }

    public function test_admin_can_assign_package_to_user()
    {
        Sanctum::actingAs($this->admin);

        $package = Package::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/user-packages', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_packages', [
            'user_id' => $this->user->id,
            'package_id' => $package->id,
        ]);
    }
}
