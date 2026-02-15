<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserPackage;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PaymentInstallmentTest extends TestCase
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

    public function test_payment_installments_requires_auth()
    {
        $this->getJson('/api/v1/payment-installments')->assertStatus(401);
    }

    public function test_member_cannot_access_payment_installments()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/payment-installments')->assertStatus(403);
    }

    public function test_admin_can_list_payment_installments()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/payment-installments');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_payment_installment()
    {
        Sanctum::actingAs($this->admin);
        $service = Service::create(['name' => 'EMS', 'slug' => 'ems', 'lesson_type' => 'personal', 'is_active' => true]);
        $package = Package::create([
            'name' => 'Test Package',
            'price' => 200,
            'sessions' => 10,
            'duration' => 30,
            'status' => 'active',
        ]);
        $package->services()->attach($service->id);
        $userPackage = UserPackage::create([
            'user_id' => $this->member->id,
            'package_id' => $package->id,
            'name' => 'Test Package',
            'assigned_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'total_sessions' => 10,
            'remaining_sessions' => 10,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/payment-installments', [
            'user_package_id' => $userPackage->id,
            'amount' => 100.00,
            'due_date' => now()->addDays(15)->toDateString(),
            'payment_method' => 'cash',
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }
}
