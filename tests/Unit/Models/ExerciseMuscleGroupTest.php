<?php

namespace Tests\Unit\Models;

use App\Models\ExerciseMuscleGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseMuscleGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_exercise_muscle_group(): void
    {
        $muscleGroup = ExerciseMuscleGroup::factory()->create();
        $this->assertDatabaseHas('exercise_muscle_groups', ['id' => $muscleGroup->id]);
    }

    public function test_active_state(): void
    {
        $muscleGroup = ExerciseMuscleGroup::factory()->active()->create();
        $this->assertTrue($muscleGroup->is_active);
    }

    public function test_inactive_state(): void
    {
        $muscleGroup = ExerciseMuscleGroup::factory()->inactive()->create();
        $this->assertFalse($muscleGroup->is_active);
    }

    public function test_active_scope(): void
    {
        ExerciseMuscleGroup::factory()->active()->count(2)->create();
        ExerciseMuscleGroup::factory()->inactive()->create();

        $this->assertCount(2, ExerciseMuscleGroup::active()->get());
    }
}
