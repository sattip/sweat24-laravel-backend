<?php

namespace Tests\Unit\Models;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_signature(): void
    {
        $signature = Signature::factory()->create();
        $this->assertDatabaseHas('signatures', ['id' => $signature->id]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $signature = Signature::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $signature->user);
        $this->assertEquals($user->id, $signature->user->id);
    }

    public function test_terms_and_conditions_state(): void
    {
        $signature = Signature::factory()->termsAndConditions()->create();
        $this->assertEquals('terms_and_conditions', $signature->document_type);
    }

    public function test_privacy_policy_state(): void
    {
        $signature = Signature::factory()->privacyPolicy()->create();
        $this->assertEquals('privacy_policy', $signature->document_type);
    }

    public function test_liability_state(): void
    {
        $signature = Signature::factory()->liability()->create();
        $this->assertEquals('liability_waiver', $signature->document_type);
    }

    public function test_has_document_version(): void
    {
        $signature = Signature::factory()->create();
        $this->assertEquals('1.0', $signature->document_version);
    }

    public function test_has_signature_data(): void
    {
        $signature = Signature::factory()->create();

        $this->assertNotNull($signature->signature_data);
        $this->assertStringStartsWith('data:image/png;base64,', $signature->signature_data);
    }

    public function test_has_signed_at(): void
    {
        $signature = Signature::factory()->create();
        $this->assertNotNull($signature->signed_at);
    }

    public function test_has_ip_address(): void
    {
        $signature = Signature::factory()->create();
        $this->assertNotNull($signature->ip_address);
    }
}
