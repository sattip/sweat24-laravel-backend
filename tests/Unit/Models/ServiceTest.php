<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Package;
use App\Models\Service;
use App\Models\TrialAppointment;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_service(): void
    {
        $service = Service::create([
            'name' => 'Personal Training',
            'description' => 'One-on-one training sessions',
            'trial_price' => 25.00,
            'is_active' => true,
            'allows_trial' => true,
            'max_trial_per_user' => 2,
        ]);

        $this->assertDatabaseHas('services', [
            'name' => 'Personal Training',
            'trial_price' => 25.00,
        ]);
    }

    public function test_auto_generates_slug_on_create(): void
    {
        $service = Service::create([
            'name' => 'Personal Training Session',
            'is_active' => true,
        ]);

        $this->assertEquals('personal-training-session', $service->slug);
    }

    public function test_updates_slug_when_name_changes(): void
    {
        $service = Service::create([
            'name' => 'Old Name',
            'is_active' => true,
        ]);

        $service->update(['name' => 'New Name']);
        $service->refresh();

        $this->assertEquals('new-name', $service->slug);
    }

    public function test_preserves_custom_slug_when_set(): void
    {
        $service = Service::create([
            'name' => 'Personal Training',
            'slug' => 'custom-slug',
            'is_active' => true,
        ]);

        $this->assertEquals('custom-slug', $service->slug);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_trial_price_is_decimal(): void
    {
        $service = Service::create([
            'name' => 'Test Service',
            'trial_price' => 25.50,
            'is_active' => true,
        ]);

        $this->assertEquals('25.50', $service->trial_price);
    }

    public function test_is_active_is_boolean(): void
    {
        $service = Service::create([
            'name' => 'Test Service',
            'is_active' => 1,
        ]);

        $this->assertTrue($service->is_active);
        $this->assertIsBool($service->is_active);
    }

    public function test_allows_trial_is_boolean(): void
    {
        $service = Service::create([
            'name' => 'Test Service',
            'is_active' => true,
            'allows_trial' => 0,
        ]);

        $this->assertFalse($service->allows_trial);
        $this->assertIsBool($service->allows_trial);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_has_many_gym_classes(): void
    {
        $service = Service::factory()->create();

        GymClass::factory()->count(3)->create([
            'service_id' => $service->id,
        ]);

        $this->assertCount(3, $service->gymClasses);
    }

    public function test_has_many_bookings(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();

        Booking::factory()->count(2)->create([
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $service->bookings);
    }

    public function test_belongs_to_many_packages(): void
    {
        $service = Service::factory()->create();
        $packages = Package::factory()->count(2)->create();

        $service->packages()->attach($packages->pluck('id'));

        $this->assertCount(2, $service->packages);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_active(): void
    {
        Service::factory()->count(2)->create(['is_active' => true]);
        Service::factory()->create(['is_active' => false]);

        $activeServices = Service::active()->get();

        $this->assertCount(2, $activeServices);
    }

    public function test_scope_ordered(): void
    {
        Service::factory()->create(['name' => 'Zebra', 'display_order' => 2]);
        Service::factory()->create(['name' => 'Alpha', 'display_order' => 1]);
        Service::factory()->create(['name' => 'Beta', 'display_order' => 1]);

        $orderedServices = Service::ordered()->get();

        $this->assertEquals('Alpha', $orderedServices->first()->name);
        $this->assertEquals('Zebra', $orderedServices->last()->name);
    }

    // ==========================================
    // Trial Functionality Tests
    // ==========================================

    public function test_allows_trial_for_user_when_trial_disabled(): void
    {
        $service = Service::factory()->create([
            'allows_trial' => false,
        ]);
        $user = User::factory()->create();

        $this->assertFalse($service->allowsTrialForUser($user));
    }

    public function test_allows_trial_for_user_under_limit(): void
    {
        $service = Service::factory()->create([
            'allows_trial' => true,
            'max_trial_per_user' => 2,
        ]);
        $user = User::factory()->create();

        $this->assertTrue($service->allowsTrialForUser($user));
    }

    public function test_get_trial_count_for_user(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();

        // Create some trial appointments
        TrialAppointment::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'pending',
        ]);

        TrialAppointment::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '11:00',
            'status' => 'completed',
        ]);

        // Cancelled ones should not count
        TrialAppointment::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '12:00',
            'status' => 'cancelled',
        ]);

        $this->assertEquals(2, $service->getTrialCountForUser($user));
    }

    public function test_denies_trial_when_limit_reached(): void
    {
        $service = Service::factory()->create([
            'allows_trial' => true,
            'max_trial_per_user' => 1,
        ]);
        $user = User::factory()->create();

        // Create one trial appointment
        TrialAppointment::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'pending',
        ]);

        $this->assertFalse($service->allowsTrialForUser($user));
    }

    // ==========================================
    // Default Values Tests
    // ==========================================

    public function test_default_values(): void
    {
        $service = Service::create([
            'name' => 'Test Service',
        ]);

        // Refresh to get database defaults
        $service->refresh();

        $this->assertTrue($service->is_active);
        $this->assertTrue($service->allows_trial);
        $this->assertEquals(1, $service->max_trial_per_user);
        $this->assertEquals(0, $service->display_order);
    }
}
