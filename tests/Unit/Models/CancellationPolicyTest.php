<?php

namespace Tests\Unit\Models;

use App\Models\CancellationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_cancellation_policy(): void
    {
        $policy = CancellationPolicy::factory()->create();
        $this->assertDatabaseHas('cancellation_policies', ['id' => $policy->id]);
    }

    public function test_active_state(): void
    {
        $policy = CancellationPolicy::factory()->active()->create();
        $this->assertTrue($policy->is_active);
    }

    public function test_inactive_state(): void
    {
        $policy = CancellationPolicy::factory()->inactive()->create();
        $this->assertFalse($policy->is_active);
    }

    public function test_no_reschedule_state(): void
    {
        $policy = CancellationPolicy::factory()->noReschedule()->create();
        $this->assertFalse($policy->allow_reschedule);
    }

    public function test_strict_state(): void
    {
        $policy = CancellationPolicy::factory()->strict()->create();

        $this->assertEquals(48, $policy->hours_before);
        $this->assertEquals(100, $policy->penalty_percentage);
        $this->assertFalse($policy->allow_reschedule);
    }

    public function test_lenient_state(): void
    {
        $policy = CancellationPolicy::factory()->lenient()->create();

        $this->assertEquals(2, $policy->hours_before);
        $this->assertEquals(0, $policy->penalty_percentage);
        $this->assertTrue($policy->allow_reschedule);
        $this->assertEquals(5, $policy->max_reschedules_per_month);
    }

    public function test_for_class_types(): void
    {
        $policy = CancellationPolicy::factory()->forClassTypes(['yoga', 'pilates'])->create();

        $this->assertIsArray($policy->applicable_to);
        $this->assertEquals(['yoga', 'pilates'], $policy->applicable_to['class_types']);
    }

    public function test_for_packages(): void
    {
        $policy = CancellationPolicy::factory()->forPackages([1, 2, 3])->create();

        $this->assertIsArray($policy->applicable_to);
        $this->assertEquals([1, 2, 3], $policy->applicable_to['package_ids']);
    }
}
