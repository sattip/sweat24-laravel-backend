<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CashRegisterEntry;
use App\Models\Package;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class MultiStoreCashRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $store1;
    protected $store2;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->store1 = Store::create(['name' => 'Store A', 'address' => '123 Test St']);
        $this->store2 = Store::create(['name' => 'Store B', 'address' => '456 Test Ave']);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_cash_register_entry()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/cash-register', [
            'type' => 'income',
            'amount' => 100.00,
            'store_id' => $this->store1->id,
            'category' => 'package_usage',
            'description' => 'Package income test',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('cash_register_entries', [
            'store_id' => $this->store1->id,
            'type' => 'income',
        ]);
    }

    /** @test */
    public function it_can_list_cash_register_entries()
    {
        Sanctum::actingAs($this->admin);

        CashRegisterEntry::create([
            'type' => 'income',
            'amount' => 100.00,
            'store_id' => $this->store1->id,
            'user_id' => $this->admin->id,
            'category' => 'package_usage',
            'description' => 'Package income',
            'payment_method' => 'cash',
        ]);

        $response = $this->getJson('/api/v1/cash-register');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_auth_for_cash_register()
    {
        $response = $this->getJson('/api/v1/cash-register');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_admin_or_trainer_role_for_cash_register()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/cash-register');
        $response->assertStatus(403);
    }

    /** @test */
    public function stores_are_created_correctly()
    {
        $this->assertDatabaseHas('stores', ['name' => 'Store A']);
        $this->assertDatabaseHas('stores', ['name' => 'Store B']);
        $this->assertNotEquals($this->store1->id, $this->store2->id);
    }

    /** @test */
    public function user_package_tracks_sessions_correctly()
    {
        $package = Package::create([
            'name' => 'Test Package',
            'price' => 100.00,
            'sessions' => 3,
            'duration' => 30,
            'status' => 'active',
        ]);

        $userPackage = UserPackage::create([
            'user_id' => $this->user->id,
            'package_id' => $package->id,
            'name' => $package->name,
            'remaining_sessions' => 3,
            'total_sessions' => 3,
            'status' => 'active',
            'assigned_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertEquals(3, $userPackage->remaining_sessions);
        $this->assertEquals(3, $userPackage->total_sessions);

        // Simulate session usage
        UserPackage::where('id', $userPackage->id)
            ->where('remaining_sessions', '>', 0)
            ->decrement('remaining_sessions');

        $userPackage->refresh();
        $this->assertEquals(2, $userPackage->remaining_sessions);
    }
}
