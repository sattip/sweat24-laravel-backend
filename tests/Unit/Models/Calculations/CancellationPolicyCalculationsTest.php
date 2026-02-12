<?php

namespace Tests\Unit\Models\Calculations;

use App\Models\CancellationPolicy;
use App\Models\GymClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationPolicyCalculationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Penalty Calculation Tests
    // ==========================================

    public function test_calculates_penalty_correctly(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Standard Policy',
            'description' => 'Standard cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'is_active' => true,
        ]);

        $penalty = $policy->calculatePenalty(100);

        $this->assertEquals(50, $penalty);
    }

    public function test_calculates_penalty_with_decimal_percentage(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Flexible Policy',
            'description' => 'Flexible cancellation policy',
            'hours_before' => 12,
            'penalty_percentage' => 25.5,
            'is_active' => true,
        ]);

        $penalty = $policy->calculatePenalty(200);

        $this->assertEquals(51, $penalty);
    }

    public function test_calculates_zero_penalty(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'No Penalty Policy',
            'description' => 'No penalty policy',
            'hours_before' => 6,
            'penalty_percentage' => 0,
            'is_active' => true,
        ]);

        $penalty = $policy->calculatePenalty(150);

        $this->assertEquals(0, $penalty);
    }

    public function test_calculates_full_penalty(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Strict Policy',
            'description' => 'Strict cancellation policy',
            'hours_before' => 48,
            'penalty_percentage' => 100,
            'is_active' => true,
        ]);

        $penalty = $policy->calculatePenalty(80);

        $this->assertEquals(80, $penalty);
    }

    public function test_calculates_penalty_with_small_amount(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Standard Policy',
            'description' => 'Standard cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 10,
            'is_active' => true,
        ]);

        $penalty = $policy->calculatePenalty(15);

        $this->assertEquals(1.5, $penalty);
    }

    // ==========================================
    // Cancellation Without Penalty Tests
    // ==========================================

    public function test_can_cancel_without_penalty_before_threshold(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Standard Policy',
            'description' => 'Standard cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'is_active' => true,
        ]);

        $this->assertTrue($policy->canCancelWithoutPenalty(25)); // 25 hours before
        $this->assertTrue($policy->canCancelWithoutPenalty(24)); // Exactly at threshold
    }

    public function test_cannot_cancel_without_penalty_after_threshold(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Standard Policy',
            'description' => 'Standard cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'is_active' => true,
        ]);

        $this->assertFalse($policy->canCancelWithoutPenalty(23)); // Just under threshold
        $this->assertFalse($policy->canCancelWithoutPenalty(1));  // 1 hour before
    }

    public function test_can_cancel_without_penalty_with_short_threshold(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Flexible Policy',
            'description' => 'Flexible cancellation policy',
            'hours_before' => 2,
            'penalty_percentage' => 25,
            'is_active' => true,
        ]);

        $this->assertTrue($policy->canCancelWithoutPenalty(3));
        $this->assertTrue($policy->canCancelWithoutPenalty(2));
        $this->assertFalse($policy->canCancelWithoutPenalty(1));
    }

    // ==========================================
    // Reschedule Tests
    // ==========================================

    public function test_can_reschedule_when_allowed(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Flexible Policy',
            'description' => 'Flexible cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'allow_reschedule' => true,
            'reschedule_hours_before' => 12,
            'is_active' => true,
        ]);

        $this->assertTrue($policy->canReschedule(15)); // More than 12 hours
        $this->assertTrue($policy->canReschedule(12)); // Exactly at threshold
    }

    public function test_cannot_reschedule_when_not_allowed(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Strict Policy',
            'description' => 'Strict cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 100,
            'allow_reschedule' => false,
            'is_active' => true,
        ]);

        $this->assertFalse($policy->canReschedule(48)); // Even with lots of time
        $this->assertFalse($policy->canReschedule(1));
    }

    public function test_cannot_reschedule_past_threshold(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Standard Policy',
            'description' => 'Standard cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'allow_reschedule' => true,
            'reschedule_hours_before' => 12,
            'is_active' => true,
        ]);

        $this->assertFalse($policy->canReschedule(11)); // Less than 12 hours
        $this->assertFalse($policy->canReschedule(1));
    }

    public function test_uses_hours_before_when_no_reschedule_hours(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Default Hours Policy',
            'description' => 'Default hours policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'allow_reschedule' => true,
            'reschedule_hours_before' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($policy->canReschedule(25));
        $this->assertTrue($policy->canReschedule(24));
        $this->assertFalse($policy->canReschedule(23));
    }

    // ==========================================
    // Applicability Tests - Class Types
    // ==========================================

    public function test_applies_to_all_when_no_restrictions(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'General Policy',
            'description' => 'General cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'applicable_to' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($policy->appliesToClassType('Yoga'));
        $this->assertTrue($policy->appliesToClassType('HIIT'));
        $this->assertTrue($policy->appliesToClassType('Personal'));
    }

    public function test_applies_to_all_with_empty_array(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'General Policy',
            'description' => 'General cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'applicable_to' => [],
            'is_active' => true,
        ]);

        $this->assertTrue($policy->appliesToClassType('Yoga'));
        $this->assertTrue($policy->appliesToClassType('HIIT'));
    }

    public function test_applies_only_to_specified_class_types(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Group Class Policy',
            'description' => 'Group class policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'applicable_to' => ['class_types' => ['Yoga', 'Pilates']],
            'is_active' => true,
        ]);

        $this->assertTrue($policy->appliesToClassType('Yoga'));
        $this->assertTrue($policy->appliesToClassType('Pilates'));
        $this->assertFalse($policy->appliesToClassType('HIIT'));
        $this->assertFalse($policy->appliesToClassType('CrossFit'));
    }

    // ==========================================
    // Applicability Tests - Packages
    // ==========================================

    public function test_applies_to_all_packages_when_no_restrictions(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'General Policy',
            'description' => 'General cancellation policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'applicable_to' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($policy->appliesToPackage(1));
        $this->assertTrue($policy->appliesToPackage(999));
    }

    public function test_applies_only_to_specified_packages(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Premium Package Policy',
            'description' => 'Premium package policy',
            'hours_before' => 12,
            'penalty_percentage' => 25,
            'applicable_to' => ['package_ids' => [1, 2, 5]],
            'is_active' => true,
        ]);

        $this->assertTrue($policy->appliesToPackage(1));
        $this->assertTrue($policy->appliesToPackage(2));
        $this->assertTrue($policy->appliesToPackage(5));
        $this->assertFalse($policy->appliesToPackage(3));
        $this->assertFalse($policy->appliesToPackage(10));
    }

    // ==========================================
    // Can Be Deleted Tests
    // ==========================================

    public function test_can_be_deleted_when_not_used(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Unused Policy',
            'description' => 'Unused policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'is_active' => true,
        ]);

        $this->assertTrue($policy->canBeDeleted());
    }

    public function test_cannot_be_deleted_when_used_by_class(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Used Policy',
            'description' => 'Used policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'is_active' => true,
        ]);

        GymClass::factory()->create([
            'cancellation_policy_id' => $policy->id,
        ]);

        $this->assertFalse($policy->canBeDeleted());
    }

    public function test_cannot_be_deleted_when_used_by_multiple_classes(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Popular Policy',
            'description' => 'Popular policy',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'is_active' => true,
        ]);

        GymClass::factory()->count(3)->create([
            'cancellation_policy_id' => $policy->id,
        ]);

        $this->assertFalse($policy->canBeDeleted());
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_active_returns_only_active_policies(): void
    {
        CancellationPolicy::create([
            'name' => 'Active Policy 1',
            'description' => 'Active policy 1',
            'hours_before' => 24,
            'penalty_percentage' => 50,
            'is_active' => true,
            'priority' => 1,
        ]);

        CancellationPolicy::create([
            'name' => 'Active Policy 2',
            'description' => 'Active policy 2',
            'hours_before' => 12,
            'penalty_percentage' => 25,
            'is_active' => true,
            'priority' => 2,
        ]);

        CancellationPolicy::create([
            'name' => 'Inactive Policy',
            'description' => 'Inactive policy',
            'hours_before' => 48,
            'penalty_percentage' => 100,
            'is_active' => false,
            'priority' => 0,
        ]);

        $activePolicies = CancellationPolicy::active()->get();

        $this->assertCount(2, $activePolicies);
    }

    // ==========================================
    // Combined Scenario Tests
    // ==========================================

    public function test_strict_policy_scenario(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Strict 48-Hour Policy',
            'description' => 'Strict 48-hour cancellation policy',
            'hours_before' => 48,
            'penalty_percentage' => 100,
            'allow_reschedule' => false,
            'is_active' => true,
        ]);

        // 36 hours before class - past deadline
        $this->assertFalse($policy->canCancelWithoutPenalty(36));
        $this->assertFalse($policy->canReschedule(36));
        $this->assertEquals(75, $policy->calculatePenalty(75));
    }

    public function test_flexible_policy_scenario(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'Flexible 6-Hour Policy',
            'description' => 'Flexible 6-hour cancellation policy',
            'hours_before' => 6,
            'penalty_percentage' => 10,
            'allow_reschedule' => true,
            'reschedule_hours_before' => 2,
            'is_active' => true,
        ]);

        // 4 hours before - can reschedule but will have penalty
        $this->assertFalse($policy->canCancelWithoutPenalty(4));
        $this->assertTrue($policy->canReschedule(4));
        $this->assertEquals(5, $policy->calculatePenalty(50)); // 10% of 50
    }

    public function test_premium_package_policy_scenario(): void
    {
        $policy = CancellationPolicy::create([
            'name' => 'VIP No Penalty',
            'description' => 'VIP no penalty policy',
            'hours_before' => 1,
            'penalty_percentage' => 0,
            'allow_reschedule' => true,
            'reschedule_hours_before' => 1,
            'applicable_to' => ['package_ids' => [100, 101]],
            'is_active' => true,
        ]);

        // VIP user with package 100
        $this->assertTrue($policy->appliesToPackage(100));
        $this->assertTrue($policy->canCancelWithoutPenalty(2));
        $this->assertEquals(0, $policy->calculatePenalty(200));

        // Regular user with package 50
        $this->assertFalse($policy->appliesToPackage(50));
    }
}
