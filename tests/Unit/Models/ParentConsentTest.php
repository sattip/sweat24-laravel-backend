<?php

namespace Tests\Unit\Models;

use App\Models\ParentConsent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_parent_consent(): void
    {
        $consent = ParentConsent::factory()->create();
        $this->assertDatabaseHas('parent_consents', ['id' => $consent->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $consent = ParentConsent::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $consent->user);
        $this->assertEquals($user->id, $consent->user->id);
    }

    public function test_with_consent_state(): void
    {
        $consent = ParentConsent::factory()->withConsent()->create();
        $this->assertTrue($consent->consent_accepted);
    }

    public function test_without_consent_state(): void
    {
        $consent = ParentConsent::factory()->withoutConsent()->create();
        $this->assertFalse($consent->consent_accepted);
    }

    public function test_has_parent_details(): void
    {
        $consent = ParentConsent::factory()->create();

        $this->assertNotEmpty($consent->parent_full_name);
        $this->assertNotEmpty($consent->father_first_name);
        $this->assertNotEmpty($consent->mother_first_name);
        $this->assertNotEmpty($consent->parent_id_number);
    }

    public function test_has_signature(): void
    {
        $consent = ParentConsent::factory()->create();
        $this->assertNotEmpty($consent->signature);
    }

    public function test_has_consent_version(): void
    {
        $consent = ParentConsent::factory()->create();
        $this->assertEquals('1.0', $consent->consent_version);
    }

    public function test_has_contact_info(): void
    {
        $consent = ParentConsent::factory()->create();

        $this->assertNotEmpty($consent->parent_phone);
        $this->assertNotEmpty($consent->parent_email);
        $this->assertNotEmpty($consent->parent_location);
    }
}
