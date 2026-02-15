<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Store;
use App\Models\WorkoutMuscleGroup;

/**
 * WorkoutMuscleGroupController exists but routes are not registered in api.php.
 * These tests validate the model and data layer directly.
 */
class WorkoutMuscleGroupTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $store = Store::create(['name' => 'Test Store', 'address' => '123 Test St']);

        $this->booking = Booking::create([
            'user_id' => $this->user->id,
            'store_id' => $store->id,
            'class_name' => 'Test Class',
            'instructor' => 'Test Trainer',
            'date' => now()->subDay()->toDateString(),
            'time' => '10:00',
            'type' => 'personal',
            'status' => 'completed',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'location' => 'Test Store',
            'booking_time' => now(),
            'attended' => 1,
        ]);
    }

    /** @test */
    public function can_create_muscle_group_record()
    {
        $muscleGroup = WorkoutMuscleGroup::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->user->id,
            'muscle_groups' => ['legs', 'core'],
        ]);

        $this->assertDatabaseHas('workout_muscle_groups', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(['legs', 'core'], $muscleGroup->fresh()->muscle_groups);
    }

    /** @test */
    public function can_store_single_muscle_group()
    {
        $muscleGroup = WorkoutMuscleGroup::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->user->id,
            'muscle_groups' => ['total_body'],
        ]);

        $this->assertEquals(['total_body'], $muscleGroup->fresh()->muscle_groups);
    }

    /** @test */
    public function can_update_existing_muscle_groups()
    {
        $muscleGroup = WorkoutMuscleGroup::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->user->id,
            'muscle_groups' => ['legs'],
        ]);

        $muscleGroup->update(['muscle_groups' => ['chest', 'back']]);

        $this->assertEquals(['chest', 'back'], $muscleGroup->fresh()->muscle_groups);

        // Still only one record
        $this->assertEquals(1, WorkoutMuscleGroup::where('booking_id', $this->booking->id)->count());
    }

    /** @test */
    public function muscle_groups_stored_as_json()
    {
        $validGroups = ['total_body', 'legs', 'chest', 'back', 'shoulders', 'arms', 'core', 'cardio'];

        $muscleGroup = WorkoutMuscleGroup::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->user->id,
            'muscle_groups' => $validGroups,
        ]);

        $stored = $muscleGroup->fresh()->muscle_groups;
        $this->assertEquals($validGroups, $stored);
    }

    /** @test */
    public function muscle_group_belongs_to_booking()
    {
        $muscleGroup = WorkoutMuscleGroup::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->user->id,
            'muscle_groups' => ['legs'],
        ]);

        $this->assertEquals($this->booking->id, $muscleGroup->booking_id);
        $this->assertEquals($this->user->id, $muscleGroup->user_id);
    }
}
