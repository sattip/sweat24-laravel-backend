<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\User;
use App\Models\WorkoutMuscleGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutMuscleGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_workout_muscle_group(): void
    {
        $workoutGroup = WorkoutMuscleGroup::factory()->create();
        $this->assertDatabaseHas('workout_muscle_groups', ['id' => $workoutGroup->id]);
    }

    public function test_belongs_to_booking(): void
    {
        $booking = Booking::factory()->create();
        $workoutGroup = WorkoutMuscleGroup::factory()->create(['booking_id' => $booking->id]);

        $this->assertInstanceOf(Booking::class, $workoutGroup->booking);
        $this->assertEquals($booking->id, $workoutGroup->booking->id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $workoutGroup = WorkoutMuscleGroup::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $workoutGroup->user);
        $this->assertEquals($user->id, $workoutGroup->user->id);
    }

    public function test_upper_body_state(): void
    {
        $workoutGroup = WorkoutMuscleGroup::factory()->upperBody()->create();
        $expectedMuscles = ['chest', 'back', 'shoulders', 'arms'];

        $this->assertEquals($expectedMuscles, $workoutGroup->muscle_groups);
    }

    public function test_lower_body_state(): void
    {
        $workoutGroup = WorkoutMuscleGroup::factory()->lowerBody()->create();
        $expectedMuscles = ['legs', 'glutes'];

        $this->assertEquals($expectedMuscles, $workoutGroup->muscle_groups);
    }

    public function test_full_body_state(): void
    {
        $workoutGroup = WorkoutMuscleGroup::factory()->fullBody()->create();
        $expectedMuscles = ['chest', 'back', 'legs', 'shoulders', 'arms', 'core'];

        $this->assertEquals($expectedMuscles, $workoutGroup->muscle_groups);
    }

    public function test_muscle_groups_is_array(): void
    {
        $workoutGroup = WorkoutMuscleGroup::factory()->create();

        $this->assertIsArray($workoutGroup->muscle_groups);
        $this->assertNotEmpty($workoutGroup->muscle_groups);
    }
}
