<?php

namespace Tests\Unit\Policies;

use App\Models\Booking;
use App\Models\GymClass;
use App\Models\User;
use App\Policies\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected BookingPolicy $policy;
    protected User $admin;
    protected User $trainer;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BookingPolicy();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->trainer = User::factory()->create(['role' => 'trainer']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    /** @test */
    public function admin_can_view_any_bookings(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    /** @test */
    public function trainer_can_view_any_bookings(): void
    {
        $this->assertTrue($this->policy->viewAny($this->trainer));
    }

    /** @test */
    public function member_cannot_view_any_bookings(): void
    {
        $this->assertFalse($this->policy->viewAny($this->member));
    }

    /** @test */
    public function admin_can_view_any_booking(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->member->id]);
        $this->assertTrue($this->policy->view($this->admin, $booking));
    }

    /** @test */
    public function trainer_can_view_any_booking(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->member->id]);
        $this->assertTrue($this->policy->view($this->trainer, $booking));
    }

    /** @test */
    public function member_can_view_own_booking(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->member->id]);
        $this->assertTrue($this->policy->view($this->member, $booking));
    }

    /** @test */
    public function member_cannot_view_other_users_booking(): void
    {
        $otherMember = User::factory()->create(['role' => 'member']);
        $booking = Booking::factory()->create(['user_id' => $otherMember->id]);
        $this->assertFalse($this->policy->view($this->member, $booking));
    }

    /** @test */
    public function any_user_can_create_booking(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
        $this->assertTrue($this->policy->create($this->trainer));
        $this->assertTrue($this->policy->create($this->member));
    }

    /** @test */
    public function admin_can_delete_booking(): void
    {
        $booking = Booking::factory()->create();
        $this->assertTrue($this->policy->delete($this->admin, $booking));
    }

    /** @test */
    public function trainer_cannot_delete_booking(): void
    {
        $booking = Booking::factory()->create();
        $this->assertFalse($this->policy->delete($this->trainer, $booking));
    }

    /** @test */
    public function member_cannot_delete_booking(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->member->id]);
        $this->assertFalse($this->policy->delete($this->member, $booking));
    }

    /** @test */
    public function admin_can_mark_attendance(): void
    {
        $booking = Booking::factory()->create();
        $this->assertTrue($this->policy->markAttended($this->admin, $booking));
    }

    /** @test */
    public function trainer_can_mark_attendance(): void
    {
        $booking = Booking::factory()->create();
        $this->assertTrue($this->policy->markAttended($this->trainer, $booking));
    }

    /** @test */
    public function member_cannot_mark_attendance(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->member->id]);
        $this->assertFalse($this->policy->markAttended($this->member, $booking));
    }

    /** @test */
    public function member_can_cancel_own_booking(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->member->id]);
        $this->assertTrue($this->policy->cancel($this->member, $booking));
    }

    /** @test */
    public function member_cannot_cancel_other_users_booking(): void
    {
        $otherMember = User::factory()->create(['role' => 'member']);
        $booking = Booking::factory()->create(['user_id' => $otherMember->id]);
        $this->assertFalse($this->policy->cancel($this->member, $booking));
    }
}
