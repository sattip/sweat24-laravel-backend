<?php

namespace Database\Seeders;

use App\Models\ReferralRewardTier;
use Illuminate\Database\Seeder;

class ReferralRewardTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'referrals_required' => 1,
                'name' => '1η Σύσταση',
                'description' => 'Έκπτωση 10% στην επόμενη ανανέωση τριμήνου πακέτου',
                'reward_type' => 'discount',
                'discount_percentage' => 10.00,
                'validity_days' => 90,
                'quarterly_only' => true,
                'next_renewal_only' => true,
                'is_active' => true,
                'terms_conditions' => [
                    'Ισχύει μόνο για τρίμηνα πακέτα',
                    'Δεν συνδυάζεται με άλλες προσφορές',
                    'Η έκπτωση ισχύει για την αμέσως επόμενη ανανέωση'
                ],
            ],
            [
                'referrals_required' => 2,
                'name' => '2η Σύσταση',
                'description' => 'Έκπτωση 15% στην επόμενη ανανέωση τριμήνου πακέτου',
                'reward_type' => 'discount',
                'discount_percentage' => 15.00,
                'validity_days' => 90,
                'quarterly_only' => true,
                'next_renewal_only' => true,
                'is_active' => true,
                'terms_conditions' => [
                    'Ισχύει μόνο για τρίμηνα πακέτα',
                    'Δεν συνδυάζεται με άλλες προσφορές',
                    'Η έκπτωση ισχύει για την αμέσως επόμενη ανανέωση'
                ],
            ],
            [
                'referrals_required' => 3,
                'name' => '3η Σύσταση',
                'description' => 'Έκπτωση 20% στην επόμενη ανανέωση τριμήνου πακέτου',
                'reward_type' => 'discount',
                'discount_percentage' => 20.00,
                'validity_days' => 90,
                'quarterly_only' => true,
                'next_renewal_only' => true,
                'is_active' => true,
                'terms_conditions' => [
                    'Ισχύει μόνο για τρίμηνα πακέτα',
                    'Δεν συνδυάζεται με άλλες προσφορές',
                    'Η έκπτωση ισχύει για την αμέσως επόμενη ανανέωση'
                ],
            ],
            [
                'referrals_required' => 5,
                'name' => '5η Σύσταση',
                'description' => 'Δωρεάν μήνας συνδρομής',
                'reward_type' => 'free_month',
                'validity_days' => 180,
                'quarterly_only' => false,
                'next_renewal_only' => false,
                'is_active' => true,
                'terms_conditions' => [
                    'Προσθέτει έναν επιπλέον μήνα στο τρέχον πακέτο',
                    'Δεν συνδυάζεται με άλλες προσφορές',
                    'Ισχύει για 6 μήνες από την απόκτηση'
                ],
            ],
            [
                'referrals_required' => 10,
                'name' => '10η Σύσταση',
                'description' => 'Δωρεάν προσωπική προπόνηση (1 ώρα)',
                'reward_type' => 'personal_training',
                'validity_days' => 90,
                'quarterly_only' => false,
                'next_renewal_only' => false,
                'is_active' => true,
                'terms_conditions' => [
                    'Μία (1) ώρα προσωπικής προπόνησης',
                    'Κλείσιμο ραντεβού μέσω reception',
                    'Ισχύει για 3 μήνες από την απόκτηση'
                ],
            ],
        ];

        foreach ($tiers as $tier) {
            ReferralRewardTier::create($tier);
        }
    }
}
