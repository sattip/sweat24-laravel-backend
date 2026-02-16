<?php

namespace Tests\Feature\CashRegister;

use App\Models\CashRegisterEntry;
use App\Models\Package;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PackagePurchaseTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Package Purchase Creates Cash Entry Tests
    // ==========================================

    public function test_package_purchase_creates_cash_register_entry(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 150, 'sessions' => 10]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201);

        // Verify cash register entry was created
        $this->assertDatabaseHas('cash_register_entries', [
            'type' => 'income',
            'amount' => 150,
            'category' => 'Package Payment',
        ]);
    }

    public function test_package_purchase_with_correct_amount(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 299.99, 'sessions' => 20]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
        ]);

        $entry = CashRegisterEntry::where('category', 'Package Payment')->latest()->first();

        $this->assertEquals(299.99, (float) $entry->amount);
    }

    // ==========================================
    // Special Price Tests
    // ==========================================

    public function test_package_purchase_with_special_price(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 200, 'sessions' => 10]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'special_price' => 150,
            'special_price_reason' => 'VIP discount',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['special_price_applied' => true]);

        // Cash register should have special price, not original
        $this->assertDatabaseHas('cash_register_entries', [
            'type' => 'income',
            'amount' => 150,
        ]);
    }

    public function test_special_price_lower_than_original(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 300, 'sessions' => 15]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'special_price' => 250,
        ]);

        $userPackage = UserPackage::latest()->first();

        $this->assertEquals(250, (float) $userPackage->custom_price);
    }

    public function test_no_special_price_when_not_provided(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 200, 'sessions' => 10]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            // No special_price provided
        ]);

        $response->assertJson(['special_price_applied' => false]);
        
        // Cash register should have original price
        $this->assertDatabaseHas('cash_register_entries', [
            'amount' => 200,
        ]);
    }

    // ==========================================
    // Payment Method Tests
    // ==========================================

    public function test_cash_payment_recorded_correctly(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('cash_register_entries', [
            'payment_method' => 'cash',
        ]);
    }

    public function test_card_payment_recorded_correctly(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 100]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'payment_method' => 'card',
        ]);

        $this->assertDatabaseHas('cash_register_entries', [
            'payment_method' => 'card',
        ]);
    }

    // ==========================================
    // Multiple Package Scenario Tests
    // ==========================================

    public function test_multiple_packages_total_correctly(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package1 = Package::factory()->create(['price' => 100]);
        $package2 = Package::factory()->create(['price' => 150]);

        Sanctum::actingAs($admin);

        // Purchase first package
        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package1->id,
        ]);

        // Purchase second package
        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package2->id,
        ]);

        $totalIncome = CashRegisterEntry::where('type', 'income')
            ->where('category', 'Package Payment')
            ->sum('amount');

        $this->assertEquals(250, (float) $totalIncome);
    }

    // ==========================================
    // Edge Cases
    // ==========================================

    public function test_zero_price_package(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 0, 'sessions' => 5]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('cash_register_entries', [
            'amount' => 0,
        ]);
    }

    public function test_high_value_package(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 9999.99, 'sessions' => 100]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
        ]);

        $this->assertDatabaseHas('cash_register_entries', [
            'amount' => 9999.99,
        ]);
    }

    public function test_decimal_price_precision(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 123.45, 'sessions' => 7]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
        ]);

        $entry = CashRegisterEntry::where('category', 'Package Payment')->latest()->first();

        $this->assertEquals('123.45', $entry->amount);
    }

    // ==========================================
    // Discount/Special Price Amount Verification
    // ==========================================

    public function test_special_price_discount_amount_is_correct(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 500, 'sessions' => 20]);

        Sanctum::actingAs($admin);

        $specialPrice = 400; // 100 discount
        
        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'special_price' => $specialPrice,
        ]);

        // Verify the cash entry has the discounted price
        $entry = CashRegisterEntry::where('category', 'Package Payment')->latest()->first();
        $this->assertEquals($specialPrice, (float) $entry->amount);

        // Verify the user package has custom_price set
        $userPackage = UserPackage::latest()->first();
        $this->assertEquals($specialPrice, (float) $userPackage->custom_price);
    }

    public function test_50_percent_discount_special_price(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $package = Package::factory()->create(['price' => 200, 'sessions' => 10]);

        Sanctum::actingAs($admin);

        $halfPrice = 100; // 50% discount
        
        $this->postJson('/api/v1/user-packages', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'special_price' => $halfPrice,
        ]);

        $this->assertDatabaseHas('cash_register_entries', [
            'amount' => 100,
        ]);
    }
}
