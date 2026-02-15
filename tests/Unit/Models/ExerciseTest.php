<?php

namespace Tests\Unit\Models;

use App\Models\Exercise;
use App\Models\TrainingExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_exercise(): void
    {
        $exercise = Exercise::factory()->create();

        $this->assertDatabaseHas('exercises', [
            'id' => $exercise->id,
            'name_en' => $exercise->name_en,
        ]);
    }

    public function test_exercise_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $exercise->creator);
        $this->assertEquals($user->id, $exercise->creator->id);
    }

    public function test_exercise_has_many_training_exercises(): void
    {
        $exercise = Exercise::factory()->create();
        TrainingExercise::factory()->count(3)->create(['exercise_id' => $exercise->id]);

        $this->assertCount(3, $exercise->trainingExercises);
        $this->assertInstanceOf(TrainingExercise::class, $exercise->trainingExercises->first());
    }

    public function test_by_muscle_group_scope(): void
    {
        Exercise::factory()->chest()->count(2)->create();
        Exercise::factory()->back()->create();

        $chestExercises = Exercise::byMuscleGroup('chest')->get();

        $this->assertCount(2, $chestExercises);
    }

    public function test_by_category_scope(): void
    {
        Exercise::factory()->create(['category' => 'strength']);
        Exercise::factory()->count(2)->create(['category' => 'cardio']);

        $cardioExercises = Exercise::byCategory('cardio')->get();

        $this->assertCount(2, $cardioExercises);
    }

    public function test_by_difficulty_scope(): void
    {
        Exercise::factory()->beginner()->count(2)->create();
        Exercise::factory()->advanced()->create();

        $beginnerExercises = Exercise::byDifficulty('beginner')->get();

        $this->assertCount(2, $beginnerExercises);
    }

    public function test_by_equipment_scope(): void
    {
        Exercise::factory()->create(['equipment' => ['barbell', 'dumbbell']]);
        Exercise::factory()->create(['equipment' => ['machine']]);

        $barbellExercises = Exercise::byEquipment('barbell')->get();

        $this->assertCount(1, $barbellExercises);
    }

    public function test_active_scope(): void
    {
        Exercise::factory()->active()->count(2)->create();
        Exercise::factory()->inactive()->create();

        $activeExercises = Exercise::active()->get();

        $this->assertCount(2, $activeExercises);
    }

    public function test_search_scope(): void
    {
        Exercise::factory()->create(['name_en' => 'Bench Press', 'name_gr' => 'Πιέσεις Πάγκου']);
        Exercise::factory()->create(['name_en' => 'Squat', 'name_gr' => 'Κάθισμα']);

        $results = Exercise::search('Bench')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Bench Press', $results->first()->name_en);
    }

    public function test_search_scope_greek(): void
    {
        Exercise::factory()->create(['name_en' => 'Bench Press', 'name_gr' => 'Πιέσεις Πάγκου']);

        $results = Exercise::search('Πιέσεις')->get();

        $this->assertCount(1, $results);
    }

    public function test_equipment_cast_to_array(): void
    {
        $exercise = Exercise::factory()->create(['equipment' => ['barbell', 'dumbbell']]);

        $this->assertIsArray($exercise->equipment);
        $this->assertContains('barbell', $exercise->equipment);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $exercise = Exercise::factory()->create(['is_active' => true]);

        $this->assertIsBool($exercise->is_active);
        $this->assertTrue($exercise->is_active);
    }

    public function test_active_state(): void
    {
        $exercise = Exercise::factory()->active()->create();

        $this->assertTrue($exercise->is_active);
    }

    public function test_inactive_state(): void
    {
        $exercise = Exercise::factory()->inactive()->create();

        $this->assertFalse($exercise->is_active);
    }

    public function test_difficulty_states(): void
    {
        $beginner = Exercise::factory()->beginner()->create();
        $intermediate = Exercise::factory()->intermediate()->create();
        $advanced = Exercise::factory()->advanced()->create();

        $this->assertEquals('beginner', $beginner->difficulty_level);
        $this->assertEquals('intermediate', $intermediate->difficulty_level);
        $this->assertEquals('advanced', $advanced->difficulty_level);
    }

    public function test_muscle_group_states(): void
    {
        $chest = Exercise::factory()->chest()->create();
        $back = Exercise::factory()->back()->create();
        $legs = Exercise::factory()->legs()->create();

        $this->assertEquals('chest', $chest->muscle_group);
        $this->assertEquals('back', $back->muscle_group);
        $this->assertEquals('legs', $legs->muscle_group);
    }
}
