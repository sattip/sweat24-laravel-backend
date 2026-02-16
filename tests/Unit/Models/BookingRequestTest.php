<?php

namespace Tests\Unit\Models;

use App\Models\BookingRequest;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRequestTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_booking_request(): void
    {
        $request = BookingRequest::factory()->create([
            'client_name' => 'John Doe',
            'service_type' => 'personal',
        ]);

        $this->assertDatabaseHas('booking_requests', [
            'client_name' => 'John Doe',
            'status' => 'pending',
        ]);
    }

    // ==========================================
    // Constants Tests
    // ==========================================

    public function test_status_constants(): void
    {
        $this->assertEquals('pending', BookingRequest::STATUS_PENDING);
        $this->assertEquals('confirmed', BookingRequest::STATUS_CONFIRMED);
        $this->assertEquals('rejected', BookingRequest::STATUS_REJECTED);
        $this->assertEquals('cancelled', BookingRequest::STATUS_CANCELLED);
        $this->assertEquals('completed', BookingRequest::STATUS_COMPLETED);
    }

    public function test_service_type_constants(): void
    {
        $this->assertEquals('ems', BookingRequest::SERVICE_EMS);
        $this->assertEquals('personal', BookingRequest::SERVICE_PERSONAL);
    }

    public function test_duration_constants(): void
    {
        $this->assertEquals(20, BookingRequest::EMS_DURATION_MINUTES);
        $this->assertEquals(50, BookingRequest::PERSONAL_DURATION_MINUTES);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_preferred_time_slots_is_array(): void
    {
        $request = BookingRequest::factory()->create([
            'preferred_time_slots' => ['09:00', '10:00', '14:00'],
        ]);

        $this->assertIsArray($request->preferred_time_slots);
        $this->assertContains('09:00', $request->preferred_time_slots);
    }

    public function test_confirmed_date_is_date(): void
    {
        $request = BookingRequest::factory()->confirmed()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $request->confirmed_date);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $request = BookingRequest::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('Test User', $request->user->name);
    }

    public function test_belongs_to_instructor(): void
    {
        $instructor = Instructor::factory()->create(['name' => 'John Trainer']);
        $request = BookingRequest::factory()->create(['instructor_id' => $instructor->id]);

        $this->assertEquals('John Trainer', $request->instructor->name);
    }

    public function test_belongs_to_processed_by(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User']);
        $request = BookingRequest::factory()->confirmed()->create([
            'processed_by' => $admin->id,
        ]);

        $this->assertEquals('Admin User', $request->processedBy->name);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_pending(): void
    {
        BookingRequest::factory()->pending()->create(['client_name' => 'Test 1']);
        BookingRequest::factory()->confirmed()->create(['client_name' => 'Test 2']);

        $pendingRequests = BookingRequest::pending()->get();

        $this->assertCount(1, $pendingRequests);
        $this->assertEquals('Test 1', $pendingRequests->first()->client_name);
    }

    public function test_scope_confirmed(): void
    {
        BookingRequest::factory()->pending()->create(['client_name' => 'Test 1']);
        BookingRequest::factory()->confirmed()->create(['client_name' => 'Test 2']);

        $confirmedRequests = BookingRequest::confirmed()->get();

        $this->assertCount(1, $confirmedRequests);
        $this->assertEquals('Test 2', $confirmedRequests->first()->client_name);
    }

    // ==========================================
    // Status Methods Tests
    // ==========================================

    public function test_is_pending(): void
    {
        $request = BookingRequest::factory()->pending()->create();

        $this->assertTrue($request->isPending());
    }

    public function test_is_confirmed(): void
    {
        $request = BookingRequest::factory()->confirmed()->create();

        $this->assertTrue($request->isConfirmed());
    }

    // ==========================================
    // Action Methods Tests
    // ==========================================

    public function test_cancel_updates_status(): void
    {
        $request = BookingRequest::factory()->pending()->create();

        $request->cancel('Changed my mind');

        $this->assertEquals('cancelled', $request->fresh()->status);
        $this->assertEquals('Changed my mind', $request->fresh()->rejection_reason);
    }

    public function test_mark_as_completed(): void
    {
        $request = BookingRequest::factory()->confirmed()->create();

        $request->markAsCompleted();

        $this->assertEquals('completed', $request->fresh()->status);
    }

    // ==========================================
    // Static Methods Tests
    // ==========================================

    public function test_get_service_types(): void
    {
        $types = BookingRequest::getServiceTypes();

        $this->assertArrayHasKey('ems', $types);
        $this->assertArrayHasKey('personal', $types);
        $this->assertEquals('EMS Training', $types['ems']);
        $this->assertEquals('Personal Training', $types['personal']);
    }

    public function test_get_statuses(): void
    {
        $statuses = BookingRequest::getStatuses();

        $this->assertArrayHasKey('pending', $statuses);
        $this->assertArrayHasKey('confirmed', $statuses);
        $this->assertArrayHasKey('rejected', $statuses);
        $this->assertArrayHasKey('cancelled', $statuses);
        $this->assertArrayHasKey('completed', $statuses);
    }

    // ==========================================
    // Duration Methods Tests
    // ==========================================

    public function test_get_duration_minutes_for_ems(): void
    {
        $request = BookingRequest::factory()->ems()->create();

        $this->assertEquals(20, $request->getDurationMinutes());
    }

    public function test_get_duration_minutes_for_personal(): void
    {
        $request = BookingRequest::factory()->personal()->create();

        $this->assertEquals(50, $request->getDurationMinutes());
    }
}
