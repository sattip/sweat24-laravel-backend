<?php

namespace Tests\Unit\Models;

use App\Models\BulkOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkOperationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_bulk_operation(): void
    {
        $operation = BulkOperation::factory()->create();
        $this->assertDatabaseHas('bulk_operations', ['id' => $operation->id]);
    }

    public function test_belongs_to_performed_by_user(): void
    {
        $user = User::factory()->create();
        $operation = BulkOperation::factory()->create(['performed_by' => $user->id]);

        $this->assertInstanceOf(User::class, $operation->performedBy);
        $this->assertEquals($user->id, $operation->performedBy->id);
    }

    public function test_pending_state(): void
    {
        $operation = BulkOperation::factory()->pending()->create();
        $this->assertEquals(BulkOperation::STATUS_PENDING, $operation->status);
    }

    public function test_in_progress_state(): void
    {
        $operation = BulkOperation::factory()->inProgress()->create();

        $this->assertEquals(BulkOperation::STATUS_IN_PROGRESS, $operation->status);
        $this->assertNotNull($operation->started_at);
    }

    public function test_completed_state(): void
    {
        $operation = BulkOperation::factory()->completed()->create();

        $this->assertEquals(BulkOperation::STATUS_COMPLETED, $operation->status);
        $this->assertNotNull($operation->completed_at);
    }

    public function test_completed_with_errors_state(): void
    {
        $operation = BulkOperation::factory()->completedWithErrors()->create();

        $this->assertEquals(BulkOperation::STATUS_COMPLETED_WITH_ERRORS, $operation->status);
        $this->assertGreaterThan(0, $operation->failed_count);
    }

    public function test_failed_state(): void
    {
        $operation = BulkOperation::factory()->failed()->create();

        $this->assertEquals(BulkOperation::STATUS_FAILED, $operation->status);
        $this->assertNotNull($operation->errors);
    }

    public function test_cancelled_state(): void
    {
        $operation = BulkOperation::factory()->cancelled()->create();
        $this->assertEquals(BulkOperation::STATUS_CANCELLED, $operation->status);
    }

    public function test_package_extension_state(): void
    {
        $operation = BulkOperation::factory()->packageExtension()->create();
        $this->assertEquals(BulkOperation::TYPE_PACKAGE_EXTENSION, $operation->type);
    }

    public function test_pricing_adjustment_state(): void
    {
        $operation = BulkOperation::factory()->pricingAdjustment()->create();
        $this->assertEquals(BulkOperation::TYPE_PRICING_ADJUSTMENT, $operation->type);
    }

    public function test_is_running_returns_true_for_pending(): void
    {
        $operation = BulkOperation::factory()->pending()->create();
        $this->assertTrue($operation->isRunning());
    }

    public function test_is_running_returns_true_for_in_progress(): void
    {
        $operation = BulkOperation::factory()->inProgress()->create();
        $this->assertTrue($operation->isRunning());
    }

    public function test_is_running_returns_false_for_completed(): void
    {
        $operation = BulkOperation::factory()->completed()->create();
        $this->assertFalse($operation->isRunning());
    }

    public function test_is_successful(): void
    {
        $completed = BulkOperation::factory()->completed()->create();
        $failed = BulkOperation::factory()->failed()->create();

        $this->assertTrue($completed->isSuccessful());
        $this->assertFalse($failed->isSuccessful());
    }

    public function test_has_errors(): void
    {
        $withErrors = BulkOperation::factory()->completedWithErrors()->create();
        $completed = BulkOperation::factory()->completed()->create();

        $this->assertTrue($withErrors->hasErrors());
        $this->assertFalse($completed->hasErrors());
    }

    public function test_progress_percentage(): void
    {
        $operation = BulkOperation::factory()->create([
            'target_count' => 100,
            'successful_count' => 30,
            'failed_count' => 10,
            'status' => BulkOperation::STATUS_IN_PROGRESS,
        ]);

        $this->assertEquals(40, $operation->progress_percentage);
    }

    public function test_progress_percentage_returns_100_for_completed(): void
    {
        $operation = BulkOperation::factory()->completed()->create();
        $this->assertEquals(100, $operation->progress_percentage);
    }

    public function test_success_rate(): void
    {
        $operation = BulkOperation::factory()->create([
            'target_count' => 100,
            'successful_count' => 80,
        ]);

        $this->assertEquals(80, $operation->success_rate);
    }

    public function test_type_label(): void
    {
        $operation = BulkOperation::factory()->packageExtension()->create();
        $this->assertEquals('Package Extension', $operation->getTypeLabel());
    }

    public function test_status_label(): void
    {
        $operation = BulkOperation::factory()->completed()->create();
        $this->assertEquals('Completed', $operation->getStatusLabel());
    }

    public function test_of_type_scope(): void
    {
        BulkOperation::factory()->packageExtension()->count(2)->create();
        BulkOperation::factory()->pricingAdjustment()->create();

        $this->assertCount(2, BulkOperation::ofType(BulkOperation::TYPE_PACKAGE_EXTENSION)->get());
    }

    public function test_with_status_scope(): void
    {
        BulkOperation::factory()->pending()->count(2)->create();
        BulkOperation::factory()->completed()->create();

        $this->assertCount(2, BulkOperation::withStatus(BulkOperation::STATUS_PENDING)->get());
    }

    public function test_filters_cast_to_array(): void
    {
        $operation = BulkOperation::factory()->create(['filters' => ['key' => 'value']]);
        $this->assertIsArray($operation->filters);
    }

    public function test_operation_data_cast_to_array(): void
    {
        $operation = BulkOperation::factory()->create(['operation_data' => ['key' => 'value']]);
        $this->assertIsArray($operation->operation_data);
    }

    public function test_errors_cast_to_array(): void
    {
        $operation = BulkOperation::factory()->create(['errors' => ['error' => 'message']]);
        $this->assertIsArray($operation->errors);
    }
}
