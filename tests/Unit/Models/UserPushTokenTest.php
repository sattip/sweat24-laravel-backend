<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserPushToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPushTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user_push_token(): void
    {
        $token = UserPushToken::factory()->create();

        $this->assertDatabaseHas('user_push_tokens', [
            'id' => $token->id,
            'user_id' => $token->user_id,
        ]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $token = UserPushToken::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $token->user);
        $this->assertEquals($user->id, $token->user->id);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $token = UserPushToken::factory()->create(['is_active' => true]);

        $this->assertIsBool($token->is_active);
        $this->assertTrue($token->is_active);
    }

    public function test_active_state(): void
    {
        $token = UserPushToken::factory()->active()->create();

        $this->assertTrue($token->is_active);
    }

    public function test_inactive_state(): void
    {
        $token = UserPushToken::factory()->inactive()->create();

        $this->assertFalse($token->is_active);
    }

    public function test_ios_state(): void
    {
        $token = UserPushToken::factory()->ios()->create();

        $this->assertEquals('ios', $token->platform);
    }

    public function test_android_state(): void
    {
        $token = UserPushToken::factory()->android()->create();

        $this->assertEquals('android', $token->platform);
    }

    public function test_web_state(): void
    {
        $token = UserPushToken::factory()->web()->create();

        $this->assertEquals('web', $token->platform);
    }

    public function test_save_token_creates_new_token(): void
    {
        $user = User::factory()->create();
        
        $token = UserPushToken::saveToken($user->id, 'test-token-123', 'ios');

        $this->assertDatabaseHas('user_push_tokens', [
            'user_id' => $user->id,
            'token' => 'test-token-123',
            'platform' => 'ios',
            'is_active' => true,
        ]);
    }

    public function test_save_token_updates_existing_token(): void
    {
        $user = User::factory()->create();
        
        // Create initial token
        UserPushToken::saveToken($user->id, 'old-token', 'ios');
        
        // Update with new token
        UserPushToken::saveToken($user->id, 'new-token', 'ios');

        // Should only have one token for this platform
        $this->assertEquals(1, UserPushToken::where('user_id', $user->id)->where('platform', 'ios')->count());
        
        // Should have the new token
        $this->assertDatabaseHas('user_push_tokens', [
            'user_id' => $user->id,
            'token' => 'new-token',
            'platform' => 'ios',
        ]);
    }

    public function test_get_active_tokens_for_user(): void
    {
        $user = User::factory()->create();
        UserPushToken::factory()->active()->create(['user_id' => $user->id]);
        UserPushToken::factory()->active()->create(['user_id' => $user->id, 'platform' => 'android']);
        UserPushToken::factory()->inactive()->create(['user_id' => $user->id, 'platform' => 'web']);

        $activeTokens = UserPushToken::getActiveTokensForUser($user->id);

        $this->assertCount(2, $activeTokens);
    }

    public function test_deactivate(): void
    {
        $token = UserPushToken::factory()->active()->create();

        $token->deactivate();

        $this->assertFalse($token->fresh()->is_active);
    }
}
