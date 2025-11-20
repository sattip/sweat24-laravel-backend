<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Booking;
use App\Models\WorkoutMuscleGroup;
use Laravel\Sanctum\Sanctum;

class WorkoutMuscleGroupTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Create test booking
        $this->booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'attended' => 1,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function can_store_muscle_groups_for_attended_workout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
            'muscle_groups' => ['legs', 'core']
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Muscle groups saved successfully',
                'data' => [
                    'booking_id' => $this->booking->id,
                    'user_id' => $this->user->id,
                    'muscle_groups' => ['legs', 'core']
                ]
            ]);

        $this->assertDatabaseHas('workout_muscle_groups', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function can_store_single_muscle_group()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
            'muscle_groups' => ['total_body']
        ]);

        $response->assertStatus(200);
        
        $muscleGroup = WorkoutMuscleGroup::where('booking_id', $this->booking->id)->first();
        $this->assertEquals(['total_body'], $muscleGroup->muscle_groups);
    }

    /** @test */
    public function can_update_existing_muscle_groups()
    {
        Sanctum::actingAs($this->user);

        // First store
        $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
            'muscle_groups' => ['legs']
        ]);

        // Update
        $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
            'muscle_groups' => ['chest', 'back']
        ]);

        $response->assertStatus(200);
        
        $muscleGroup = WorkoutMuscleGroup::where('booking_id', $this->booking->id)->first();
        $this->assertEquals(['chest', 'back'], $muscleGroup->muscle_groups);
        
        // Should still only have one record (update, not insert)
        $this->assertEquals(1, WorkoutMuscleGroup::where('booking_id', $this->booking->id)->count());
    }

    /** @test */
    public function cannot_store_muscle_groups_for_non_attended_workout()
    {
        Sanctum::actingAs($this->user);

        $nonAttendedBooking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'attended' => 0,
        ]);

        $response = $this->postJson("/api/v1/workouts/{$nonAttendedBooking->id}/muscle-groups", [
            'muscle_groups' => ['legs']
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot record muscle groups for a workout you did not attend'
            ]);
    }

    /** @test */
    public function cannot_store_muscle_groups_for_other_users_booking()
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
            'muscle_groups' => ['legs']
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access to this booking'
            ]);
    }

    /** @test */
    public function validates_empty_muscle_groups_array()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
            'muscle_groups' => []
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['muscle_groups']);
    }

    /** @test */
    public function validates_invalid_muscle_group_value()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
            'muscle_groups' => ['invalid_group']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['muscle_groups.0']);
    }

    /** @test */
    public function validates_missing_muscle_groups_field()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['muscle_groups']);
    }

    /** @test */
    public function validates_muscle_groups_must_be_array()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
            'muscle_groups' => 'legs'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['muscle_groups']);
    }

    /** @test */
    public function can_retrieve_muscle_groups_for_workout()
    {
        Sanctum::actingAs($this->user);

        // Store muscle groups first
        WorkoutMuscleGroup::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->user->id,
            'muscle_groups' => ['legs', 'core']
        ]);

        $response = $this->getJson("/api/v1/workouts/{$this->booking->id}/muscle-groups");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'booking_id' => $this->booking->id,
                    'user_id' => $this->user->id,
                    'muscle_groups' => ['legs', 'core']
                ]
            ]);
    }

    /** @test */
    public function returns_404_when_no_muscle_groups_recorded()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workouts/{$this->booking->id}/muscle-groups");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No muscle groups recorded for this workout'
            ]);
    }

    /** @test */
    public function cannot_retrieve_muscle_groups_for_other_users_booking()
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/workouts/{$this->booking->id}/muscle-groups");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access to this booking'
            ]);
    }

    /** @test */
    public function test_history_includes_muscle_groups()
    {
        // Create past booking with muscle groups
        $pastBooking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'attended' => 1,
            'date' => now()->subDays(2),
            'time' => '10:00',
            'status' => 'confirmed',
        ]);

        WorkoutMuscleGroup::create([
            'booking_id' => $pastBooking->id,
            'user_id' => $this->user->id,
            'muscle_groups' => ['legs', 'core']
        ]);

        // Create another past booking without muscle groups
        $pastBookingNoGroups = Booking::factory()->create([
            'user_id' => $this->user->id,
            'attended' => 1,
            'date' => now()->subDays(3),
            'time' => '10:00',
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/test-history?user_id={$this->user->id}");

        $response->assertStatus(200);
        
        $bookings = $response->json();
        
        // Find the booking with muscle groups
        $bookingWithGroups = collect($bookings)->firstWhere('id', $pastBooking->id);
        $this->assertEquals(['legs', 'core'], $bookingWithGroups['muscle_groups']);
        $this->assertTrue($bookingWithGroups['muscle_groups_recorded']);
        
        // Find the booking without muscle groups
        $bookingWithoutGroups = collect($bookings)->firstWhere('id', $pastBookingNoGroups->id);
        $this->assertNull($bookingWithoutGroups['muscle_groups']);
        $this->assertFalse($bookingWithoutGroups['muscle_groups_recorded']);
    }

    /** @test */
    public function all_muscle_group_values_are_valid()
    {
        Sanctum::actingAs($this->user);

        $validGroups = [
            'total_body',
            'legs',
            'chest',
            'back',
            'shoulders',
            'arms',
            'core',
            'cardio'
        ];

        foreach ($validGroups as $group) {
            $response = $this->postJson("/api/v1/workouts/{$this->booking->id}/muscle-groups", [
                'muscle_groups' => [$group]
            ]);

            $response->assertStatus(200);
        }
    }
}