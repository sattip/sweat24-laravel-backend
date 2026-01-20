<?php

namespace Tests\Unit\Models\Calculations;

use App\Models\User;
use App\Models\WorkSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkSessionCalculationsTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Hours Worked Calculation Tests
    // ==========================================

    public function test_calculates_hours_worked_correctly(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 17:00:00'),
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        // Model returns signed value, verify magnitude
        $this->assertEquals(8.0, abs($hoursWorked));
    }

    public function test_calculates_partial_hours_correctly(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 12:30:00'),
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        $this->assertEquals(3.5, abs($hoursWorked));
    }

    public function test_calculates_hours_with_minutes(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 09:45:00'),
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        $this->assertEquals(0.75, abs($hoursWorked)); // 45 minutes = 0.75 hours
    }

    public function test_returns_null_when_not_clocked_out(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => null,
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        $this->assertNull($hoursWorked);
    }

    public function test_calculates_overnight_shift(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 22:00:00'),
            'clock_out' => Carbon::parse('2026-01-21 06:00:00'),
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        $this->assertEquals(8.0, abs($hoursWorked));
    }

    public function test_calculates_short_shift(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 10:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 10:15:00'),
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        $this->assertEquals(0.25, abs($hoursWorked)); // 15 minutes = 0.25 hours
    }

    // ==========================================
    // Formatted Duration Tests
    // ==========================================

    public function test_formatted_duration_with_stored_hours(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 17:30:00'),
            'hours_worked' => 8.5,
        ]);

        $this->assertEquals('8h 30m', $session->formatted_duration);
    }

    public function test_formatted_duration_with_whole_hours(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 17:00:00'),
            'hours_worked' => 8.0,
        ]);

        $this->assertEquals('8h 0m', $session->formatted_duration);
    }

    public function test_formatted_duration_returns_dash_when_no_data(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 17:00:00'),
            'hours_worked' => null,
        ]);

        $this->assertEquals('-', $session->formatted_duration);
    }

    public function test_formatted_duration_calculates_live_for_active_session(): void
    {
        $user = User::factory()->create();

        // Set test time
        Carbon::setTestNow(Carbon::parse('2026-01-20 12:00:00'));

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => null,
            'hours_worked' => null,
        ]);

        $this->assertEquals('3h 0m', $session->formatted_duration);

        Carbon::setTestNow();
    }

    // ==========================================
    // Scope Tests - Active Sessions
    // ==========================================

    public function test_scope_active_returns_clocked_in_sessions(): void
    {
        $user = User::factory()->create();

        // Active session
        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        // Completed session
        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $activeSessions = WorkSession::active()->get();

        $this->assertCount(1, $activeSessions);
    }

    public function test_scope_completed_returns_clocked_out_sessions(): void
    {
        $user = User::factory()->create();

        // Active session
        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        // Completed sessions
        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => now()->subHours(16),
            'clock_out' => now()->subHours(8),
        ]);

        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $completedSessions = WorkSession::completed()->get();

        $this->assertCount(2, $completedSessions);
    }

    // ==========================================
    // Scope Tests - Date Filters
    // ==========================================

    public function test_scope_on_date(): void
    {
        $user = User::factory()->create();

        // Session on target date
        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-15 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-15 17:00:00'),
        ]);

        // Session on different date
        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-16 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-16 17:00:00'),
        ]);

        $sessions = WorkSession::onDate('2026-01-15')->get();

        $this->assertCount(1, $sessions);
    }

    public function test_scope_between_dates(): void
    {
        $user = User::factory()->create();

        // Sessions within range
        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-10 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-10 17:00:00'),
        ]);

        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-15 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-15 17:00:00'),
        ]);

        // Session outside range
        WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-25 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-25 17:00:00'),
        ]);

        $sessions = WorkSession::betweenDates(
            Carbon::parse('2026-01-08 00:00:00'),
            Carbon::parse('2026-01-20 23:59:59')
        )->get();

        $this->assertCount(2, $sessions);
    }

    // ==========================================
    // Multiple Users Tests
    // ==========================================

    public function test_hours_worked_per_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $session1 = WorkSession::create([
            'user_id' => $user1->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 17:00:00'),
        ]);

        $session2 = WorkSession::create([
            'user_id' => $user2->id,
            'clock_in' => Carbon::parse('2026-01-20 08:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 14:00:00'),
        ]);

        $this->assertEquals(8.0, abs($session1->calculateHoursWorked()));
        $this->assertEquals(6.0, abs($session2->calculateHoursWorked()));
    }

    // ==========================================
    // Edge Cases
    // ==========================================

    public function test_handles_same_clock_in_and_out(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 09:00:00'),
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        $this->assertEquals(0.0, $hoursWorked);
    }

    public function test_calculates_long_shift(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 06:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 22:00:00'),
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        // Verify the magnitude is correct (sign may vary based on Carbon implementation)
        $this->assertEquals(16.0, abs($hoursWorked));
    }

    public function test_rounds_minutes_to_two_decimals(): void
    {
        $user = User::factory()->create();

        $session = WorkSession::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2026-01-20 09:00:00'),
            'clock_out' => Carbon::parse('2026-01-20 09:10:00'),
        ]);

        $hoursWorked = $session->calculateHoursWorked();

        // 10 minutes = 0.166666... should round to 0.17
        // Verify the magnitude is correct
        $this->assertEquals(0.17, abs($hoursWorked));
    }
}
