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
        $this->instructor = Instructor::create([
            'name' => 'Test Instructor',
            'email' => 'instructor@test.com',
            'phone' => '1234567890',
            'specialties' => ['Yoga'],
            'hourly_rate' => 20,
            'contract_type' => 'hourly',
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_anyone_can_view_classes()
    {
        GymClass::factory()->count(3)->create([
            'instructor' => $this->instructor->id,
            'date' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/classes');

        $response->assertStatus(200);
    }

    public function test_anyone_can_view_specific_class()
    {
        $class = GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => now()->addDay()->toDateString(),
        ]);

        $response = $this->getJson("/api/v1/classes/{$class->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $class->id,
                'name' => $class->name,
            ]);
    }

    public function test_authenticated_user_can_create_class()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/classes', [
            'name' => 'Morning Yoga',
            'type' => 'Yoga',
            'instructor' => $this->instructor->id,
            'date' => now()->addDays(5)->toDateString(),
            'time' => '09:00',
            'duration' => 60,
            'max_participants' => 20,
            'location' => 'Studio A',
            'description' => 'Relaxing morning yoga session',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'type',
                'instructor',
                'date',
                'time',
            ]);

        $this->assertDatabaseHas('gym_classes', [
            'name' => 'Morning Yoga',
            'type' => 'Yoga',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_class()
    {
        $response = $this->postJson('/api/v1/classes', [
            'name' => 'Unauthorized Class',
            'type' => 'Yoga',
            'instructor' => $this->instructor->id,
            'date' => now()->addDays(5)->toDateString(),
            'time' => '09:00',
            'duration' => 60,
            'max_participants' => 20,
            'location' => 'Studio A',
            'description' => 'This should fail',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_class()
    {
        Sanctum::actingAs($this->admin);
        $class = GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => now()->addDays(3)->toDateString(),
        ]);

        $response = $this->putJson("/api/v1/classes/{$class->id}", [
            'name' => 'Updated Class Name',
            'max_participants' => 25,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gym_classes', [
            'id' => $class->id,
            'name' => 'Updated Class Name',
        ]);
    }

    public function test_authenticated_user_can_delete_class()
    {
        Sanctum::actingAs($this->admin);
        $class = GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => now()->addDays(3)->toDateString(),
        ]);

        $response = $this->deleteJson("/api/v1/classes/{$class->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('gym_classes', [
            'id' => $class->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_class()
    {
        $class = GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => now()->addDays(3)->toDateString(),
        ]);

        $response = $this->deleteJson("/api/v1/classes/{$class->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('gym_classes', [
            'id' => $class->id,
        ]);
    }

    public function test_can_filter_classes_by_date()
    {
        $tomorrow = now()->addDay()->toDateString();
        $dayAfter = now()->addDays(2)->toDateString();

        GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => $tomorrow,
        ]);
        GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => $dayAfter,
        ]);

        $response = $this->getJson("/api/v1/classes?date={$tomorrow}");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json()));
    }

    public function test_can_filter_classes_by_instructor()
    {
        $instructor2 = Instructor::create([
            'name' => 'Second Instructor',
            'email' => 'instructor2@test.com',
            'phone' => '9876543210',
            'specialties' => ['Pilates'],
            'hourly_rate' => 25,
            'contract_type' => 'hourly',
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        GymClass::factory()->create([
            'instructor' => $this->instructor->id,
            'date' => now()->addDays(2)->toDateString(),
        ]);
        GymClass::factory()->create([
            'instructor' => $instructor2->id,
            'date' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->getJson("/api/v1/classes?instructor={$this->instructor->id}");

        $response->assertStatus(200);
    }
}
