<?php

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_activity_log(): void
    {
        $log = ActivityLog::factory()->create();
        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_login_state(): void
    {
        $log = ActivityLog::factory()->login()->create();
        $this->assertEquals(ActivityLog::TYPE_LOGIN, $log->activity_type);
    }

    public function test_logout_state(): void
    {
        $log = ActivityLog::factory()->logout()->create();
        $this->assertEquals(ActivityLog::TYPE_LOGOUT, $log->activity_type);
    }

    public function test_registration_state(): void
    {
        $log = ActivityLog::factory()->registration()->create();
        $this->assertEquals(ActivityLog::TYPE_REGISTRATION, $log->activity_type);
    }

    public function test_booking_state(): void
    {
        $log = ActivityLog::factory()->booking()->create();
        $this->assertEquals(ActivityLog::TYPE_BOOKING, $log->activity_type);
    }

    public function test_booking_cancellation_state(): void
    {
        $log = ActivityLog::factory()->bookingCancellation()->create();
        $this->assertEquals(ActivityLog::TYPE_BOOKING_CANCELLATION, $log->activity_type);
    }

    public function test_payment_state(): void
    {
        $log = ActivityLog::factory()->payment()->create();
        $this->assertEquals(ActivityLog::TYPE_PAYMENT, $log->activity_type);
    }

    public function test_package_purchase_state(): void
    {
        $log = ActivityLog::factory()->packagePurchase()->create();
        $this->assertEquals(ActivityLog::TYPE_PACKAGE_PURCHASE, $log->activity_type);
    }

    public function test_with_properties(): void
    {
        $properties = ['amount' => 100, 'package' => 'Premium'];
        $log = ActivityLog::factory()->withProperties($properties)->create();

        $this->assertEquals($properties, $log->properties);
    }

    public function test_for_model(): void
    {
        $log = ActivityLog::factory()->forModel('App\\Models\\User', 123)->create();

        $this->assertEquals('App\\Models\\User', $log->model_type);
        $this->assertEquals(123, $log->model_id);
    }

    public function test_activity_type_label_attribute(): void
    {
        $log = ActivityLog::factory()->login()->create();
        $this->assertEquals('User Login', $log->activity_type_label);
    }

    public function test_activity_icon_attribute(): void
    {
        $log = ActivityLog::factory()->login()->create();
        $this->assertEquals('fas fa-sign-in-alt', $log->activity_icon);
    }

    public function test_activity_color_attribute(): void
    {
        $log = ActivityLog::factory()->login()->create();
        $this->assertEquals('green', $log->activity_color);
    }

    public function test_of_type_scope(): void
    {
        ActivityLog::factory()->login()->count(2)->create();
        ActivityLog::factory()->logout()->create();

        $loginLogs = ActivityLog::ofType(ActivityLog::TYPE_LOGIN)->get();
        $this->assertCount(2, $loginLogs);
    }

    public function test_by_user_scope(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory()->count(2)->create(['user_id' => $user->id]);
        ActivityLog::factory()->create();

        $userLogs = ActivityLog::byUser($user->id)->get();
        $this->assertCount(2, $userLogs);
    }

    public function test_type_constants(): void
    {
        $this->assertEquals('login', ActivityLog::TYPE_LOGIN);
        $this->assertEquals('logout', ActivityLog::TYPE_LOGOUT);
        $this->assertEquals('registration', ActivityLog::TYPE_REGISTRATION);
        $this->assertEquals('booking', ActivityLog::TYPE_BOOKING);
        $this->assertEquals('payment', ActivityLog::TYPE_PAYMENT);
    }
}
