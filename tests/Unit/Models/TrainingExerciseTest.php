<?php

namespace Tests\Unit\Models;

use App\Models\Exercise;
use App\Models\TrainingExercise;
use App\Models\TrainingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_training_exercise(): void
    {
        $session = TrainingSession::factory()->create();
        $exercise = Exercise::factory()->create();

        $trainingExercise = TrainingExercise::factory()->create([
            'training_session_id' => $session->id,
            'exercise_id' => $exercise->id,
        ]);

        $this->assertDatabaseHas('training_exercises', [
            'id' => $trainingExercise->id,
            'training_session_id' => $session->id,
            'exercise_id' => $exercise->id,
        ]);
    }

    public function test_training_exercise_belongs_to_session(): void
    {
        $session = TrainingSession::factory()->create();
        $trainingExercise = TrainingExercise::factory()->create(['training_session_id' => $session->id]);

        $this->assertInstanceOf(TrainingSession::class, $trainingExercise->trainingSession);
        $this->assertEquals($session->id, $trainingExercise->trainingSession->id);
    }

    public function test_training_exercise_belongs_to_exercise(): void
    {
        $exercise = Exercise::factory()->create();
        $trainingExercise = TrainingExercise::factory()->create(['exercise_id' => $exercise->id]);

        $this->assertInstanceOf(Exercise::class, $trainingExercise->exercise);
        $this->assertEquals($exercise->id, $trainingExercise->exercise->id);
    }

    public function test_volume_attribute(): void
    {
        $trainingExercise = TrainingExercise::factory()->create([
            'sets' => 3,
            'reps' => 10,
            'weight_kg' => 50,
        ]);

        $this->assertEquals(1500, $trainingExercise->volume);
    }

    public function test_volume_attribute_with_null_weight(): void
    {
        $trainingExercise = TrainingExercise::factory()->bodyweight()->create([
            'sets' => 3,
            'reps' => 10,
        ]);

        $this->assertEquals(0, $trainingExercise->volume);
    }

    public function test_exercise_type_label_standard(): void
    {
        $trainingExercise = TrainingExercise::factory()->standard()->create();

        $this->assertEquals('Κανονικό', $trainingExercise->exercise_type_label);
    }

    public function test_exercise_type_label_superset(): void
    {
        $trainingExercise = TrainingExercise::factory()->superset()->create();

        $this->assertEquals('Superset', $trainingExercise->exercise_type_label);
    }

    public function test_exercise_type_label_drop(): void
    {
        $trainingExercise = TrainingExercise::factory()->dropSet()->create();

        $this->assertEquals('Drop Set', $trainingExercise->exercise_type_label);
    }

    public function test_exercise_type_icon(): void
    {
        $superset = TrainingExercise::factory()->superset()->create();
        $standard = TrainingExercise::factory()->standard()->create();

        $this->assertEquals('🔁', $superset->exercise_type_icon);
        $this->assertEquals('', $standard->exercise_type_icon);
    }

    public function test_display_name_from_exercise(): void
    {
        $exercise = Exercise::factory()->create(['name_gr' => 'Κάθισμα', 'name_en' => 'Squat']);
        $trainingExercise = TrainingExercise::factory()->create(['exercise_id' => $exercise->id]);

        $this->assertEquals('Κάθισμα', $trainingExercise->display_name);
    }

    public function test_display_name_from_custom_exercise_name(): void
    {
        $trainingExercise = TrainingExercise::factory()->customExercise('Custom Exercise')->create();

        $this->assertEquals('Custom Exercise', $trainingExercise->display_name);
    }

    public function test_bodyweight_state(): void
    {
        $trainingExercise = TrainingExercise::factory()->bodyweight()->create();

        $this->assertNull($trainingExercise->weight_kg);
    }

    public function test_with_tempo_state(): void
    {
        $trainingExercise = TrainingExercise::factory()->withTempo()->create();

        $this->assertEquals('3-1-1-0', $trainingExercise->tempo);
    }

    public function test_with_rir_state(): void
    {
        $trainingExercise = TrainingExercise::factory()->withRir(2)->create();

        $this->assertEquals(2, $trainingExercise->rir);
    }

    public function test_with_notes_state(): void
    {
        $trainingExercise = TrainingExercise::factory()->withNotes()->create();

        $this->assertNotNull($trainingExercise->notes);
    }

    public function test_exercise_type_states(): void
    {
        $standard = TrainingExercise::factory()->standard()->create();
        $superset = TrainingExercise::factory()->superset()->create();
        $drop = TrainingExercise::factory()->dropSet()->create();
        $pyramid = TrainingExercise::factory()->pyramid()->create();
        $emom = TrainingExercise::factory()->emom()->create();
        $amrap = TrainingExercise::factory()->amrap()->create();
        $failure = TrainingExercise::factory()->toFailure()->create();
        $timed = TrainingExercise::factory()->timed()->create();

        $this->assertEquals('standard', $standard->exercise_type);
        $this->assertEquals('superset', $superset->exercise_type);
        $this->assertEquals('drop', $drop->exercise_type);
        $this->assertEquals('pyramid', $pyramid->exercise_type);
        $this->assertEquals('emom', $emom->exercise_type);
        $this->assertEquals('amrap', $amrap->exercise_type);
        $this->assertEquals('failure', $failure->exercise_type);
        $this->assertEquals('timed', $timed->exercise_type);
    }

    public function test_integer_casts(): void
    {
        $trainingExercise = TrainingExercise::factory()->create([
            'sets' => 3,
            'reps' => 10,
            'rest_seconds' => 60,
            'rir' => 2,
            'order' => 1,
        ]);

        $this->assertIsInt($trainingExercise->sets);
        $this->assertIsInt($trainingExercise->reps);
        $this->assertIsInt($trainingExercise->rest_seconds);
        $this->assertIsInt($trainingExercise->rir);
        $this->assertIsInt($trainingExercise->order);
    }

    public function test_weight_decimal_cast(): void
    {
        $trainingExercise = TrainingExercise::factory()->create(['weight_kg' => 50.5]);

        $this->assertEquals('50.50', $trainingExercise->weight_kg);
    }
}
