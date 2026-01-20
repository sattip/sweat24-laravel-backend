<?php

namespace Tests\Unit\Models;

use App\Models\AppointmentRequest;
use App\Models\Booking;
use App\Models\BusinessExpense;
use App\Models\CashRegisterEntry;
use App\Models\Instructor;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Basic Model Tests
    // ==========================================

    public function test_can_create_store(): void
    {
        $store = Store::create([
            'name' => 'Main Store',
            'address' => '123 Main Street',
            'phone' => '+30 210 1234567',
            'email' => 'main@gym.com',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('stores', [
            'name' => 'Main Store',
            'address' => '123 Main Street',
        ]);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_is_active_is_boolean(): void
    {
        $store = Store::factory()->create(['is_active' => 1]);

        $this->assertTrue($store->is_active);
        $this->assertIsBool($store->is_active);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    public function test_has_many_cash_register_entries(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        CashRegisterEntry::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 100.00,
            'description' => 'Sale',
            'category' => 'sales',
        ]);

        $this->assertCount(1, $store->cashRegisterEntries);
    }

    public function test_has_many_bookings(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create();

        Booking::factory()->count(3)->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $store->bookings);
    }

    public function test_has_many_instructors(): void
    {
        $store = Store::factory()->create();

        Instructor::factory()->count(2)->create([
            'store_id' => $store->id,
        ]);

        $this->assertCount(2, $store->instructors);
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    public function test_scope_active(): void
    {
        // Get count of existing active stores
        $existingCount = Store::active()->count();

        Store::factory()->count(2)->create(['is_active' => true]);
        Store::factory()->create(['is_active' => false]);

        $activeStores = Store::active()->get();

        $this->assertCount($existingCount + 2, $activeStores);
    }

    // ==========================================
    // Default Values Tests
    // ==========================================

    public function test_default_values(): void
    {
        $store = Store::create([
            'name' => 'Test Store',
        ]);

        // Refresh to get database defaults
        $store->refresh();

        $this->assertTrue($store->is_active);
    }

    // ==========================================
    // Optional Fields Tests
    // ==========================================

    public function test_optional_fields_can_be_null(): void
    {
        $store = Store::create([
            'name' => 'Test Store',
            'is_active' => true,
        ]);

        $this->assertNull($store->address);
        $this->assertNull($store->phone);
        $this->assertNull($store->email);
        $this->assertNull($store->description);
    }

    public function test_can_have_color_attribute(): void
    {
        $store = Store::factory()->create([
            'color' => '#FF5733',
        ]);

        $this->assertEquals('#FF5733', $store->color);
    }
}
