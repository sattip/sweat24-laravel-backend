<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Package;
use App\Models\Instructor;
use App\Models\GymClass;
use App\Models\Booking;
use App\Models\UserPackage;
use App\Models\PaymentInstallment;
use App\Models\CashRegisterEntry;
use App\Models\BusinessExpense;
use App\Models\WorkTimeEntry;
use App\Models\PayrollAgreement;
use Carbon\Carbon;

class GymDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create Packages
        $packages = [
            [
                'name' => 'Personal Training - 12 Συνεδρίες',
                'price' => 300.00,
                'sessions' => 12,
                'duration' => 90,
                'type' => 'Personal',
                'status' => 'active',
                'description' => 'Εξατομικευμένες προπονήσεις'
            ],
            [
                'name' => 'Group Fitness Pass - 1 Μήνας',
                'price' => 80.00,
                'sessions' => null,
                'duration' => 30,
                'type' => 'Group',
                'status' => 'active',
                'description' => 'Απεριόριστες ομαδικές προπονήσεις'
            ],
            [
                'name' => 'Yoga & Pilates - 10 Συνεδρίες',
                'price' => 150.00,
                'sessions' => 10,
                'duration' => 60,
                'type' => 'Yoga/Pilates',
                'status' => 'active',
                'description' => 'Χαλάρωση και ευελιξία'
            ],
            [
                'name' => 'Basic Membership 1 μήνας',
                'price' => 50.00,
                'sessions' => null,
                'duration' => 30,
                'type' => 'membership',
                'status' => 'active',
                'description' => 'Βασική συνδρομή για ομαδικά μαθήματα'
            ],
            [
                'name' => 'Premium Membership 6 μήνες',
                'price' => 300.00,
                'sessions' => null,
                'duration' => 180,
                'type' => 'membership',
                'status' => 'active',
                'description' => 'Premium πρόσβαση σε όλες τις υπηρεσίες'
            ]
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }

        // Create Instructors
        $instructors = [
            [
                'name' => 'Άλεξ Ροδρίγκεζ',
                'specialties' => json_encode(['HIIT', 'Strength']),
                'email' => 'alex@sweat24.com',
                'phone' => '6944111222',
                'hourly_rate' => 25.00,
                'monthly_bonus' => 100.00,
                'commission_rate' => 0.10,
                'contract_type' => 'hourly',
                'status' => 'active',
                'join_date' => '2020-03-15',
                'total_revenue' => 4500.00,
                'completed_sessions' => 180
            ],
            [
                'name' => 'Εμιλι Τσεν',
                'specialties' => json_encode(['Yoga', 'Pilates']),
                'email' => 'emily@sweat24.com',
                'phone' => '6955222333',
                'hourly_rate' => 30.00,
                'commission_rate' => 0.15,
                'contract_type' => 'commission',
                'status' => 'active',
                'join_date' => '2021-01-20',
                'total_revenue' => 3800.00,
                'completed_sessions' => 145
            ],
            [
                'name' => 'Τζέιμς Τέιλορ',
                'specialties' => json_encode(['Personal Training', 'Powerlifting']),
                'email' => 'james@sweat24.com',
                'phone' => '6966333444',
                'hourly_rate' => 35.00,
                'monthly_bonus' => 150.00,
                'contract_type' => 'salary',
                'status' => 'active',
                'join_date' => '2019-08-10',
                'total_revenue' => 6200.00,
                'completed_sessions' => 220
            ]
        ];

        foreach ($instructors as $instructor) {
            Instructor::create($instructor);
        }

        // Create Admin User
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@sweat24.com',
            'password' => bcrypt('password'),
            'phone' => '6900000000',
            'membership_type' => 'Admin',
            'join_date' => now()->subYear(),
            'status' => 'active'
        ]);

        // Create Sample Users/Members
        $users = [
            [
                'name' => 'Γιάννης Παπαδόπουλος',
                'email' => 'giannis@email.com',
                'password' => bcrypt('password'),
                'phone' => '6944123456',
                'membership_type' => 'Premium',
                'join_date' => '2024-01-15',
                'remaining_sessions' => 8,
                'total_sessions' => 20,
                'status' => 'active',
                'last_visit' => '2024-05-20',
                'medical_history' => 'Χωρίς ιδιαίτερες παθήσεις'
            ],
            [
                'name' => 'Μαρία Κωνσταντίνου',
                'email' => 'maria@email.com',
                'password' => bcrypt('password'),
                'phone' => '6955234567',
                'membership_type' => 'Basic',
                'join_date' => '2024-02-10',
                'remaining_sessions' => 3,
                'total_sessions' => 10,
                'status' => 'active',
                'last_visit' => '2024-05-18',
                'medical_history' => 'Πρόβλημα στη μέση'
            ],
            [
                'name' => 'Κώστας Δημητρίου',
                'email' => 'kostas@email.com',
                'password' => bcrypt('password'),
                'phone' => '6966345678',
                'membership_type' => 'Premium',
                'join_date' => '2024-03-05',
                'remaining_sessions' => 0,
                'total_sessions' => 15,
                'status' => 'expired',
                'last_visit' => '2024-05-10',
                'medical_history' => 'Αθλητικός τραυματισμός γόνατος'
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Create Classes for the next 7 days
        $classes = [];
        $classTemplates = [
            ['name' => 'HIIT Blast', 'type' => 'group', 'instructor' => 'Άλεξ Ροδρίγκεζ', 'time' => '09:00:00', 'duration' => 45, 'max' => 12],
            ['name' => 'Yoga Flow', 'type' => 'group', 'instructor' => 'Εμιλι Τσεν', 'time' => '07:30:00', 'duration' => 60, 'max' => 15],
            ['name' => 'Power Training', 'type' => 'group', 'instructor' => 'Τζέιμς Τέιλορ', 'time' => '18:00:00', 'duration' => 60, 'max' => 10],
            ['name' => 'Morning Yoga', 'type' => 'group', 'instructor' => 'Εμιλι Τσεν', 'time' => '08:00:00', 'duration' => 45, 'max' => 20],
            ['name' => 'Strength Circuit', 'type' => 'group', 'instructor' => 'Άλεξ Ροδρίγκεζ', 'time' => '17:30:00', 'duration' => 50, 'max' => 12],
            ['name' => 'Personal Training', 'type' => 'personal', 'instructor' => 'Τζέιμς Τέιλορ', 'time' => '10:00:00', 'duration' => 60, 'max' => 1],
            ['name' => 'Evening HIIT', 'type' => 'group', 'instructor' => 'Άλεξ Ροδρίγκεζ', 'time' => '19:00:00', 'duration' => 45, 'max' => 15],
            ['name' => 'Core & Abs', 'type' => 'group', 'instructor' => 'Τζέιμς Τέιλορ', 'time' => '12:00:00', 'duration' => 30, 'max' => 20],
        ];
        
        // Generate classes for next 7 days
        for ($day = 0; $day < 7; $day++) {
            // Add 2-4 classes per day
            $numClasses = rand(2, 4);
            $usedTemplates = [];
            
            for ($i = 0; $i < $numClasses; $i++) {
                do {
                    $templateIndex = rand(0, count($classTemplates) - 1);
                } while (in_array($templateIndex, $usedTemplates));
                
                $usedTemplates[] = $templateIndex;
                $template = $classTemplates[$templateIndex];
                
                // Make some classes full for testing waitlist
                $participants = rand(0, $template['max']);
                // 30% chance of class being full
                if (rand(1, 100) <= 30) {
                    $participants = $template['max'];
                }
                
                $classes[] = [
                    'name' => $template['name'],
                    'type' => $template['type'],
                    'instructor' => $template['instructor'],
                    'date' => today()->addDays($day),
                    'time' => $template['time'],
                    'duration' => $template['duration'],
                    'max_participants' => $template['max'],
                    'current_participants' => $participants,
                    'location' => $template['type'] === 'personal' ? 'Personal Training Area' : 'Main Floor',
                    'description' => 'Προπόνηση ' . $template['name'],
                    'status' => 'active'
                ];
            }
        }

        foreach ($classes as $class) {
            GymClass::create($class);
        }

        // Create Payment Installments
        $installments = [
            [
                'customer_id' => 2,
                'customer_name' => 'Γιάννης Παπαδόπουλος',
                'package_id' => 5,
                'package_name' => 'Premium Membership 6 μήνες',
                'installment_number' => 1,
                'total_installments' => 3,
                'amount' => 100.00,
                'due_date' => today()->addDays(15),
                'status' => 'pending'
            ],
            [
                'customer_id' => 3,
                'customer_name' => 'Μαρία Κωνσταντίνου',
                'package_id' => 3,
                'package_name' => 'Yoga & Pilates - 10 Συνεδρίες',
                'installment_number' => 1,
                'total_installments' => 1,
                'amount' => 150.00,
                'due_date' => today()->subDays(5),
                'status' => 'overdue'
            ]
        ];

        foreach ($installments as $installment) {
            PaymentInstallment::create($installment);
        }

        // Create Cash Register Entries
        $cashEntries = [
            [
                'type' => 'income',
                'amount' => 200.00,
                'description' => 'Πληρωμή πακέτου - Μαρία Παπαδοπούλου',
                'category' => 'Package Payment',
                'user_id' => 1,
                'payment_method' => 'card',
                'related_entity_id' => '2',
                'related_entity_type' => 'customer'
            ],
            [
                'type' => 'income',
                'amount' => 50.00,
                'description' => 'Personal Training - Άλεξ Ροδρίγκεζ',
                'category' => 'Personal Training',
                'user_id' => 1,
                'payment_method' => 'cash',
                'related_entity_id' => '2',
                'related_entity_type' => 'customer'
            ],
            [
                'type' => 'withdrawal',
                'amount' => 500.00,
                'description' => 'Ανάληψη ιδιοκτήτη',
                'category' => 'Owner Withdrawal',
                'user_id' => 1,
                'payment_method' => 'cash'
            ]
        ];

        foreach ($cashEntries as $entry) {
            CashRegisterEntry::create($entry);
        }

        // Create Business Expenses
        $expenses = [
            [
                'category' => 'utilities',
                'subcategory' => 'Ηλεκτρικό ρεύμα',
                'description' => 'Λογαριασμός ΔΕΗ Μαΐου',
                'amount' => 320.00,
                'date' => today()->subDays(7),
                'vendor' => 'ΔΕΗ',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin'
            ],
            [
                'category' => 'equipment',
                'subcategory' => 'Συντήρηση μηχανημάτων',
                'description' => 'Επισκευή treadmill #3',
                'amount' => 150.00,
                'date' => today()->subDays(5),
                'vendor' => 'TechnoGym Service',
                'payment_method' => 'cash',
                'approved' => true,
                'approved_by' => 'admin'
            ]
        ];

        foreach ($expenses as $expense) {
            BusinessExpense::create($expense);
        }
    }
}