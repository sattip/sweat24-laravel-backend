<?php

namespace Tests\Unit\Models;

use App\Models\OfferRedemption;
use App\Models\PartnerOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferRedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_offer_redemption(): void
    {
        $redemption = OfferRedemption::factory()->create();

        $this->assertDatabaseHas('offer_redemptions', [
            'id' => $redemption->id,
            'user_id' => $redemption->user_id,
        ]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $redemption = OfferRedemption::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $redemption->user);
        $this->assertEquals($user->id, $redemption->user->id);
    }

    public function test_belongs_to_partner_offer(): void
    {
        $offer = PartnerOffer::factory()->create();
        $redemption = OfferRedemption::factory()->create(['partner_offer_id' => $offer->id]);

        $this->assertInstanceOf(PartnerOffer::class, $redemption->partnerOffer);
        $this->assertEquals($offer->id, $redemption->partnerOffer->id);
    }

    public function test_used_at_cast_to_datetime(): void
    {
        $redemption = OfferRedemption::factory()->used()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $redemption->used_at);
    }

    public function test_expires_at_cast_to_datetime(): void
    {
        $redemption = OfferRedemption::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $redemption->expires_at);
    }

    public function test_pending_state(): void
    {
        $redemption = OfferRedemption::factory()->pending()->create();

        $this->assertEquals('pending', $redemption->status);
        $this->assertNull($redemption->used_at);
    }

    public function test_used_state(): void
    {
        $redemption = OfferRedemption::factory()->used()->create();

        $this->assertEquals('used', $redemption->status);
        $this->assertNotNull($redemption->used_at);
    }

    public function test_expired_state(): void
    {
        $redemption = OfferRedemption::factory()->expired()->create();

        $this->assertEquals('expired', $redemption->status);
        $this->assertTrue($redemption->expires_at->isPast());
    }

    public function test_with_notes_state(): void
    {
        $redemption = OfferRedemption::factory()->withNotes()->create();

        $this->assertNotNull($redemption->notes);
    }

    public function test_auto_generates_verification_code(): void
    {
        $user = User::factory()->create();
        $offer = PartnerOffer::factory()->create();

        // Create without providing verification_code
        $redemption = OfferRedemption::create([
            'user_id' => $user->id,
            'partner_offer_id' => $offer->id,
            'status' => 'pending',
        ]);

        $this->assertNotNull($redemption->verification_code);
        $this->assertStringStartsWith('S24-', $redemption->verification_code);
    }

    public function test_auto_sets_expires_at(): void
    {
        $user = User::factory()->create();
        $offer = PartnerOffer::factory()->create();

        // Create without providing expires_at
        $redemption = OfferRedemption::create([
            'user_id' => $user->id,
            'partner_offer_id' => $offer->id,
            'status' => 'pending',
            'verification_code' => 'S24-TEST01',
        ]);

        $this->assertNotNull($redemption->expires_at);
        $this->assertTrue($redemption->expires_at->isFuture());
    }

    public function test_verification_code_is_unique(): void
    {
        $redemption1 = OfferRedemption::factory()->create(['verification_code' => 'S24-UNIQUE1']);
        $redemption2 = OfferRedemption::factory()->create(['verification_code' => 'S24-UNIQUE2']);

        $this->assertNotEquals($redemption1->verification_code, $redemption2->verification_code);
    }

    public function test_fillable_fields(): void
    {
        $user = User::factory()->create();
        $offer = PartnerOffer::factory()->create();

        $redemption = OfferRedemption::create([
            'user_id' => $user->id,
            'partner_offer_id' => $offer->id,
            'verification_code' => 'S24-TEST99',
            'status' => 'pending',
            'notes' => 'Test notes',
        ]);

        $this->assertEquals($user->id, $redemption->user_id);
        $this->assertEquals('S24-TEST99', $redemption->verification_code);
        $this->assertEquals('Test notes', $redemption->notes);
    }
}
