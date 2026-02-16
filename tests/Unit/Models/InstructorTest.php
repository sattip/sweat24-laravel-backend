<?php

namespace Tests\Unit\Models;

use App\Models\BookingRequest;
use App\Models\Instructor;
use App\Models\PayrollAgreement;
use App\Models\Store;
use App\Models\User;
use App\Models\WorkTimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_instructor(): void
    {
        $instructor = Instructor::factory()->create([
            'name' => 'John Smith',
            'title' => 'Senior Trainer',
        ]);

        $this->assertDatabaseHas('instructors', [
            'name' => 'John Smith',
            'title' => 'Senior Trainer',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_specialties_is_json_array(): void
    {
        $instructor = Instructor::factory()->create([
            'specialties' => ['Weight Training', 'HIIT', 'Yoga'],
        ]);

        $this->assertIsArray($instructor->specialties);
        $this->assertContains('HIIT', $instructor->specialties);
    }

    public function test_certifications_is_json_array(): void
    {
        $instructor = Instructor::factory()->create([
            'certifications' => ['ACE Certified', 'NASM CPT'],
        ]);

        $this->assertIsArray($instructor->certifications);
        $this->assertContains('ACE Certified', $instructor->certifications);
    }

    public function test_services_is_json_array(): void
    {
        $instructor = Instructor::factory()->create([
            'services' => ['Personal Training', 'Group Classes'],
        ]);

        $this->assertIsArray($instructor->services);
    }

    public function test_hourly_rate_is_decimal(): void
    {
        $instructor = Instructor::factory()->create([
            'hourly_rate' => 50.75,
        ]);

        $this->assertEquals('50.75', $instructor->hourly_rate);
    }

    public function test_commission_rate_is_decimal(): void
    {
        $instructor = Instructor::factory()->create([
            'commission_rate' => 0.1250,
        ]);

        $this->assertEquals('0.1250', $instructor->commission_rate);
    }

    public function test_join_date_is_date(): void
    {
        $instructor = Instructor::factory()->create([
            'join_date' => '2024-01-15',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $instructor->join_date);
        $this->assertEquals('2024-01-15', $instructor->join_date->format('Y-m-d'));
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_store(): void
    {
        $store = Store::factory()->create(['name' => 'Main Store']);
        $instructor = Instructor::factory()->create(['store_id' => $store->id]);

        $this->assertEquals('Main Store', $instructor->store->name);
    }

    public function test_has_many_booking_requests(): void
    {
        $instructor = Instructor::factory()->create();

        BookingRequest::factory()->create([
            'instructor_id' => $instructor->id,
        ]);

        $this->assertCount(1, $instructor->bookingRequests);
    }

    public function test_has_many_work_time_entries(): void
    {
        $instructor = Instructor::factory()->create();

        // Use DB::table since model $fillable doesn't include all required fields
        \DB::table('work_time_entries')->insert([
            'instructor_id' => $instructor->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'hours_worked' => 8.0,
            'description' => 'Regular shift',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertCount(1, $instructor->workTimeEntries);
    }

    public function test_has_many_payroll_agreements(): void
    {
        $instructor = Instructor::factory()->create();

        // Use DB::table since model $fillable doesn't include all required fields
        \DB::table('payroll_agreements')->insert([
            'instructor_id' => $instructor->id,
            'instructor_name' => $instructor->name,
            'type' => 'hourly_rate',
            'amount' => 50.00,
            'description' => 'Hourly rate agreement',
            'start_date' => now()->toDateString(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertCount(1, $instructor->payrollAgreements);
    }

    // ==========================================
    // Optional Fields Tests
    // ==========================================

    public function test_optional_fields_can_be_null(): void
    {
        $instructor = Instructor::factory()->create([
            'image_url' => null,
            'bio' => null,
            'experience' => null,
        ]);

        $this->assertNull($instructor->image_url);
        $this->assertNull($instructor->bio);
        $this->assertNull($instructor->experience);
    }

    // ==========================================
    // Financial Fields Tests
    // ==========================================

    public function test_tracks_financial_metrics(): void
    {
        $instructor = Instructor::factory()->create([
            'total_revenue' => 15000.50,
            'completed_sessions' => 150,
        ]);

        $this->assertEquals('15000.50', $instructor->total_revenue);
        $this->assertEquals(150, $instructor->completed_sessions);
    }

    public function test_contract_types(): void
    {
        $instructor = Instructor::factory()->create([
            'contract_type' => 'hourly',
        ]);

        $this->assertEquals('hourly', $instructor->contract_type);
    }
}
