<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'description' => 'Ακύρωση χωρίς χρέωση έως 24 ώρες πριν το μάθημα. Μετάθεση επιτρέπεται έως 12 ώρες πριν.',
                'hours_before' => 24,
                'penalty_percentage' => 50,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 12,
                'max_reschedules_per_month' => 3,
                'is_active' => true,
                'priority' => 1,
                'applicable_to' => json_encode(['class_types' => ['group']]),
            ],
            [
                'name' => 'Πολιτική Personal Training',
                'description' => 'Ακύρωση χωρίς χρέωση έως 48 ώρες πριν τη συνεδρία. Μετάθεση επιτρέπεται έως 24 ώρες πριν.',
                'hours_before' => 48,
                'penalty_percentage' => 100,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 24,
                'max_reschedules_per_month' => 2,
                'is_active' => true,
                'priority' => 2,
                'applicable_to' => json_encode(['class_types' => ['personal']]),
            ],
            [
                'name' => 'Πολιτική Premium Πακέτων',
                'description' => 'Ευέλικτη πολιτική για κατόχους premium πακέτων. Ακύρωση έως 6 ώρες πριν χωρίς χρέωση.',
                'hours_before' => 6,
                'penalty_percentage' => 0,
                'allow_reschedule' => true,
                'reschedule_hours_before' => 6,
                'max_reschedules_per_month' => 5,
                'is_active' => true,
                'priority' => 3,
                'applicable_to' => json_encode(['package_ids' => [5]]), // Premium Membership 6 μήνες
            ],
            [
                'name' => 'Αυστηρή Πολιτική Ειδικών Σεμιναρίων',
                'description' => 'Για ειδικά σεμινάρια και workshops. Δεν επιτρέπεται μετάθεση.',
                'hours_before' => 72,
                'penalty_percentage' => 100,
                'allow_reschedule' => false,
                'reschedule_hours_before' => null,
                'max_reschedules_per_month' => 0,
                'is_active' => true,
                'priority' => 4,
                'applicable_to' => json_encode(['class_types' => ['workshop', 'seminar']]),
            ],
        ];

        foreach ($policies as $policy) {
            CancellationPolicy::create($policy);
        }
    }
}