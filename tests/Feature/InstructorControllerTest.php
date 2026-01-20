<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class InstructorControllerTest extends TestCase
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

    public function test_anyone_can_view_all_instructors()
    {
        Instructor::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/instructors');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'specialties'
                ]
            ]);
    }

    public function test_anyone_can_view_specific_instructor()
    {
        $instructor = Instructor::factory()->create();

        $response = $this->getJson("/api/v1/instructors/{$instructor->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $instructor->id,
                'name' => $instructor->name,
                'email' => $instructor->email
            ]);
    }

    public function test_admin_can_create_instructor()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/instructors', [
            'name' => 'New Instructor',
            'email' => 'newinstructor@example.com',
            'phone' => '5551234567',
            'specialties' => ['CrossFit'],
            'bio' => 'Experienced CrossFit coach'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'instructor' => [
                    'id',
                    'name',
                    'email',
                    'specialties'
                ],
                'message'
            ]);

        $this->assertDatabaseHas('instructors', [
            'email' => 'newinstructor@example.com'
        ]);
    }

    public function test_regular_user_cannot_create_instructor()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/instructors', [
            'name' => 'Unauthorized Instructor',
            'email' => 'unauth@example.com',
            'phone' => '5551234567',
            'specialties' => ['Yoga']
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_instructor()
    {
        Sanctum::actingAs($this->admin);
        $instructor = Instructor::factory()->create();

        $response = $this->putJson("/api/v1/instructors/{$instructor->id}", [
            'name' => 'Updated Name',
            'specialties' => ['Pilates', 'Yoga']
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Name'
            ]);

        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_admin_can_delete_instructor()
    {
        Sanctum::actingAs($this->admin);
        $instructor = Instructor::factory()->create();

        $response = $this->deleteJson("/api/v1/instructors/{$instructor->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('instructors', [
            'id' => $instructor->id
        ]);
    }

    public function test_regular_user_cannot_delete_instructor()
    {
        Sanctum::actingAs($this->user);
        $instructor = Instructor::factory()->create();

        $response = $this->deleteJson("/api/v1/instructors/{$instructor->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id
        ]);
    }

    public function test_cannot_create_instructor_with_duplicate_email()
    {
        Sanctum::actingAs($this->admin);

        Instructor::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/instructors', [
            'name' => 'Duplicate Instructor',
            'email' => 'existing@example.com',
            'phone' => '5551234567',
            'specialties' => ['Yoga']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}