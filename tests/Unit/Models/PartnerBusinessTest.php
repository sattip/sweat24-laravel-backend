<?php

namespace Tests\Unit\Models;

use App\Models\PartnerBusiness;
use App\Models\PartnerOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerBusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_partner_business(): void
    {
        $business = PartnerBusiness::factory()->create();

        $this->assertDatabaseHas('partner_businesses', [
            'id' => $business->id,
            'name' => $business->name,
        ]);
    }

    public function test_has_many_offers(): void
    {
        $business = PartnerBusiness::factory()->create();
        PartnerOffer::factory()->count(3)->create(['partner_business_id' => $business->id]);

        $this->assertCount(3, $business->offers);
        $this->assertInstanceOf(PartnerOffer::class, $business->offers->first());
    }

    public function test_active_offers_only_returns_active(): void
    {
        $business = PartnerBusiness::factory()->create();
        PartnerOffer::factory()->active()->count(2)->create(['partner_business_id' => $business->id]);
        PartnerOffer::factory()->inactive()->create(['partner_business_id' => $business->id]);

        $this->assertCount(2, $business->activeOffers);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $business = PartnerBusiness::factory()->create(['is_active' => true]);

        $this->assertIsBool($business->is_active);
        $this->assertTrue($business->is_active);
    }

    public function test_active_state(): void
    {
        $business = PartnerBusiness::factory()->active()->create();

        $this->assertTrue($business->is_active);
    }

    public function test_inactive_state(): void
    {
        $business = PartnerBusiness::factory()->inactive()->create();

        $this->assertFalse($business->is_active);
    }

    public function test_phone_accessor_returns_contact_phone(): void
    {
        $business = PartnerBusiness::factory()->create(['contact_phone' => '123-456-7890']);

        $this->assertEquals('123-456-7890', $business->phone);
    }

    public function test_fillable_fields(): void
    {
        $data = [
            'name' => 'Test Business',
            'logo_url' => 'https://example.com/logo.png',
            'description' => 'Test description',
            'contact_email' => 'test@example.com',
            'contact_phone' => '123-456-7890',
            'address' => '123 Test St',
            'is_active' => true,
            'display_order' => 5,
        ];

        $business = PartnerBusiness::create($data);

        $this->assertEquals('Test Business', $business->name);
        $this->assertEquals('test@example.com', $business->contact_email);
        $this->assertEquals(5, $business->display_order);
    }
}
