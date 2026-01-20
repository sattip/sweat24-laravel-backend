<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_fillable_attributes(): void
    {
        $user = new User();

        $this->assertContains('name', $user->getFillable());
        $this->assertContains('email', $user->getFillable());
    }

    public function test_user_has_hidden_attributes(): void
    {
        $user = new User();

        $this->assertContains('password', $user->getHidden());
        $this->assertContains('remember_token', $user->getHidden());
    }

    public function test_user_can_be_created_with_factory(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_has_bookings_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->bookings());
    }

    public function test_user_has_user_packages_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->userPackages());
    }

    public function test_user_email_must_be_unique(): void
    {
        $email = 'test@example.com';
        User::factory()->create(['email' => $email]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => $email]);
    }

    // ========== isSuperAdmin() Tests ==========

    public function test_is_super_admin_returns_false_for_non_admin_user(): void
    {
        config(['auth.super_admins' => ['super@example.com']]);

        $member = User::factory()->create([
            'role' => 'member',
            'email' => 'super@example.com',
        ]);

        $this->assertFalse($member->isSuperAdmin());
    }

    public function test_is_super_admin_returns_false_for_trainer(): void
    {
        config(['auth.super_admins' => ['super@example.com']]);

        $trainer = User::factory()->create([
            'role' => 'trainer',
            'email' => 'super@example.com',
        ]);

        $this->assertFalse($trainer->isSuperAdmin());
    }

    public function test_is_super_admin_returns_false_for_admin_not_in_config(): void
    {
        config(['auth.super_admins' => ['super@example.com']]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'regular-admin@example.com',
        ]);

        $this->assertFalse($admin->isSuperAdmin());
    }

    public function test_is_super_admin_returns_true_for_admin_in_config(): void
    {
        config(['auth.super_admins' => ['super@example.com']]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'super@example.com',
        ]);

        $this->assertTrue($admin->isSuperAdmin());
    }

    public function test_is_super_admin_returns_true_for_multiple_super_admins(): void
    {
        config(['auth.super_admins' => ['super1@example.com', 'super2@example.com']]);

        $admin1 = User::factory()->create([
            'role' => 'admin',
            'email' => 'super1@example.com',
        ]);

        $admin2 = User::factory()->create([
            'role' => 'admin',
            'email' => 'super2@example.com',
        ]);

        $this->assertTrue($admin1->isSuperAdmin());
        $this->assertTrue($admin2->isSuperAdmin());
    }

    public function test_is_super_admin_returns_false_with_empty_config(): void
    {
        config(['auth.super_admins' => []]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $this->assertFalse($admin->isSuperAdmin());
    }

    public function test_is_super_admin_is_case_sensitive(): void
    {
        config(['auth.super_admins' => ['Super@Example.com']]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'super@example.com',
        ]);

        // Email comparison is case-sensitive by default
        $this->assertFalse($admin->isSuperAdmin());
    }

    // ========== Role Helper Methods Tests ==========

    public function test_is_admin_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_is_admin_returns_false_for_non_admin(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($member->isAdmin());
        $this->assertFalse($trainer->isAdmin());
    }

    public function test_is_trainer_returns_true_for_trainer(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $this->assertTrue($trainer->isTrainer());
    }

    public function test_is_trainer_returns_false_for_non_trainer(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertFalse($member->isTrainer());
        $this->assertFalse($admin->isTrainer());
    }

    public function test_is_member_returns_true_for_member(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $this->assertTrue($member->isMember());
    }

    public function test_is_member_returns_false_for_non_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($admin->isMember());
        $this->assertFalse($trainer->isMember());
    }
}
