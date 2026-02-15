<?php

namespace Tests\Unit\Models;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_task(): void
    {
        $task = Task::factory()->create();
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $task->creator);
        $this->assertEquals($user->id, $task->creator->id);
    }

    public function test_belongs_to_assignee(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $this->assertInstanceOf(User::class, $task->assignee);
        $this->assertEquals($user->id, $task->assignee->id);
    }

    public function test_pending_state(): void
    {
        $task = Task::factory()->pending()->create();

        $this->assertEquals('pending', $task->status);
        $this->assertNull($task->completion_date);
    }

    public function test_in_progress_state(): void
    {
        $task = Task::factory()->inProgress()->create();

        $this->assertEquals('in_progress', $task->status);
        $this->assertNull($task->completion_date);
    }

    public function test_completed_state(): void
    {
        $task = Task::factory()->completed()->create();

        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completion_date);
    }

    public function test_cancelled_state(): void
    {
        $task = Task::factory()->cancelled()->create();
        $this->assertEquals('cancelled', $task->status);
    }

    public function test_low_priority_state(): void
    {
        $task = Task::factory()->lowPriority()->create();
        $this->assertEquals('low', $task->priority);
    }

    public function test_medium_priority_state(): void
    {
        $task = Task::factory()->mediumPriority()->create();
        $this->assertEquals('medium', $task->priority);
    }

    public function test_high_priority_state(): void
    {
        $task = Task::factory()->highPriority()->create();
        $this->assertEquals('high', $task->priority);
    }

    public function test_by_status_scope(): void
    {
        Task::factory()->pending()->count(2)->create();
        Task::factory()->completed()->create();

        $this->assertCount(2, Task::byStatus('pending')->get());
    }

    public function test_by_priority_scope(): void
    {
        Task::factory()->highPriority()->count(2)->create();
        Task::factory()->lowPriority()->create();

        $this->assertCount(2, Task::byPriority('high')->get());
    }

    public function test_assigned_to_scope(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['assigned_to' => $user->id]);
        Task::factory()->create();

        $this->assertCount(2, Task::assignedTo($user->id)->get());
    }

    public function test_created_by_scope(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['created_by' => $user->id]);
        Task::factory()->create();

        $this->assertCount(2, Task::createdBy($user->id)->get());
    }
}
