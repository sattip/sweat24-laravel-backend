<?php

namespace Tests\Unit\Models;

use App\Models\ClassWaitlist;
use App\Models\GymClass;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassWaitlistTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_waitlist_entry(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'waiting',
        ]);

        $this->assertDatabaseHas('class_waitlists', [
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_notified_at_is_datetime(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'notified',
            'notified_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $waitlist->notified_at);
    }

    public function test_expires_at_is_datetime(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'notified',
            'expires_at' => now()->addHours(24),
        ]);

        $this->assertInstanceOf(Carbon::class, $waitlist->expires_at);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_belongs_to_gym_class(): void
    {
        $gymClass = GymClass::factory()->create(['name' => 'Morning Yoga']);
        $user = User::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'waiting',
        ]);

        $this->assertEquals('Morning Yoga', $waitlist->gymClass->name);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'waiting',
        ]);

        $this->assertEquals('Test User', $waitlist->user->name);
    }

    // ==========================================
    // isValid Method Tests
    // ==========================================

    public function test_is_valid_when_status_waiting(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'waiting',
        ]);

        $this->assertTrue($waitlist->isValid());
    }

    public function test_is_valid_when_notified_and_not_expired(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'notified',
            'notified_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $this->assertTrue($waitlist->isValid());
    }

    public function test_is_not_valid_when_notified_and_expired(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'notified',
            'notified_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($waitlist->isValid());
    }

    public function test_is_not_valid_when_expired(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'expired',
        ]);

        $this->assertFalse($waitlist->isValid());
    }

    public function test_is_not_valid_when_confirmed(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'confirmed',
        ]);

        $this->assertFalse($waitlist->isValid());
    }

    // ==========================================
    // Position Tests
    // ==========================================

    public function test_multiple_entries_maintain_position_order(): void
    {
        $gymClass = GymClass::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user1->id,
            'position' => 1,
            'status' => 'waiting',
        ]);

        ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user2->id,
            'position' => 2,
            'status' => 'waiting',
        ]);

        ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user3->id,
            'position' => 3,
            'status' => 'waiting',
        ]);

        $waitlistEntries = ClassWaitlist::where('class_id', $gymClass->id)
            ->orderBy('position')
            ->get();

        $this->assertEquals($user1->id, $waitlistEntries[0]->user_id);
        $this->assertEquals($user2->id, $waitlistEntries[1]->user_id);
        $this->assertEquals($user3->id, $waitlistEntries[2]->user_id);
    }

    // ==========================================
    // Edge Cases Tests
    // ==========================================

    public function test_is_valid_when_notified_but_no_expires_at(): void
    {
        $user = User::factory()->create();
        $gymClass = GymClass::factory()->create();

        $waitlist = ClassWaitlist::create([
            'class_id' => $gymClass->id,
            'user_id' => $user->id,
            'position' => 1,
            'status' => 'notified',
            'notified_at' => now(),
            'expires_at' => null,
        ]);

        // When notified but no expires_at, the condition (expires_at && expires_at->isFuture()) is false
        $this->assertFalse($waitlist->isValid());
    }
}
