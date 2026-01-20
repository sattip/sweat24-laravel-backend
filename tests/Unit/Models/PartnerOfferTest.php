<?php

namespace Tests\Unit\Models;

use App\Models\OfferRedemption;
use App\Models\PartnerBusiness;
use App\Models\PartnerOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_partner_offer(): void
    {
        $offer = PartnerOffer::factory()->create();

        $this->assertDatabaseHas('partner_offers', [
            'id' => $offer->id,
            'title' => $offer->title,
        ]);
    }

    public function test_belongs_to_partner_business(): void
    {
        $business = PartnerBusiness::factory()->create();
        $offer = PartnerOffer::factory()->create(['partner_business_id' => $business->id]);

        $this->assertInstanceOf(PartnerBusiness::class, $offer->partnerBusiness);
        $this->assertEquals($business->id, $offer->partnerBusiness->id);
    }

    public function test_has_many_redemptions(): void
    {
        $offer = PartnerOffer::factory()->create();
        OfferRedemption::factory()->count(3)->create(['partner_offer_id' => $offer->id]);

        $this->assertCount(3, $offer->redemptions);
        $this->assertInstanceOf(OfferRedemption::class, $offer->redemptions->first());
    }

    public function test_formatted_offer_for_percentage_type(): void
    {
        $offer = PartnerOffer::factory()->create([
            'type' => 'percentage',
            'discount_value' => 20,
            'discount_percentage' => null,
        ]);

        // discount_value is cast to decimal:2, so it shows 20.00
        $this->assertEquals('20.00% έκπτωση', $offer->formatted_offer);
    }

    public function test_formatted_offer_for_fixed_amount(): void
    {
        $offer = PartnerOffer::factory()->create([
            'type' => 'fixed_amount',
            'discount_value' => 10.00,
        ]);

        $this->assertEquals('€10.00 έκπτωση', $offer->formatted_offer);
    }

    public function test_formatted_offer_fallback_to_title(): void
    {
        $offer = PartnerOffer::factory()->create([
            'type' => 'custom',
            'title' => 'Special Deal',
        ]);

        $this->assertEquals('Special Deal', $offer->formatted_offer);
    }

    public function test_used_count_accessor(): void
    {
        $offer = PartnerOffer::factory()->create(['current_usage_count' => 5]);

        $this->assertEquals(5, $offer->used_count);
    }

    public function test_usage_limit_accessor(): void
    {
        $offer = PartnerOffer::factory()->create(['total_usage_limit' => 100]);

        $this->assertEquals(100, $offer->usage_limit);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $offer = PartnerOffer::factory()->create(['is_active' => true]);

        $this->assertIsBool($offer->is_active);
        $this->assertTrue($offer->is_active);
    }

    public function test_valid_from_cast_to_date(): void
    {
        $offer = PartnerOffer::factory()->create(['valid_from' => '2026-01-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $offer->valid_from);
        $this->assertEquals('2026-01-15', $offer->valid_from->format('Y-m-d'));
    }

    public function test_valid_until_cast_to_date(): void
    {
        $offer = PartnerOffer::factory()->create(['valid_until' => '2026-06-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $offer->valid_until);
        $this->assertEquals('2026-06-15', $offer->valid_until->format('Y-m-d'));
    }

    public function test_discount_value_cast_to_decimal(): void
    {
        $offer = PartnerOffer::factory()->create(['discount_value' => 15.50]);

        $this->assertEquals('15.50', $offer->discount_value);
    }

    public function test_active_state(): void
    {
        $offer = PartnerOffer::factory()->active()->create();

        $this->assertTrue($offer->is_active);
    }

    public function test_inactive_state(): void
    {
        $offer = PartnerOffer::factory()->inactive()->create();

        $this->assertFalse($offer->is_active);
    }

    public function test_expired_state(): void
    {
        $offer = PartnerOffer::factory()->expired()->create();

        $this->assertTrue($offer->valid_until->isPast());
    }

    public function test_percentage_state(): void
    {
        $offer = PartnerOffer::factory()->percentage()->create();

        $this->assertEquals('percentage', $offer->type);
        $this->assertEquals('%', $offer->discount_unit);
    }

    public function test_fixed_amount_state(): void
    {
        $offer = PartnerOffer::factory()->fixedAmount()->create();

        $this->assertEquals('fixed_amount', $offer->type);
        $this->assertEquals('€', $offer->discount_unit);
    }

    public function test_free_item_state(): void
    {
        $offer = PartnerOffer::factory()->freeItem()->create();

        $this->assertEquals('free_item', $offer->type);
        $this->assertNull($offer->discount_value);
    }

    public function test_with_limit_state(): void
    {
        $offer = PartnerOffer::factory()->withLimit(50)->create();

        $this->assertEquals(50, $offer->total_usage_limit);
    }

    public function test_with_user_limit_state(): void
    {
        $offer = PartnerOffer::factory()->withUserLimit(3)->create();

        $this->assertEquals(3, $offer->usage_limit_per_user);
    }
}
