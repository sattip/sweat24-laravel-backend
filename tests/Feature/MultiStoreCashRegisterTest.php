<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\Package;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MultiStoreCashRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $store1;
    protected $store2;
    protected $user;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Create stores
        $this->store1 = Store::create(['name' => 'Βάρη', 'is_active' => true]);
        $this->store2 = Store::create(['name' => 'Λαγονήσι', 'is_active' => true]);

        // Create regular user
        $this->user = User::factory()->create();

        // Create package
        $this->package = Package::create([
            'name' => 'Test Package',
            'price' => 100.00,
            'sessions' => 3,
            'type' => 'personal',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_complete_booking_and_record_income()
    {
        // Create user package
        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'name' => $this->package->name,
            'remaining_sessions' => 3,
            'total_sessions' => 3,
            'status' => 'active',
        ]);

        // Create booking
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'store_id' => $this->store1->id,
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'status' => 'confirmed',
            'type' => 'personal',
            'date' => now()->addDay(),
            'time' => '10:00',
            'location' => $this->store1->name,
        ]);

        // Act as admin and complete booking
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/bookings/{$booking->id}/complete", [
                'completed_by' => $this->admin->id
            ]);

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking completed successfully'
            ]);

        // Assert booking was completed
        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
        $this->assertEquals($this->store1->id, $booking->store_id);

        // Assert package sessions decreased
        $userPackage->refresh();
        $this->assertEquals(2, $userPackage->remaining_sessions);

        // Assert cash entry was created
        $cashEntry = CashRegisterEntry::where('store_id', $this->store1->id)->first();
        $this->assertNotNull($cashEntry);
        $this->assertEquals('income', $cashEntry->type);
        $this->assertEquals(33.33, $cashEntry->amount); // 100 / 3 = 33.33
        $this->assertEquals($this->store1->id, $cashEntry->store_id);
    }

    /** @test */
    public function it_handles_rounding_correctly_for_last_session()
    {
        // Create user package with 3 sessions
        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'name' => $this->package->name,
            'remaining_sessions' => 1, // Last session
            'total_sessions' => 3,
            'status' => 'active',
        ]);

        // Create booking for last session
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'store_id' => $this->store1->id,
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'status' => 'confirmed',
            'type' => 'personal',
            'date' => now()->addDay(),
            'time' => '10:00',
            'location' => $this->store1->name,
        ]);

        // Complete booking
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/bookings/{$booking->id}/complete", [
                'completed_by' => $this->admin->id
            ]);

        $response->assertStatus(200);

        // Assert cash entry amount adjusts to match total (100 - 33.33 - 33.33 = 33.34)
        $cashEntry = CashRegisterEntry::where('store_id', $this->store1->id)->latest()->first();
        $this->assertEquals(33.34, $cashEntry->amount);
    }

    /** @test */
    public function it_requires_store_assignment_for_booking_completion()
    {
        // Create booking without store_id
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'store_id' => null, // No store assigned
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'status' => 'confirmed',
            'type' => 'personal',
            'date' => now()->addDay(),
            'time' => '10:00',
        ]);

        // Try to complete booking
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/bookings/{$booking->id}/complete", [
                'completed_by' => $this->admin->id
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Booking must have a store assigned'
            ]);
    }

    /** @test */
    public function it_can_record_expenses_per_store()
    {
        $expenseData = [
            'store_id' => $this->store1->id,
            'amount' => 50.00,
            'category' => 'Rent',
            'description' => 'Monthly rent payment',
            'payment_method' => 'transfer'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Expense recorded successfully'
            ]);

        // Assert expense was recorded
        $expense = CashRegisterEntry::where('store_id', $this->store1->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals('withdrawal', $expense->type);
        $this->assertEquals(50.00, $expense->amount);
        $this->assertEquals('Rent', $expense->category);
        $this->assertEquals($this->store1->id, $expense->store_id);
    }

    /** @test */
    public function it_can_get_store_financial_report()
    {
        // Create some cash entries
        CashRegisterEntry::create([
            'type' => 'income',
            'amount' => 100.00,
            'store_id' => $this->store1->id,
            'user_id' => $this->admin->id,
            'category' => 'package_usage',
            'description' => 'Package income',
        ]);

        CashRegisterEntry::create([
            'type' => 'withdrawal',
            'amount' => 50.00,
            'store_id' => $this->store1->id,
            'user_id' => $this->admin->id,
            'category' => 'Rent',
            'description' => 'Rent expense',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/stores/{$this->store1->id}/report");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'store_id' => $this->store1->id,
                    'income' => 100.00,
                    'expenses' => 50.00,
                    'net' => 50.00,
                ]
            ]);
    }

    /** @test */
    public function it_can_get_user_packages_with_usage_info()
    {
        // Create user package
        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'name' => $this->package->name,
            'remaining_sessions' => 2,
            'total_sessions' => 3,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/customers/{$this->user->id}/packages");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ],
                    'packages' => [
                        [
                            'id' => $userPackage->id,
                            'name' => $this->package->name,
                            'total_sessions' => 3,
                            'remaining_sessions' => 2,
                            'used_sessions' => 1,
                            'per_training_cost' => 33.33,
                            'status' => 'active',
                        ]
                    ]
                ]
            ]);
    }
}
