<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\GymClass;
use App\Models\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class GymClassControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'member']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->instructor = Instructor::factory()->create();
    }

    public function test_anyone_can_view_all_classes()
    {
        GymClass::factory()->count(5)->create(['instructor' => $this->instructor->id]);

        $response = $this->getJson('/api/v1/classes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'type',
                    'instructor',
                    'date',
                    'time',
                    'duration',
                    'max_participants',
                    'current_participants',
                    'location',
                    'description',
                    'status'
                ]
            ]);
    }

    public function test_anyone_can_view_specific_class()
    {
        $class = GymClass::factory()->create(['instructor' => $this->instructor->id]);

        $response = $this->getJson("/api/classes/{$class->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $class->id,
                'name' => $class->name,
                'type' => $class->type
            ]);
    }

    public function test_admin_can_create_class()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/classes', [
            'name' => 'Morning Yoga',
            'type' => 'Yoga',
            'instructor' => $this->instructor->id,
            'date' => '2025-09-01',
            'time' => '09:00:00',
            'duration' => 60,
            'max_participants' => 20,
            'location' => 'Studio A',
            'description' => 'Relaxing morning yoga session',
            'status' => 'active'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'type',
                'instructor',
                'date',
                'time'
            ]);

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
            'date' => '2025-09-01',
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

        $response = $this->putJson("/api/classes/{$class->id}", [
            'name' => 'Updated Class Name',
            'max_participants' => 25
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Class Name',
                'max_participants' => 25
            ]);

        $this->assertDatabaseHas('gym_classes', [
            'id' => $class->id,
            'name' => 'Updated Class Name'
        ]);
    }

    public function test_update_class_handles_null_description()
    {
        Sanctum::actingAs($this->admin);
        $class = GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'description' => 'Original description'
        ]);

        $response = $this->putJson("/api/classes/{$class->id}", [
            'description' => null
        ]);

        $response->assertStatus(200);
        
        // Description should remain unchanged when null is sent
        $this->assertDatabaseHas('gym_classes', [
            'id' => $class->id,
            'description' => 'Original description'
        ]);
    }

    public function test_admin_can_delete_class()
    {
        Sanctum::actingAs($this->admin);
        $class = GymClass::factory()->create(['instructor' => $this->instructor->id]);

        $response = $this->deleteJson("/api/classes/{$class->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('gym_classes', [
            'id' => $class->id
        ]);
    }

    public function test_regular_user_cannot_delete_class()
    {
        Sanctum::actingAs($this->user);
        $class = GymClass::factory()->create(['instructor' => $this->instructor->id]);

        $response = $this->deleteJson("/api/classes/{$class->id}");

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

        $response = $this->getJson("/api/classes?date={$today}");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
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
        $response->assertJsonCount(1);
    }

    public function test_can_filter_classes_by_instructor()
    {
        $instructor2 = Instructor::factory()->create();

        GymClass::factory()->create(['instructor' => $this->instructor->id]);
        GymClass::factory()->create(['instructor' => $instructor2->id]);

        $response = $this->getJson("/api/classes?instructor={$this->instructor->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }
}