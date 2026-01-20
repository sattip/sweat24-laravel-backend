<?php

namespace Database\Factories;

use App\Models\BulkOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulkOperationFactory extends Factory
{
    protected $model = BulkOperation::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement([
                BulkOperation::TYPE_PACKAGE_EXTENSION,
                BulkOperation::TYPE_PRICING_ADJUSTMENT,
            ]),
            'performed_by' => User::factory(),
            'target_count' => $this->faker->numberBetween(10, 100),
            'successful_count' => 0,
            'failed_count' => 0,
            'status' => BulkOperation::STATUS_PENDING,
            'filters' => [],
            'operation_data' => [],
            'errors' => [],
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => BulkOperation::STATUS_PENDING]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => BulkOperation::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $targetCount = $attributes['target_count'] ?? 50;
            return [
                'status' => BulkOperation::STATUS_COMPLETED,
                'successful_count' => $targetCount,
                'failed_count' => 0,
                'started_at' => now()->subMinutes(5),
                'completed_at' => now(),
            ];
        });
    }

    public function completedWithErrors(): static
    {
        return $this->state(function (array $attributes) {
            $targetCount = $attributes['target_count'] ?? 50;
            $failedCount = $this->faker->numberBetween(1, (int) ($targetCount * 0.2));
            return [
                'status' => BulkOperation::STATUS_COMPLETED_WITH_ERRORS,
                'successful_count' => $targetCount - $failedCount,
                'failed_count' => $failedCount,
                'started_at' => now()->subMinutes(5),
                'completed_at' => now(),
                'errors' => [['message' => 'Some operations failed']],
            ];
        });
    }

    public function failed(): static
    {
        return $this->state([
            'status' => BulkOperation::STATUS_FAILED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'errors' => [['message' => 'Operation failed']],
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => BulkOperation::STATUS_CANCELLED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    public function packageExtension(): static
    {
        return $this->state(['type' => BulkOperation::TYPE_PACKAGE_EXTENSION]);
    }

    public function pricingAdjustment(): static
    {
        return $this->state(['type' => BulkOperation::TYPE_PRICING_ADJUSTMENT]);
    }
}
