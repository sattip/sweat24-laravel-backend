<?php

namespace Tests\Unit\Models;

use App\Models\PackageNotificationLog;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageNotificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_package_notification_log(): void
    {
        $log = PackageNotificationLog::factory()->create();
        $this->assertDatabaseHas('package_notification_logs', ['id' => $log->id]);
    }

    public function test_belongs_to_user_package(): void
    {
        $userPackage = UserPackage::factory()->create();
        $log = PackageNotificationLog::factory()->create(['user_package_id' => $userPackage->id]);

        $this->assertInstanceOf(UserPackage::class, $log->userPackage);
        $this->assertEquals($userPackage->id, $log->userPackage->id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = PackageNotificationLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_successful_state(): void
    {
        $log = PackageNotificationLog::factory()->successful()->create();

        $this->assertTrue($log->sent_successfully);
        $this->assertNull($log->error_message);
    }

    public function test_failed_state(): void
    {
        $log = PackageNotificationLog::factory()->failed()->create();

        $this->assertFalse($log->sent_successfully);
        $this->assertNotNull($log->error_message);
    }

    public function test_successful_scope(): void
    {
        PackageNotificationLog::factory()->successful()->count(2)->create();
        PackageNotificationLog::factory()->failed()->create();

        $this->assertCount(2, PackageNotificationLog::successful()->get());
    }

    public function test_failed_scope(): void
    {
        PackageNotificationLog::factory()->successful()->create();
        PackageNotificationLog::factory()->failed()->count(2)->create();

        $this->assertCount(2, PackageNotificationLog::failed()->get());
    }

    public function test_by_type_scope(): void
    {
        PackageNotificationLog::factory()->expiryWarning()->count(2)->create();
        PackageNotificationLog::factory()->expired()->create();

        $this->assertCount(2, PackageNotificationLog::byType('expiry_warning')->get());
    }

    public function test_by_channel_scope(): void
    {
        PackageNotificationLog::factory()->viaPush()->count(2)->create();
        PackageNotificationLog::factory()->viaEmail()->create();

        $this->assertCount(2, PackageNotificationLog::byChannel('push')->get());
    }

    public function test_sent_successfully_cast_to_boolean(): void
    {
        $log = PackageNotificationLog::factory()->create();
        $this->assertIsBool($log->sent_successfully);
    }
}
