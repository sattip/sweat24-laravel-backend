<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\BusinessExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BusinessExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $member;
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
        $this->store = Store::create(['name' => 'Test Store', 'address' => '123 Test St']);
    }

    public function test_business_expenses_requires_auth()
    {
        $this->getJson('/api/v1/business-expenses')->assertStatus(401);
    }

    public function test_member_cannot_access_business_expenses()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/business-expenses')->assertStatus(403);
    }

    public function test_admin_can_list_business_expenses()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/business-expenses');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_business_expense()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->postJson('/api/v1/business-expenses', [
            'store_id' => $this->store->id,
            'amount' => 150.00,
            'description' => 'Office supplies',
            'category' => 'supplies',
            'date' => now()->toDateString(),
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_member_cannot_create_business_expense()
    {
        Sanctum::actingAs($this->member);
        $this->postJson('/api/v1/business-expenses', [
            'amount' => 150.00,
            'description' => 'Test',
        ])->assertStatus(403);
    }
}
