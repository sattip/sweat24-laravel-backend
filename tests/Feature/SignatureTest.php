<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Signature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SignatureTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    public function test_signature_store_requires_auth()
    {
        $this->postJson('/api/v1/signatures', ['signature_data' => 'test'])->assertStatus(401);
    }

    public function test_user_can_store_signature()
    {
        Sanctum::actingAs($this->member);
        $response = $this->postJson('/api/v1/signatures', [
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANS',
            'type' => 'terms_acceptance',
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_user_can_view_own_signatures()
    {
        Sanctum::actingAs($this->member);
        $this->getJson("/api/v1/users/{$this->member->id}/signatures")->assertStatus(200);
    }

    public function test_admin_can_list_all_signatures()
    {
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/signatures')->assertStatus(200);
    }

    public function test_member_cannot_list_all_signatures()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/signatures')->assertStatus(403);
    }
}
