<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
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

    public function test_anyone_can_view_all_packages()
    {
        Package::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/packages');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($response->json()));
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
            'description' => 'Unlimited access for 30 days',
            'price' => 150.00,
            'sessions' => 20,
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'duration',
                'price',
            ]);

        $this->assertDatabaseHas('packages', [
            'name' => 'Premium Package',
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
            'status' => 'active',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_package()
    {
        Sanctum::actingAs($this->admin);
        $package = Package::factory()->create();

        $response = $this->putJson("/api/v1/packages/{$package->id}", [
            'name' => 'Updated Package Name',
            'price' => 200.00,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'name' => 'Updated Package Name',
        ]);
    }

    public function test_admin_can_deactivate_package()
    {
        Sanctum::actingAs($this->admin);
        $package = Package::factory()->create(['status' => 'active']);

        $response = $this->putJson("/api/v1/packages/{$package->id}", [
            'status' => 'inactive',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_package()
    {
        Sanctum::actingAs($this->admin);
        $package = Package::factory()->create();

        $response = $this->deleteJson("/api/v1/packages/{$package->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('packages', [
            'id' => $package->id,
        ]);
    }

    public function test_regular_user_cannot_delete_package()
    {
        Sanctum::actingAs($this->user);
        $package = Package::factory()->create();

        $response = $this->deleteJson("/api/v1/packages/{$package->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_package()
    {
        $response = $this->postJson('/api/v1/packages', [
            'name' => 'Unauthenticated Package',
            'duration' => 30,
            'price' => 100.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_package_has_correct_structure()
    {
        $package = Package::factory()->create();

        $response = $this->getJson("/api/v1/packages/{$package->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'price',
                'sessions',
                'duration',
                'status',
            ]);
    }
}
