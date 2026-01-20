<?php

namespace Tests\Unit\Models;

use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_questionnaire(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        $this->assertDatabaseHas('questionnaires', ['id' => $questionnaire->id]);
    }

    public function test_active_state(): void
    {
        $questionnaire = Questionnaire::factory()->active()->create();
        $this->assertTrue($questionnaire->is_active);
    }

    public function test_inactive_state(): void
    {
        $questionnaire = Questionnaire::factory()->inactive()->create();
        $this->assertFalse($questionnaire->is_active);
    }

    public function test_feedback_state(): void
    {
        $questionnaire = Questionnaire::factory()->feedback()->create();
        $this->assertEquals('after_lesson', $questionnaire->triggers['type']);
    }

    public function test_survey_state(): void
    {
        $questionnaire = Questionnaire::factory()->survey()->create();
        $this->assertEquals('manual', $questionnaire->triggers['type']);
    }

    public function test_questions_is_array(): void
    {
        $questionnaire = Questionnaire::factory()->create();

        $this->assertIsArray($questionnaire->questions);
        $this->assertNotEmpty($questionnaire->questions);
    }

    public function test_triggers_is_array(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        $validTypes = ['after_lesson', 'daily', 'weekly', 'manual'];

        $this->assertIsArray($questionnaire->triggers);
        $this->assertContains($questionnaire->triggers['type'], $validTypes);
    }

    public function test_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $questionnaire = Questionnaire::factory()->create(['created_by' => $user->id]);

        $this->assertEquals($user->id, $questionnaire->created_by);
    }
}
