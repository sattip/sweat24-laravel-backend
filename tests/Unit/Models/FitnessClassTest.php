<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\FitnessClass;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitnessClassTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_fitness_class(): void
    {
        $store = Store::factory()->create();

        $class = FitnessClass::create([
            'name' => 'Morning Yoga',
            'type' => 'yoga',
            'instructor' => 'John Smith',
            'date' => '2026-01-25',
            'time' => '09:00',
            'duration' => 60,
            'max_participants' => 20,
            'current_participants' => 0,
            'store_id' => $store->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('fitness_classes', [
            'name' => 'Morning Yoga',
            'type' => 'yoga',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_date_is_date_cast(): void
    {
        $class = FitnessClass::factory()->create([
            'date' => '2026-01-25',
        ]);

        $this->assertInstanceOf(Carbon::class, $class->date);
    }

    public function test_duration_is_integer(): void
    {
        $class = FitnessClass::factory()->create([
            'duration' => 60,
        ]);

        $this->assertIsInt($class->duration);
    }

    public function test_max_participants_is_integer(): void
    {
        $class = FitnessClass::factory()->create([
            'max_participants' => 20,
        ]);

        $this->assertIsInt($class->max_participants);
    }

    public function test_is_recurring_is_boolean(): void
    {
        $class = FitnessClass::factory()->create([
            'is_recurring' => true,
        ]);

        $this->assertIsBool($class->is_recurring);
        $this->assertTrue($class->is_recurring);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_store(): void
    {
        $store = Store::factory()->create(['name' => 'Main Store']);
        $class = FitnessClass::factory()->create(['store_id' => $store->id]);

        $this->assertEquals('Main Store', $class->store->name);
    }

    public function test_has_many_bookings(): void
    {
        $class = FitnessClass::factory()->create();
        $user = User::factory()->create();

        Booking::factory()->count(3)->create([
            'class_id' => $class->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $class->bookings);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_active(): void
    {
        FitnessClass::factory()->count(2)->create(['status' => 'active']);
        FitnessClass::factory()->create(['status' => 'cancelled']);

        $activeClasses = FitnessClass::active()->get();

        $this->assertCount(2, $activeClasses);
    }

    public function test_scope_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 10:00:00'));

        FitnessClass::factory()->create([
            'date' => '2026-01-21',
            'time' => '09:00',
        ]);

        FitnessClass::factory()->create([
            'date' => '2026-01-19',
            'time' => '09:00',
        ]);

        $upcomingClasses = FitnessClass::upcoming()->get();

        $this->assertCount(1, $upcomingClasses);
        $this->assertEquals('2026-01-21', $upcomingClasses->first()->date->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_scope_by_date(): void
    {
        FitnessClass::factory()->create(['date' => '2026-01-25']);
        FitnessClass::factory()->create(['date' => '2026-01-25']);
        FitnessClass::factory()->create(['date' => '2026-01-26']);

        $classesByDate = FitnessClass::byDate('2026-01-25')->get();

        $this->assertCount(2, $classesByDate);
    }

    public function test_scope_by_instructor(): void
    {
        FitnessClass::factory()->create(['instructor' => 'John Smith']);
        FitnessClass::factory()->create(['instructor' => 'John Smith']);
        FitnessClass::factory()->create(['instructor' => 'Jane Doe']);

        $classesByInstructor = FitnessClass::byInstructor('John Smith')->get();

        $this->assertCount(2, $classesByInstructor);
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    public function test_available_spots_accessor(): void
    {
        $class = FitnessClass::factory()->create([
            'max_participants' => 20,
            'current_participants' => 15,
        ]);

        $this->assertEquals(5, $class->available_spots);
    }

    public function test_is_full_accessor_when_full(): void
    {
        $class = FitnessClass::factory()->create([
            'max_participants' => 20,
            'current_participants' => 20,
        ]);

        $this->assertTrue($class->is_full);
    }

    public function test_is_full_accessor_when_not_full(): void
    {
        $class = FitnessClass::factory()->create([
            'max_participants' => 20,
            'current_participants' => 15,
        ]);

        $this->assertFalse($class->is_full);
    }

    public function test_is_past_accessor(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $pastClass = FitnessClass::factory()->create([
            'date' => '2026-01-19',
            'time' => '09:00',
        ]);

        $futureClass = FitnessClass::factory()->create([
            'date' => '2026-01-21',
            'time' => '09:00',
        ]);

        $this->assertTrue($pastClass->is_past);
        $this->assertFalse($futureClass->is_past);

        Carbon::setTestNow();
    }

    public function test_display_location_accessor_with_store(): void
    {
        $store = Store::factory()->create(['name' => 'Downtown Gym']);
        $class = FitnessClass::factory()->create([
            'store_id' => $store->id,
            'location' => null,
        ]);

        $this->assertEquals('Downtown Gym', $class->display_location);
    }

    public function test_display_location_accessor_with_manual_location(): void
    {
        $class = FitnessClass::factory()->create([
            'store_id' => null,
            'location' => 'Outdoor Park',
        ]);

        $this->assertEquals('Outdoor Park', $class->display_location);
    }

    public function test_display_location_accessor_with_no_location(): void
    {
        $class = FitnessClass::factory()->create([
            'store_id' => null,
            'location' => null,
        ]);

        // Greek text for "Not set"
        $this->assertStringContainsString('έχει οριστεί', $class->display_location);
    }

    // ==========================================
    // Current Participants Dynamic Count Tests
    // ==========================================

    public function test_current_participants_from_bookings(): void
    {
        $class = FitnessClass::factory()->create([
            'current_participants' => 0,
        ]);
        $user = User::factory()->create();

        Booking::factory()->count(3)->create([
            'class_id' => $class->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);

        // Cancelled booking should not count
        Booking::factory()->create([
            'class_id' => $class->id,
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $class->load('bookings');

        $this->assertEquals(3, $class->current_participants);
    }

    // ==========================================
    // Recurring Class Tests
    // ==========================================

    public function test_recurring_class_attributes(): void
    {
        $class = FitnessClass::factory()->create([
            'is_recurring' => true,
            'recurrence_pattern' => 'weekly',
            'recurrence_interval' => 1,
            'recurrence_end_date' => '2026-03-31',
        ]);

        $this->assertTrue($class->is_recurring);
        $this->assertEquals('weekly', $class->recurrence_pattern);
        $this->assertEquals(1, $class->recurrence_interval);
    }
}
