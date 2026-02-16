<?php

namespace Tests\Unit\Models;

use App\Models\ChurnFeedback;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurnFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_churn_feedback(): void
    {
        $feedback = ChurnFeedback::factory()->create();
        $this->assertDatabaseHas('churn_feedback', ['id' => $feedback->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $feedback = ChurnFeedback::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $feedback->user);
        $this->assertEquals($user->id, $feedback->user->id);
    }

    public function test_churned_state(): void
    {
        $feedback = ChurnFeedback::factory()->churned()->create();
        $this->assertEquals('churn', $feedback->status);
        $this->assertNotNull($feedback->responded_at);
    }

    public function test_paused_state(): void
    {
        $feedback = ChurnFeedback::factory()->paused()->create();
        $this->assertEquals('pause', $feedback->status);
        $this->assertTrue($feedback->pause_response);
    }

    public function test_renewed_state(): void
    {
        $feedback = ChurnFeedback::factory()->renewed()->create();
        $this->assertEquals('renewed', $feedback->status);
    }

    public function test_would_return_state(): void
    {
        $feedback = ChurnFeedback::factory()->wouldReturn()->create();
        $this->assertEquals('yes', $feedback->future_return_intent);
    }

    public function test_would_not_return_state(): void
    {
        $feedback = ChurnFeedback::factory()->wouldNotReturn()->create();
        $this->assertEquals('no', $feedback->future_return_intent);
    }

    public function test_status_is_valid(): void
    {
        $feedback = ChurnFeedback::factory()->create();
        $validStatuses = ['pending', 'churn', 'pause', 'renewed'];
        $this->assertContains($feedback->status, $validStatuses);
    }
}
