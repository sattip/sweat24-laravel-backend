<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\GymClass;
use App\Models\Instructor;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class GymClassControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Instructor $instructor;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->instructor = Instructor::factory()->create();
        $this->store = Store::factory()->create();
    }

    public function test_anyone_can_view_all_classes()
    {
        GymClass::factory()->count(3)->create(['instructor' => $this->instructor->id]);

        $response = $this->getJson('/api/v1/classes');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    public function test_anyone_can_view_specific_class()
    {
        $class = GymClass::factory()->create(['instructor' => $this->instructor->id]);

        $response = $this->getJson("/api/v1/classes/{$class->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $class->id,
                'name' => $class->name
            ]);
    }

    public function test_admin_can_create_class()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/classes', [
            'name' => 'Morning Yoga',
            'type' => 'Yoga',
            'instructor' => $this->instructor->id,
            'store_id' => $this->store->id,
            'date' => now()->addDays(7)->format('Y-m-d'),
            'time' => '09:00:00',
            'duration' => 60,
            'max_participants' => 20,
            'location' => 'Studio A',
            'description' => 'Relaxing morning yoga session',
            'status' => 'active'
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('gym_classes', [
            'name' => 'Morning Yoga',
            'type' => 'Yoga'
        ]);
    }

    public function test_regular_user_cannot_create_class()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/classes', [
            'name' => 'Unauthorized Class',
            'type' => 'Yoga',
            'instructor' => $this->instructor->id,
            'store_id' => $this->store->id,
            'date' => now()->addDays(7)->format('Y-m-d'),
            'time' => '09:00:00',
            'duration' => 60,
            'max_participants' => 20,
            'location' => 'Studio A',
            'description' => 'This should fail',
            'status' => 'active'
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_class()
    {
        Sanctum::actingAs($this->admin);
        $class = GymClass::factory()->create(['instructor' => $this->instructor->id]);

        $response = $this->putJson("/api/v1/classes/{$class->id}", [
            'name' => 'Updated Class Name',
            'max_participants' => 25
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gym_classes', [
            'id' => $class->id,
            'name' => 'Updated Class Name'
        ]);
    }

    public function test_admin_can_delete_class()
    {
        Sanctum::actingAs($this->admin);
        $class = GymClass::factory()->create(['instructor' => $this->instructor->id]);

        $response = $this->deleteJson("/api/v1/classes/{$class->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('gym_classes', [
            'id' => $class->id
        ]);
    }

    public function test_regular_user_cannot_delete_class()
    {
        Sanctum::actingAs($this->user);
        $class = GymClass::factory()->create(['instructor' => $this->instructor->id]);

        $response = $this->deleteJson("/api/v1/classes/{$class->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('gym_classes', [
            'id' => $class->id
        ]);
    }

    public function test_can_filter_classes_by_date()
    {
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => $today
        ]);
        GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => $tomorrow
        ]);

        $response = $this->getJson("/api/v1/classes?date={$today}");

        $response->assertStatus(200);
    }

    public function test_can_filter_classes_by_type()
    {
        GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'type' => 'Yoga'
        ]);
        GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'type' => 'Pilates'
        ]);

        $response = $this->getJson('/api/v1/classes?type=Yoga');

        $response->assertStatus(200);
    }
}
