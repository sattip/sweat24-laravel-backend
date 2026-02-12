<?php

namespace Tests\Unit\Models;

use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_questionnaire_response(): void
    {
        $response = QuestionnaireResponse::factory()->create();
        $this->assertDatabaseHas('questionnaire_responses', ['id' => $response->id]);
    }

    public function test_belongs_to_questionnaire(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        $response = QuestionnaireResponse::factory()->create(['questionnaire_id' => $questionnaire->id]);

        $this->assertInstanceOf(Questionnaire::class, $response->questionnaire);
        $this->assertEquals($questionnaire->id, $response->questionnaire->id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $response = QuestionnaireResponse::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $response->user);
        $this->assertEquals($user->id, $response->user->id);
    }

    public function test_recent_state(): void
    {
        $response = QuestionnaireResponse::factory()->recent()->create();
        $this->assertTrue($response->completed_at->lt(now()));
    }

    public function test_complete_state(): void
    {
        $response = QuestionnaireResponse::factory()->complete()->create();
        $this->assertNotNull($response->completed_at);
    }

    public function test_responses_is_array(): void
    {
        $response = QuestionnaireResponse::factory()->create();

        $this->assertIsArray($response->responses);
        $this->assertNotEmpty($response->responses);
    }

    public function test_has_trigger_type(): void
    {
        $response = QuestionnaireResponse::factory()->create();
        $validTypes = ['after_lesson', 'daily', 'weekly', 'manual'];

        $this->assertContains($response->trigger_type, $validTypes);
    }
}
