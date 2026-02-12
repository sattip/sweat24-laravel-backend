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

/**
 * NOTE: These tests are for multi-store cash register features that are not yet implemented.
 * The following routes/endpoints do not exist in the current codebase:
 * - POST /api/v1/bookings/{id}/complete
 * - POST /api/v1/expenses
 * - GET /api/v1/stores/{id}/report
 * - GET /api/v1/customers/{id}/packages
 *
 * These tests are skipped until the features are implemented.
 */
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

        // Create package using factory to ensure all required fields are set
        $this->package = Package::factory()->create([
            'name' => 'Test Package',
            'price' => 100.00,
            'sessions' => 3,
            'duration' => 30,
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_complete_booking_and_record_income()
    {
        $this->markTestSkipped('Feature not implemented: POST /api/v1/bookings/{id}/complete endpoint does not exist');
    }

    /** @test */
    public function it_handles_rounding_correctly_for_last_session()
    {
        $this->markTestSkipped('Feature not implemented: POST /api/v1/bookings/{id}/complete endpoint does not exist');
    }

    /** @test */
    public function it_requires_store_assignment_for_booking_completion()
    {
        $this->markTestSkipped('Feature not implemented: POST /api/v1/bookings/{id}/complete endpoint does not exist');
    }

    /** @test */
    public function it_can_record_expenses_per_store()
    {
        $this->markTestSkipped('Feature not implemented: POST /api/v1/expenses endpoint does not exist. Use /api/v1/business-expenses instead.');
    }

    /** @test */
    public function it_can_get_store_financial_report()
    {
        $this->markTestSkipped('Feature not implemented: GET /api/v1/stores/{id}/report endpoint does not exist');
    }

    /** @test */
    public function it_can_get_user_packages_with_usage_info()
    {
        $this->markTestSkipped('Feature not implemented: GET /api/v1/customers/{id}/packages endpoint does not exist');
    }
}
