<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PointsReward;

class PointsRewardsSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            [
                'name' => '5€ Δωροκάρτα',
                'description' => 'Δωροκάρτα αξίας 5€ για χρήση στο γυμναστήριο',
                'points_cost' => 30,
                'reward_type' => 'gift_card',
                'reward_value' => '5€',
                'is_active' => true,
                'sort_order' => 1,
                'terms_conditions' => 'Ισχύει για 12 μήνες από την εξαργύρωση. Δεν μπορεί να συνδυαστεί με άλλες προσφορές.',
            ],
            [
                'name' => '10€ Δωροκάρτα',
                'description' => 'Δωροκάρτα αξίας 10€ για χρήση στο γυμναστήριο',
                'points_cost' => 60,
                'reward_type' => 'gift_card',
                'reward_value' => '10€',
                'is_active' => true,
                'sort_order' => 2,
                'terms_conditions' => 'Ισχύει για 12 μήνες από την εξαργύρωση. Δεν μπορεί να συνδυαστεί με άλλες προσφορές.',
            ],
            [
                'name' => 'Δωρεάν Personal Training',
                'description' => 'Μία δωρεάν προπόνηση με personal trainer',
                'points_cost' => 100,
                'reward_type' => 'free_session',
                'reward_value' => '1 session',
                'is_active' => true,
                'sort_order' => 3,
                'max_redemptions' => 50,
                'terms_conditions' => 'Ισχύει για 3 μήνες. Απαιτείται προγραμματισμός ραντεβού.',
            ],
            [
                'name' => '20% Έκπτωση στο επόμενο πακέτο',
                'description' => '20% έκπτωση στην επόμενη ανανέωση συνδρομής',
                'points_cost' => 75,
                'reward_type' => 'discount',
                'reward_value' => '20%',
                'is_active' => true,
                'sort_order' => 4,
                'terms_conditions' => 'Ισχύει για 30 ημέρες. Εφαρμόζεται μόνο σε νέες συνδρομές.',
            ],
            [
                'name' => 'Premium Μηνιαία Συνδρομή',
                'description' => 'Ένας μήνας premium υπηρεσίες',
                'points_cost' => 200,
                'reward_type' => 'premium',
                'reward_value' => '1 month',
                'is_active' => true,
                'sort_order' => 5,
                'max_redemptions' => 20,
                'terms_conditions' => 'Ενεργοποιείται αυτόματα. Δεν μπορεί να μεταφερθεί.',
            ],
            [
                'name' => 'SWEAT24 Towel',
                'description' => 'Επίσημη πετσέτα γυμναστηρίου SWEAT24',
                'points_cost' => 40,
                'reward_type' => 'merchandise',
                'reward_value' => '1 towel',
                'is_active' => true,
                'sort_order' => 6,
                'max_redemptions' => 100,
                'terms_conditions' => 'Παραλαμβάνεται από τη reception. Περιορισμένη διαθεσιμότητα.',
            ],
            [
                'name' => 'Πρωτεΐνη Whey 1kg',
                'description' => 'Πρωτεΐνη whey υψηλής ποιότητας 1kg',
                'points_cost' => 150,
                'reward_type' => 'product',
                'reward_value' => '1kg',
                'is_active' => true,
                'sort_order' => 7,
                'max_redemptions' => 30,
                'terms_conditions' => 'Παραλαμβάνεται από τη reception. Υπόκειται σε διαθεσιμότητα.',
            ],
        ];

        foreach ($rewards as $reward) {
            PointsReward::create($reward);
        }
    }
}
