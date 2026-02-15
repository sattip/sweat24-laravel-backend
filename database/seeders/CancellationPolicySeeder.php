<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CancellationPolicy;

class CancellationPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policies = [
            [
                'name' => 'Βασική Πολιτική Ακύρωσης',
                'description' => 'Στάνταρ πολιτική ακύρωσης για ομαδικά μαθήματα. Ακύρωση χωρίς χρέωση έως 24 ώρες πριν το μάθημα.',
                'hours_before' => 24,
                'penalty_percentage' => 50.00,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 12,
                'max_reschedules_per_month' => 3,
                'priority' => 1,
                'is_active' => true,
                'applicable_to' => [
                    'class_types' => ['group', 'hiit', 'yoga', 'pilates'],
                    'package_ids' => []
                ]
            ],
            [
                'name' => 'Πολιτική Προσωπικής Προπόνησης',
                'description' => 'Αυστηρότερη πολιτική για προσωπικές προπονήσεις λόγω της προσωπικής δέσμευσης του εκπαιδευτή.',
                'hours_before' => 48,
                'penalty_percentage' => 75.00,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 24,
                'max_reschedules_per_month' => 2,
                'priority' => 2,
                'is_active' => true,
                'applicable_to' => [
                    'class_types' => ['personal'],
                    'package_ids' => []
                ]
            ],
            [
                'name' => 'EMS Training Πολιτική',
                'description' => 'Ειδική πολιτική για EMS sessions που απαιτούν εξειδικευμένο εξοπλισμό και προετοιμασία.',
                'hours_before' => 36,
                'penalty_percentage' => 60.00,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 18,
                'max_reschedules_per_month' => 2,
                'priority' => 3,
                'is_active' => true,
                'applicable_to' => [
                    'class_types' => ['ems'],
                    'package_ids' => []
                ]
            ],
            [
                'name' => 'Ευέλικτη Πολιτική VIP',
                'description' => 'Ευέλικτη πολιτική για VIP μέλη με περισσότερες δυνατότητες μετάθεσης.',
                'hours_before' => 6,
                'penalty_percentage' => 25.00,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 3,
                'max_reschedules_per_month' => 5,
                'priority' => 4,
                'is_active' => false, // Απενεργοποιημένη από προεπιλογή
                'applicable_to' => [
                    'class_types' => [],
                    'package_ids' => [] // Θα συμπληρωθούν VIP package IDs
                ]
            ],
            [
                'name' => 'Αυστηρή Πολιτική',
                'description' => 'Αυστηρή πολιτική για ειδικά μαθήματα ή περιόδους υψηλής ζήτησης.',
                'hours_before' => 72,
                'penalty_percentage' => 100.00,
                'allow_reschedule' => false,
                'reschedule_hours_before' => null,
                'max_reschedules_per_month' => 0,
                'priority' => 5,
                'is_active' => false,
                'applicable_to' => [
                    'class_types' => [],
                    'package_ids' => []
                ]
            ]
        ];

        foreach ($policies as $policyData) {
            CancellationPolicy::updateOrCreate(
                ['name' => $policyData['name']],
                $policyData
            );
        }

        $this->command->info('Δημιουργήθηκαν ' . count($policies) . ' πολιτικές ακύρωσης.');
    }
}