<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CancellationPolicy;
use Carbon\Carbon;

class CancellationPoliciesSeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'name' => 'Βασική Πολιτική Ακύρωσης',
                'description' => 'Τυπική πολιτική ακύρωσης για ομαδικά μαθήματα',
                'type' => 'group_class',
                'rules' => json_encode([
                    [
                        'time_before' => 120, // 2 hours
                        'charge_percentage' => 0,
                        'description' => 'Δωρεάν ακύρωση μέχρι 2 ώρες πριν'
                    ],
                    [
                        'time_before' => 60, // 1 hour
                        'charge_percentage' => 25,
                        'description' => 'Χρέωση 25% για ακύρωση 1-2 ώρες πριν'
                    ],
                    [
                        'time_before' => 0, // Same day
                        'charge_percentage' => 50,
                        'description' => 'Χρέωση 50% για ακύρωση την ίδια μέρα'
                    ]
                ]),
                'no_show_charge' => 100,
                'max_free_cancellations' => 3,
                'reset_period' => 'monthly',
                'grace_period_hours' => 24,
                'instructor_override' => true,
                'admin_override' => true,
                'applies_to_packages' => json_encode(['membership', 'group']),
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(6),
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Personal Training Πολιτική',
                'description' => 'Αυστηρή πολιτική για προσωπικές προπονήσεις',
                'type' => 'personal_training',
                'rules' => json_encode([
                    [
                        'time_before' => 1440, // 24 hours
                        'charge_percentage' => 0,
                        'description' => 'Δωρεάν ακύρωση μέχρι 24 ώρες πριν'
                    ],
                    [
                        'time_before' => 360, // 6 hours
                        'charge_percentage' => 50,
                        'description' => 'Χρέωση 50% για ακύρωση 6-24 ώρες πριν'
                    ],
                    [
                        'time_before' => 0, // Same day
                        'charge_percentage' => 100,
                        'description' => 'Πλήρης χρέωση για ακύρωση εντός 6 ωρών'
                    ]
                ]),
                'no_show_charge' => 100,
                'max_free_cancellations' => 1,
                'reset_period' => 'monthly',
                'grace_period_hours' => 48,
                'instructor_override' => true,
                'admin_override' => true,
                'applies_to_packages' => json_encode(['personal']),
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(6),
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Premium Πολιτική',
                'description' => 'Ευνοϊκή πολιτική για premium μέλη',
                'type' => 'premium',
                'rules' => json_encode([
                    [
                        'time_before' => 60, // 1 hour
                        'charge_percentage' => 0,
                        'description' => 'Δωρεάν ακύρωση μέχρι 1 ώρα πριν'
                    ],
                    [
                        'time_before' => 30, // 30 minutes
                        'charge_percentage' => 20,
                        'description' => 'Χρέωση 20% για ακύρωση 30-60 λεπτά πριν'
                    ],
                    [
                        'time_before' => 0, // Same day
                        'charge_percentage' => 40,
                        'description' => 'Χρέωση 40% για ακύρωση εντός 30 λεπτών'
                    ]
                ]),
                'no_show_charge' => 75,
                'max_free_cancellations' => 5,
                'reset_period' => 'monthly',
                'grace_period_hours' => 12,
                'instructor_override' => true,
                'admin_override' => true,
                'applies_to_packages' => json_encode(['membership']),
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(3),
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'EMS Training Πολιτική',
                'description' => 'Ειδική πολιτική για EMS προπονήσεις',
                'type' => 'ems_training',
                'rules' => json_encode([
                    [
                        'time_before' => 720, // 12 hours
                        'charge_percentage' => 0,
                        'description' => 'Δωρεάν ακύρωση μέχρι 12 ώρες πριν'
                    ],
                    [
                        'time_before' => 240, // 4 hours
                        'charge_percentage' => 30,
                        'description' => 'Χρέωση 30% για ακύρωση 4-12 ώρες πριν'
                    ],
                    [
                        'time_before' => 0, // Same day
                        'charge_percentage' => 80,
                        'description' => 'Χρέωση 80% για ακύρωση εντός 4 ωρών'
                    ]
                ]),
                'no_show_charge' => 100,
                'max_free_cancellations' => 2,
                'reset_period' => 'monthly',
                'grace_period_hours' => 24,
                'instructor_override' => true,
                'admin_override' => true,
                'applies_to_packages' => json_encode(['ems']),
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(4),
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Δοκιμαστική Πολιτική',
                'description' => 'Ήπια πολιτική για δοκιμαστικά πακέτα',
                'type' => 'trial',
                'rules' => json_encode([
                    [
                        'time_before' => 240, // 4 hours
                        'charge_percentage' => 0,
                        'description' => 'Δωρεάν ακύρωση μέχρι 4 ώρες πριν'
                    ],
                    [
                        'time_before' => 120, // 2 hours
                        'charge_percentage' => 25,
                        'description' => 'Χρέωση 25% για ακύρωση 2-4 ώρες πριν'
                    ],
                    [
                        'time_before' => 0, // Same day
                        'charge_percentage' => 50,
                        'description' => 'Χρέωση 50% για ακύρωση εντός 2 ωρών'
                    ]
                ]),
                'no_show_charge' => 75,
                'max_free_cancellations' => 5,
                'reset_period' => 'monthly',
                'grace_period_hours' => 6,
                'instructor_override' => true,
                'admin_override' => true,
                'applies_to_packages' => json_encode(['trial']),
                'status' => 'active',
                'effective_date' => Carbon::now()->subMonths(2),
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Παλαιά Πολιτική (Αρχείο)',
                'description' => 'Προηγούμενη πολιτική που δεν ισχύει πλέον',
                'type' => 'legacy',
                'rules' => json_encode([
                    [
                        'time_before' => 60, // 1 hour
                        'charge_percentage' => 0,
                        'description' => 'Δωρεάν ακύρωση μέχρι 1 ώρα πριν'
                    ],
                    [
                        'time_before' => 0, // Same day
                        'charge_percentage' => 100,
                        'description' => 'Πλήρης χρέωση για ακύρωση εντός 1 ώρας'
                    ]
                ]),
                'no_show_charge' => 100,
                'max_free_cancellations' => 0,
                'reset_period' => 'monthly',
                'grace_period_hours' => 0,
                'instructor_override' => false,
                'admin_override' => true,
                'applies_to_packages' => json_encode(['membership', 'personal']),
                'status' => 'archived',
                'effective_date' => Carbon::now()->subYear(),
                'end_date' => Carbon::now()->subMonths(6),
                'created_by' => 1,
                'created_at' => Carbon::now()->subYear(),
                'updated_at' => Carbon::now()->subMonths(6),
            ],
        ];

        foreach ($policies as $policy) {
            CancellationPolicy::create($policy);
        }

        $this->command->info('Cancellation policies seeded successfully!');
        $this->command->info('- Total policies created: ' . count($policies));
        $this->command->info('- Policy types: group_class, personal_training, premium, ems_training, trial, legacy');
        $this->command->info('- Various charge percentages and time limits');
        $this->command->info('- Instructor and admin override options');
        $this->command->info('- Different free cancellation limits');
    }
}