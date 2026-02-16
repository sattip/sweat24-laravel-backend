<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PartnerBusiness;
use App\Models\PartnerOffer;

class PartnerSeeder extends Seeder
{
    public function run()
    {
        $partners = [
            [
                'name' => 'Health Food Store',
                'description' => 'Το κορυφαίο κατάστημα υγιεινής διατροφής στην πόλη',
                'logo_url' => 'https://images.unsplash.com/photo-1604719312566-8912e9227c6a?w=200&h=200&fit=crop',
                'contact_phone' => '2101234567',
                'address' => 'Λεωφ. Αθηνών 100',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Physio Plus',
                'description' => 'Εξειδικευμένο κέντρο φυσικοθεραπείας',
                'logo_url' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=200&h=200&fit=crop',
                'contact_phone' => '2102345678',
                'address' => 'Ερμού 50',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Sports Gear Pro',
                'description' => 'Αθλητικά είδη και εξοπλισμός',
                'logo_url' => 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=200&h=200&fit=crop',
                'contact_phone' => '2103456789',
                'address' => 'Σταδίου 20',
                'is_active' => true,
                'display_order' => 3,
            ],
        ];

        foreach ($partners as $partnerData) {
            $partner = PartnerBusiness::create($partnerData);

            // Create offers for each partner
            if ($partner->name === 'Health Food Store') {
                PartnerOffer::create([
                    'partner_business_id' => $partner->id,
                    'title' => '15% έκπτωση σε όλα τα προϊόντα',
                    'description' => 'Αποκλειστική έκπτωση για τα μέλη του Sweat24',
                    'type' => 'percentage',
                    'discount_percentage' => 15,
                    'discount_value' => 15,
                    'promo_code' => 'SWEAT15',
                    'is_active' => true,
                    'valid_from' => now(),
                    'valid_until' => now()->addMonths(6),
                    'usage_limit_per_user' => 5,
                    'total_usage_limit' => 100,
                ]);
            } elseif ($partner->name === 'Physio Plus') {
                PartnerOffer::create([
                    'partner_business_id' => $partner->id,
                    'title' => 'Δωρεάν αξιολόγηση',
                    'description' => 'Δωρεάν φυσικοθεραπευτική αξιολόγηση για μέλη Sweat24',
                    'type' => 'custom',
                    'promo_code' => 'SWEATEVAL',
                    'is_active' => true,
                    'valid_from' => now(),
                    'valid_until' => now()->addMonths(6),
                    'usage_limit_per_user' => 1,
                    'total_usage_limit' => 50,
                ]);
            } elseif ($partner->name === 'Sports Gear Pro') {
                PartnerOffer::create([
                    'partner_business_id' => $partner->id,
                    'title' => '20% έκπτωση στα παπούτσια',
                    'description' => 'Έκπτωση σε όλα τα αθλητικά παπούτσια',
                    'type' => 'percentage',
                    'discount_percentage' => 20,
                    'discount_value' => 20,
                    'promo_code' => 'SWEATSHOES',
                    'is_active' => true,
                    'valid_from' => now(),
                    'valid_until' => now()->addMonths(3),
                    'usage_limit_per_user' => 2,
                    'total_usage_limit' => 75,
                ]);
            }
        }
    }
}