<?php

namespace Tests\Unit\Policies;

use App\Models\GymClass;
use App\Models\User;
use App\Policies\GymClassPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymClassPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected GymClassPolicy $policy;
    protected User $admin;
    protected User $trainer;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new GymClassPolicy();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    /** @test */
    public function anyone_can_view_classes(): void
    {
        $this->assertTrue($this->policy->viewAny(null));
        $this->assertTrue($this->policy->viewAny($this->member));
    }

    /** @test */
    public function anyone_can_view_a_class(): void
    {
        $class = GymClass::factory()->create();
        $this->assertTrue($this->policy->view(null, $class));
        $this->assertTrue($this->policy->view($this->member, $class));
    }

    /** @test */
    public function admin_can_create_classes(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    /** @test */
    public function trainer_can_create_classes(): void
    {
        $this->assertTrue($this->policy->create($this->trainer));
    }

    /** @test */
    public function member_cannot_create_classes(): void
    {
        $this->assertFalse($this->policy->create($this->member));
    }

    /** @test */
    public function admin_can_update_classes(): void
    {
        $class = GymClass::factory()->create();
        $this->assertTrue($this->policy->update($this->admin, $class));
    }

    /** @test */
    public function trainer_can_update_classes(): void
    {
        $class = GymClass::factory()->create();
        $this->assertTrue($this->policy->update($this->trainer, $class));
    }

    /** @test */
    public function member_cannot_update_classes(): void
    {
        $class = GymClass::factory()->create();
        $this->assertFalse($this->policy->update($this->member, $class));
    }

    /** @test */
    public function only_admin_can_delete_classes(): void
    {
        $class = GymClass::factory()->create();
        $this->assertTrue($this->policy->delete($this->admin, $class));
        $this->assertFalse($this->policy->delete($this->trainer, $class));
        $this->assertFalse($this->policy->delete($this->member, $class));
    }

    /** @test */
    public function admin_can_view_class_attendees(): void
    {
        $class = GymClass::factory()->create();
        $this->assertTrue($this->policy->viewAttendees($this->admin, $class));
    }

    /** @test */
    public function trainer_can_view_class_attendees(): void
    {
        $class = GymClass::factory()->create();
        $this->assertTrue($this->policy->viewAttendees($this->trainer, $class));
    }

    /** @test */
    public function member_cannot_view_class_attendees(): void
    {
        $class = GymClass::factory()->create();
        $this->assertFalse($this->policy->viewAttendees($this->member, $class));
    }

    /** @test */
    public function admin_can_cancel_classes(): void
    {
        $class = GymClass::factory()->create();
        $this->assertTrue($this->policy->cancel($this->admin, $class));
    }

    /** @test */
    public function trainer_can_cancel_classes(): void
    {
        $class = GymClass::factory()->create();
        $this->assertTrue($this->policy->cancel($this->trainer, $class));
    }

    /** @test */
    public function member_cannot_cancel_classes(): void
    {
        $class = GymClass::factory()->create();
        $this->assertFalse($this->policy->cancel($this->member, $class));
    }
}
