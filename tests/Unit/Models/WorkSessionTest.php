<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_work_session(): void
    {
        $session = WorkSession::factory()->create();
        $this->assertDatabaseHas('work_sessions', ['id' => $session->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $session = WorkSession::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $session->user);
        $this->assertEquals($user->id, $session->user->id);
    }

    public function test_active_state(): void
    {
        $session = WorkSession::factory()->active()->create();

        $this->assertNotNull($session->clock_in);
        $this->assertNull($session->clock_out);
    }

    public function test_completed_state(): void
    {
        $session = WorkSession::factory()->completed()->create();
        $this->assertNotNull($session->clock_out);
    }

    public function test_today_state(): void
    {
        $session = WorkSession::factory()->today()->create();
        $this->assertEquals(now()->toDateString(), $session->clock_in->toDateString());
    }

    public function test_has_hours_worked(): void
    {
        $session = WorkSession::factory()->completed()->create();
        $this->assertNotNull($session->hours_worked);
    }

    public function test_active_scope(): void
    {
        WorkSession::factory()->active()->count(2)->create();
        WorkSession::factory()->completed()->create();

        $this->assertCount(2, WorkSession::active()->get());
    }

    public function test_completed_scope(): void
    {
        WorkSession::factory()->active()->create();
        WorkSession::factory()->completed()->count(2)->create();

        $this->assertCount(2, WorkSession::completed()->get());
    }
}
