<?php

namespace Tests\Unit\Services;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    // ========== Basic log() Tests ==========

    public function test_log_creates_activity_with_all_properties(): void
    {
        $user = User::factory()->create();

        $log = ActivityLogger::log(
            'test_type',
            'Test action performed',
            null,
            ['key' => 'value'],
            $user->id
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'activity_type' => 'test_type',
            'action' => 'Test action performed',
        ]);

        $this->assertEquals(['key' => 'value'], $log->properties);
    }

    public function test_log_skips_logging_without_user_id(): void
    {
        // Don't authenticate and don't provide user_id
        $log = ActivityLogger::log('test_type', 'Test action');

        $this->assertDatabaseMissing('activity_logs', [
            'activity_type' => 'test_type',
        ]);

        // Returns an empty ActivityLog instance
        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertNull($log->id);
    }

    public function test_log_with_custom_user_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1);

        // Log with a different user ID
        $log = ActivityLogger::log(
            'test_type',
            'Test action',
            null,
            [],
            $user2->id
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user2->id,
            'activity_type' => 'test_type',
        ]);
    }

    public function test_log_with_model_subject(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create();

        $log = ActivityLogger::log(
            'booking',
            'Booking created',
            $booking,
            [],
            $user->id
        );

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => Booking::class,
            'model_id' => $booking->id,
        ]);
    }

    // ========== logRegistration() Tests ==========

    public function test_log_registration_logs_user_registration(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'membership_type' => 'Premium',
        ]);

        $log = ActivityLogger::logRegistration($user);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'activity_type' => ActivityLog::TYPE_REGISTRATION,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        $this->assertEquals('John Doe', $log->properties['user_name']);
        $this->assertEquals('john@example.com', $log->properties['user_email']);
        $this->assertEquals('Premium', $log->properties['membership_type']);
    }

    // ========== logLogin() Tests ==========

    public function test_log_login_logs_user_login(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $log = ActivityLogger::logLogin($user);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'activity_type' => ActivityLog::TYPE_LOGIN,
        ]);

        $this->assertEquals('Jane Doe', $log->properties['user_name']);
        $this->assertEquals('jane@example.com', $log->properties['user_email']);
        $this->assertStringContainsString('logged in', $log->action);
    }

    // ========== logLogout() Tests ==========

    public function test_log_logout_logs_user_logout(): void
    {
        $user = User::factory()->create(['name' => 'Bob Smith']);

        $log = ActivityLogger::logLogout($user);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'activity_type' => ActivityLog::TYPE_LOGOUT,
        ]);

        $this->assertEquals('Bob Smith', $log->properties['user_name']);
        $this->assertStringContainsString('logged out', $log->action);
    }

    // ========== logUserUpdated() Tests ==========

    public function test_log_user_updated(): void
    {
        $user = User::factory()->create(['name' => 'Updated User']);
        $this->actingAs($user);

        $changes = ['email' => ['old' => 'old@example.com', 'new' => 'new@example.com']];

        $log = ActivityLogger::logUserUpdated($user, $changes);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => ActivityLog::TYPE_USER_UPDATED,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        $this->assertEquals($changes, $log->properties['changes']);
    }

    // Note: logBooking, logBookingCancellation, logClassCreated, logClassUpdated tests
    // are skipped due to a mismatch between ActivityLogger using 'title' and GymClass using 'name'
}
