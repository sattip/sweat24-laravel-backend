<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnerBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = [
            [
                'name' => 'The Coffee Spot',
                'logo_url' => '/placeholder.svg',
                'description' => 'Το ιδανικό μέρος για να χαλαρώσετε μετά την προπόνηση με τον καλύτερο καφέ της πόλης.',
                'display_order' => 1,
                'offer' => ['title' => '15% έκπτωση σε όλους τους καφέδες', 'type' => 'percentage', 'discount_value' => 15],
            ],
            [
                'name' => 'Healthy Bites',
                'logo_url' => '/placeholder.svg',
                'description' => 'Θρεπτικά και νόστιμα γεύματα για να υποστηρίξετε τους στόχους της φυσικής σας κατάστασης.',
                'display_order' => 2,
                'offer' => ['title' => '10% έκπτωση σε όλα τα γεύματα', 'type' => 'percentage', 'discount_value' => 10],
            ],
            [
                'name' => 'Sports Gear Pro',
                'logo_url' => '/placeholder.svg',
                'description' => 'Βρείτε τον καλύτερο εξοπλισμό και ρουχισμό για τις προπονήσεις σας.',
                'display_order' => 3,
                'offer' => ['title' => '20% έκπτωση σε είδη ρουχισμού', 'type' => 'percentage', 'discount_value' => 20],
            ],
        ];

        foreach ($partners as $partnerData) {
            $offer = $partnerData['offer'];
            unset($partnerData['offer']);
            
            $partner = \App\Models\PartnerBusiness::create($partnerData);
            
            \App\Models\PartnerOffer::create([
                'partner_business_id' => $partner->id,
                'title' => $offer['title'],
                'description' => $offer['title'],
                'type' => $offer['type'],
                'discount_value' => $offer['discount_value'],
                'promo_code' => 'S24-PROMO-' . $partner->id,
                'usage_limit_per_user' => 1,
            ]);
        }
    }
}
