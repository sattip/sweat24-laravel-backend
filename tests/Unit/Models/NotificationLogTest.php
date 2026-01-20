<?php

namespace Tests\Unit\Models;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification_log(): void
    {
        $log = NotificationLog::factory()->create();
        $this->assertDatabaseHas('notification_logs', ['id' => $log->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = NotificationLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_success_state(): void
    {
        $log = NotificationLog::factory()->success()->create();

        $this->assertEquals('success', $log->status);
        $this->assertNull($log->error_message);
    }

    public function test_failed_state(): void
    {
        $log = NotificationLog::factory()->failed()->create();

        $this->assertEquals('failed', $log->status);
        $this->assertNotNull($log->error_message);
    }

    public function test_invalid_token_state(): void
    {
        $log = NotificationLog::factory()->invalidToken()->create();

        $this->assertEquals('invalid_token', $log->status);
        $this->assertNotNull($log->error_message);
    }

    public function test_ios_state(): void
    {
        $log = NotificationLog::factory()->ios()->create();
        $this->assertEquals('ios', $log->platform);
    }

    public function test_android_state(): void
    {
        $log = NotificationLog::factory()->android()->create();
        $this->assertEquals('android', $log->platform);
    }

    public function test_web_state(): void
    {
        $log = NotificationLog::factory()->web()->create();
        $this->assertEquals('web', $log->platform);
    }

    public function test_response_data_cast_to_array(): void
    {
        $log = NotificationLog::factory()->create(['response_data' => ['key' => 'value']]);
        $this->assertIsArray($log->response_data);
    }
}
