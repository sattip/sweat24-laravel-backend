<?php

namespace Tests\Unit\Auth;

use App\Auth\TokenAbilities;
use Tests\TestCase;

class TokenAbilitiesTest extends TestCase
{
    /** @test */
    public function member_abilities_include_basic_permissions(): void
    {
        $abilities = TokenAbilities::forMember();

        $this->assertContains(TokenAbilities::READ_PROFILE, $abilities);
        $this->assertContains(TokenAbilities::UPDATE_PROFILE, $abilities);
        $this->assertContains(TokenAbilities::CREATE_BOOKING, $abilities);
        $this->assertContains(TokenAbilities::READ_BOOKINGS, $abilities);
        $this->assertContains(TokenAbilities::CANCEL_BOOKING, $abilities);
        $this->assertContains(TokenAbilities::READ_PACKAGES, $abilities);
        $this->assertContains(TokenAbilities::READ_CLASSES, $abilities);
        $this->assertContains(TokenAbilities::READ_NOTIFICATIONS, $abilities);
    }

    /** @test */
    public function member_abilities_do_not_include_admin_permissions(): void
    {
        $abilities = TokenAbilities::forMember();

        $this->assertNotContains(TokenAbilities::ADMIN_ACCESS, $abilities);
        $this->assertNotContains(TokenAbilities::MANAGE_USERS, $abilities);
        $this->assertNotContains(TokenAbilities::MANAGE_FINANCIALS, $abilities);
        $this->assertNotContains(TokenAbilities::VIEW_REPORTS, $abilities);
        $this->assertNotContains(TokenAbilities::MANAGE_SETTINGS, $abilities);
    }

    /** @test */
    public function trainer_abilities_include_member_abilities(): void
    {
        $memberAbilities = TokenAbilities::forMember();
        $trainerAbilities = TokenAbilities::forTrainer();

        foreach ($memberAbilities as $ability) {
            $this->assertContains($ability, $trainerAbilities);
        }
    }

    /** @test */
    public function trainer_abilities_include_management_permissions(): void
    {
        $abilities = TokenAbilities::forTrainer();

        $this->assertContains(TokenAbilities::MANAGE_BOOKINGS, $abilities);
        $this->assertContains(TokenAbilities::MANAGE_CLASSES, $abilities);
        $this->assertContains(TokenAbilities::READ_USERS, $abilities);
        $this->assertContains(TokenAbilities::READ_FINANCIALS, $abilities);
        $this->assertContains(TokenAbilities::MANAGE_CASH_REGISTER, $abilities);
    }

    /** @test */
    public function trainer_abilities_do_not_include_full_admin_permissions(): void
    {
        $abilities = TokenAbilities::forTrainer();

        $this->assertNotContains(TokenAbilities::ADMIN_ACCESS, $abilities);
        $this->assertNotContains(TokenAbilities::MANAGE_USERS, $abilities);
        $this->assertNotContains(TokenAbilities::MANAGE_PACKAGES, $abilities);
        $this->assertNotContains(TokenAbilities::MANAGE_SETTINGS, $abilities);
    }

    /** @test */
    public function admin_abilities_include_all_permissions(): void
    {
        $abilities = TokenAbilities::forAdmin();

        $this->assertContains(TokenAbilities::ADMIN_ACCESS, $abilities);
        $this->assertContains(TokenAbilities::MANAGE_USERS, $abilities);
        $this->assertContains(TokenAbilities::APPROVE_USERS, $abilities);
        $this->assertContains(TokenAbilities::MANAGE_PACKAGES, $abilities);
        $this->assertContains(TokenAbilities::MANAGE_FINANCIALS, $abilities);
        $this->assertContains(TokenAbilities::VIEW_REPORTS, $abilities);
        $this->assertContains(TokenAbilities::MANAGE_SETTINGS, $abilities);
    }

    /** @test */
    public function for_role_returns_correct_abilities(): void
    {
        $this->assertEquals(TokenAbilities::forMember(), TokenAbilities::forRole('member'));
        $this->assertEquals(TokenAbilities::forTrainer(), TokenAbilities::forRole('trainer'));
        $this->assertEquals(TokenAbilities::forAdmin(), TokenAbilities::forRole('admin'));
    }

    /** @test */
    public function unknown_role_returns_member_abilities(): void
    {
        $this->assertEquals(TokenAbilities::forMember(), TokenAbilities::forRole('unknown'));
    }

    /** @test */
    public function all_returns_admin_abilities(): void
    {
        $this->assertEquals(TokenAbilities::forAdmin(), TokenAbilities::all());
    }
}
