<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * NOTE: These tests are for Session features that are not yet implemented.
 * The App\Models\Session class and SessionController do not exist in the codebase.
 * The /api/v1/sessions routes do not exist.
 *
 * Consider using:
 * - /api/v1/training-sessions for workout/training sessions
 * - /api/v1/work-sessions for trainer work time tracking
 *
 * These tests are skipped until the Session feature is implemented.
 */
class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_user_can_view_own_sessions()
    {
        $this->markTestSkipped('Feature not implemented: App\Models\Session class and /api/v1/sessions endpoint do not exist');
    }

    public function test_admin_can_view_all_sessions()
    {
        $this->markTestSkipped('Feature not implemented: App\Models\Session class and /api/v1/sessions endpoint do not exist');
    }

    public function test_admin_can_create_session()
    {
        $this->markTestSkipped('Feature not implemented: App\Models\Session class and /api/v1/sessions endpoint do not exist');
    }

    public function test_session_deduction_on_completion()
    {
        $this->markTestSkipped('Feature not implemented: App\Models\Session class and /api/v1/sessions endpoint do not exist');
    }

    public function test_user_can_view_own_session_details()
    {
        $this->markTestSkipped('Feature not implemented: App\Models\Session class and /api/v1/sessions endpoint do not exist');
    }

    public function test_user_cannot_view_other_user_session()
    {
        $this->markTestSkipped('Feature not implemented: App\Models\Session class and /api/v1/sessions endpoint do not exist');
    }

    public function test_admin_can_update_session()
    {
        $this->markTestSkipped('Feature not implemented: App\Models\Session class and /api/v1/sessions endpoint do not exist');
    }

    public function test_admin_can_delete_session()
    {
        $this->markTestSkipped('Feature not implemented: App\Models\Session class and /api/v1/sessions endpoint do not exist');
    }
}
