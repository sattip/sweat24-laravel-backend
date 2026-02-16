<?php

namespace Tests\Unit\Models;

use App\Models\BulkOperation;
use App\Models\ExerciseCategory;
use App\Models\ExerciseEquipment;
use App\Models\ExerciseMuscleGroup;
use App\Models\FitnessLevel;
use App\Models\NotificationFilter;
use App\Models\NotificationLog;
use App\Models\PackageNotificationLog;
use App\Models\PriorityBookingSetting;
use App\Models\PriorityBookingSettings;
use App\Models\SpecializedService;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_exercise_category_factory(): void
    {
        $category = ExerciseCategory::factory()->create();
        $this->assertDatabaseHas('exercise_categories', ['id' => $category->id]);
    }

    public function test_exercise_category_active_state(): void
    {
        $category = ExerciseCategory::factory()->active()->create();
        $this->assertTrue($category->is_active);
    }

    public function test_exercise_category_inactive_state(): void
    {
        $category = ExerciseCategory::factory()->inactive()->create();
        $this->assertFalse($category->is_active);
    }

    public function test_exercise_equipment_factory(): void
    {
        $equipment = ExerciseEquipment::factory()->create();
        $this->assertDatabaseHas('exercise_equipment', ['id' => $equipment->id]);
    }

    public function test_exercise_muscle_group_factory(): void
    {
        $muscleGroup = ExerciseMuscleGroup::factory()->create();
        $this->assertDatabaseHas('exercise_muscle_groups', ['id' => $muscleGroup->id]);
    }

    public function test_fitness_level_factory(): void
    {
        $fitnessLevel = FitnessLevel::factory()->create();
        $this->assertDatabaseHas('fitness_levels', ['id' => $fitnessLevel->id]);
    }

    public function test_fitness_level_beginner_state(): void
    {
        $fitnessLevel = FitnessLevel::factory()->beginner()->create();
        $this->assertEquals('beginner', $fitnessLevel->level);
    }

    public function test_fitness_level_advanced_state(): void
    {
        $fitnessLevel = FitnessLevel::factory()->advanced()->create();
        $this->assertEquals('advanced', $fitnessLevel->level);
    }

    public function test_fitness_level_elite_state(): void
    {
        $fitnessLevel = FitnessLevel::factory()->elite()->create();
        $this->assertEquals('elite', $fitnessLevel->level);
    }

    public function test_fitness_level_relationships(): void
    {
        $fitnessLevel = FitnessLevel::factory()->create();
        $this->assertNotNull($fitnessLevel->user);
        $this->assertNotNull($fitnessLevel->assessor);
    }

    public function test_fitness_level_label_attribute(): void
    {
        $fitnessLevel = FitnessLevel::factory()->beginner()->create();
        $this->assertEquals('Πολύ Αρχάριος', $fitnessLevel->level_label);
    }

    public function test_fitness_level_color_attribute(): void
    {
        $fitnessLevel = FitnessLevel::factory()->elite()->create();
        $this->assertEquals('purple', $fitnessLevel->level_color);
    }

    public function test_bulk_operation_factory(): void
    {
        $operation = BulkOperation::factory()->create();
        $this->assertDatabaseHas('bulk_operations', ['id' => $operation->id]);
    }

    public function test_bulk_operation_pending_state(): void
    {
        $operation = BulkOperation::factory()->pending()->create();
        $this->assertEquals(BulkOperation::STATUS_PENDING, $operation->status);
    }

    public function test_bulk_operation_in_progress_state(): void
    {
        $operation = BulkOperation::factory()->inProgress()->create();
        $this->assertEquals(BulkOperation::STATUS_IN_PROGRESS, $operation->status);
        $this->assertNotNull($operation->started_at);
    }

    public function test_bulk_operation_completed_state(): void
    {
        $operation = BulkOperation::factory()->completed()->create();
        $this->assertEquals(BulkOperation::STATUS_COMPLETED, $operation->status);
        $this->assertNotNull($operation->completed_at);
    }

    public function test_bulk_operation_failed_state(): void
    {
        $operation = BulkOperation::factory()->failed()->create();
        $this->assertEquals(BulkOperation::STATUS_FAILED, $operation->status);
    }

    public function test_bulk_operation_is_running(): void
    {
        $pending = BulkOperation::factory()->pending()->create();
        $inProgress = BulkOperation::factory()->inProgress()->create();
        $completed = BulkOperation::factory()->completed()->create();

        $this->assertTrue($pending->isRunning());
        $this->assertTrue($inProgress->isRunning());
        $this->assertFalse($completed->isRunning());
    }

    public function test_bulk_operation_progress_percentage(): void
    {
        $operation = BulkOperation::factory()->create([
            'target_count' => 100,
            'successful_count' => 30,
            'failed_count' => 10,
            'status' => BulkOperation::STATUS_IN_PROGRESS,
        ]);

        $this->assertEquals(40, $operation->progress_percentage);
    }

    public function test_notification_filter_factory(): void
    {
        $filter = NotificationFilter::factory()->create();
        $this->assertDatabaseHas('notification_filters', ['id' => $filter->id]);
    }

    public function test_notification_filter_active_scope(): void
    {
        NotificationFilter::factory()->active()->count(2)->create();
        NotificationFilter::factory()->inactive()->create();

        $this->assertCount(2, NotificationFilter::active()->get());
    }

    public function test_notification_log_factory(): void
    {
        $log = NotificationLog::factory()->create();
        $this->assertDatabaseHas('notification_logs', ['id' => $log->id]);
    }

    public function test_notification_log_success_state(): void
    {
        $log = NotificationLog::factory()->success()->create();
        $this->assertEquals('success', $log->status);
    }

    public function test_notification_log_failed_state(): void
    {
        $log = NotificationLog::factory()->failed()->create();
        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->error_message);
    }

    public function test_package_notification_log_factory(): void
    {
        $log = PackageNotificationLog::factory()->create();
        $this->assertDatabaseHas('package_notification_logs', ['id' => $log->id]);
    }

    public function test_package_notification_log_successful_scope(): void
    {
        PackageNotificationLog::factory()->successful()->count(2)->create();
        PackageNotificationLog::factory()->failed()->create();

        $this->assertCount(2, PackageNotificationLog::successful()->get());
    }

    public function test_priority_booking_setting_factory(): void
    {
        $setting = PriorityBookingSetting::factory()->create();
        $this->assertDatabaseHas('priority_booking_settings', ['id' => $setting->id]);
    }

    public function test_priority_booking_settings_factory(): void
    {
        // Both PriorityBookingSetting and PriorityBookingSettings use same table
        // and there's seeded data, so we just verify the factory creates properly
        PriorityBookingSettings::query()->delete();
        $settings = PriorityBookingSettings::factory()->create();
        $this->assertDatabaseHas('priority_booking_settings', ['id' => $settings->id]);
    }

    public function test_specialized_service_factory(): void
    {
        $service = SpecializedService::factory()->create();
        $this->assertDatabaseHas('specialized_services', ['id' => $service->id]);
    }

    public function test_specialized_service_has_slug(): void
    {
        $service = SpecializedService::factory()->create(['name' => 'Personal Training', 'slug' => null]);
        $this->assertEquals('personal-training', $service->slug);
    }

    public function test_task_factory(): void
    {
        $task = Task::factory()->create();
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_task_pending_state(): void
    {
        $task = Task::factory()->pending()->create();
        $this->assertEquals('pending', $task->status);
    }

    public function test_task_completed_state(): void
    {
        $task = Task::factory()->completed()->create();
        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completion_date);
    }

    public function test_task_priority_states(): void
    {
        $low = Task::factory()->lowPriority()->create();
        $medium = Task::factory()->mediumPriority()->create();
        $high = Task::factory()->highPriority()->create();

        $this->assertEquals('low', $low->priority);
        $this->assertEquals('medium', $medium->priority);
        $this->assertEquals('high', $high->priority);
    }

    public function test_task_relationships(): void
    {
        $task = Task::factory()->create();
        $this->assertNotNull($task->creator);
        $this->assertNotNull($task->assignee);
    }

    public function test_task_by_status_scope(): void
    {
        Task::factory()->pending()->count(2)->create();
        Task::factory()->completed()->create();

        $this->assertCount(2, Task::byStatus('pending')->get());
    }

    public function test_task_by_priority_scope(): void
    {
        Task::factory()->highPriority()->count(2)->create();
        Task::factory()->lowPriority()->create();

        $this->assertCount(2, Task::byPriority('high')->get());
    }
}
