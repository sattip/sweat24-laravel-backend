<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessExpense;
use App\Models\User;
use Carbon\Carbon;

class EnhancedBusinessExpensesSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::where('role', 'admin')->get();
        
        if ($admins->isEmpty()) {
            $this->command->warn('No admins found. Please run ComprehensiveUsersSeeder first.');
            return;
        }

        $expenses = [];
        
        // Generate expenses for the last 90 days
        for ($day = 89; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day);
            
            // Generate monthly recurring expenses
            if ($date->day === 1) {
                $monthlyExpenses = $this->generateMonthlyRecurringExpenses($date, $admins);
                $expenses = array_merge($expenses, $monthlyExpenses);
            }
            
            // Generate weekly recurring expenses
            if ($date->dayOfWeek === 1) { // Monday
                $weeklyExpenses = $this->generateWeeklyRecurringExpenses($date, $admins);
                $expenses = array_merge($expenses, $weeklyExpenses);
            }
            
            // Generate random daily expenses
            $dailyExpenses = $this->generateDailyExpenses($date, $admins);
            $expenses = array_merge($expenses, $dailyExpenses);
        }

        // Generate some seasonal/special expenses
        $seasonalExpenses = $this->generateSeasonalExpenses($admins);
        $expenses = array_merge($expenses, $seasonalExpenses);

        // Insert expenses in batches
        $chunks = array_chunk($expenses, 50);
        foreach ($chunks as $chunk) {
            BusinessExpense::insert($chunk);
        }

        // Calculate statistics
        $totalExpenses = collect($expenses)->sum('amount');
        $approvedExpenses = collect($expenses)->where('approved', true)->sum('amount');
        $categoryBreakdown = collect($expenses)->groupBy('category')->map(function($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount')
            ];
        });

        $this->command->info('Enhanced business expenses seeded successfully!');
        $this->command->info('- Total expenses created: ' . count($expenses));
        $this->command->info('- Total expense amount: €' . number_format($totalExpenses, 2));
        $this->command->info('- Approved expenses: €' . number_format($approvedExpenses, 2));
        $this->command->info('- Category breakdown:');
        foreach ($categoryBreakdown as $category => $data) {
            $this->command->info("  - {$category}: {$data['count']} items, €" . number_format($data['total'], 2));
        }
    }

    private function generateMonthlyRecurringExpenses($date, $admins)
    {
        $expenses = [];
        $admin = $admins->first();
        
        $monthlyExpenses = [
            [
                'category' => 'utilities',
                'subcategory' => 'Ηλεκτρικό ρεύμα',
                'description' => 'Λογαριασμός ΔΕΗ ' . $date->format('F Y'),
                'amount' => rand(280, 450),
                'vendor' => 'ΔΕΗ',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
            ],
            [
                'category' => 'utilities',
                'subcategory' => 'Νερό',
                'description' => 'Λογαριασμός ΕΥΔΑΠ ' . $date->format('F Y'),
                'amount' => rand(80, 150),
                'vendor' => 'ΕΥΔΑΠ',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
            ],
            [
                'category' => 'utilities',
                'subcategory' => 'Αέριο',
                'description' => 'Λογαριασμός φυσικού αερίου ' . $date->format('F Y'),
                'amount' => rand(120, 250),
                'vendor' => 'ΔΕΔΑ',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
            ],
            [
                'category' => 'utilities',
                'subcategory' => 'Internet/Τηλέφωνα',
                'description' => 'Λογαριασμός τηλεπικοινωνιών ' . $date->format('F Y'),
                'amount' => rand(60, 120),
                'vendor' => 'Cosmote',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
            ],
            [
                'category' => 'rent',
                'subcategory' => 'Ενοίκιο χώρου',
                'description' => 'Μηνιαίο ενοίκιο ' . $date->format('F Y'),
                'amount' => rand(2000, 3500),
                'vendor' => 'Ιδιοκτήτης ακινήτου',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
            ],
            [
                'category' => 'insurance',
                'subcategory' => 'Ασφάλεια επιχείρησης',
                'description' => 'Μηνιαία ασφάλεια ' . $date->format('F Y'),
                'amount' => rand(150, 300),
                'vendor' => 'Ασφαλιστική εταιρία',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
            ],
            [
                'category' => 'payroll',
                'subcategory' => 'Μισθοί προσωπικού',
                'description' => 'Μισθοδοσία ' . $date->format('F Y'),
                'amount' => rand(3500, 6000),
                'vendor' => 'Εσωτερικό',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
            ],
            [
                'category' => 'payroll',
                'subcategory' => 'Ασφαλιστικές εισφορές',
                'description' => 'Εισφορές ΙΚΑ ' . $date->format('F Y'),
                'amount' => rand(800, 1400),
                'vendor' => 'ΙΚΑ/ΕΦΚΑ',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
            ],
        ];
        
        foreach ($monthlyExpenses as $expenseData) {
            $expenses[] = array_merge($expenseData, [
                'date' => $date->copy()->addDays(rand(1, 5)),
                'user_id' => $admin->id,
                'created_at' => $date->copy()->addDays(rand(1, 5)),
                'updated_at' => $date->copy()->addDays(rand(1, 5)),
            ]);
        }
        
        return $expenses;
    }

    private function generateWeeklyRecurringExpenses($date, $admins)
    {
        $expenses = [];
        $admin = $admins->random();
        
        $weeklyExpenses = [
            [
                'category' => 'maintenance',
                'subcategory' => 'Καθαρισμός',
                'description' => 'Εβδομαδιαίος καθαρισμός εγκαταστάσεων',
                'amount' => rand(120, 200),
                'vendor' => 'Εταιρία καθαρισμού',
                'payment_method' => 'cash',
                'approved' => true,
                'approved_by' => 'admin',
            ],
            [
                'category' => 'supplies',
                'subcategory' => 'Καθαριστικά',
                'description' => 'Εβδομαδιαία αναπλήρωση καθαριστικών',
                'amount' => rand(60, 120),
                'vendor' => 'CleanPro',
                'payment_method' => 'cash',
                'approved' => true,
                'approved_by' => 'admin',
            ],
        ];
        
        foreach ($weeklyExpenses as $expenseData) {
            $expenses[] = array_merge($expenseData, [
                'date' => $date->copy()->addDays(rand(0, 6)),
                'user_id' => $admin->id,
                'created_at' => $date->copy()->addDays(rand(0, 6)),
                'updated_at' => $date->copy()->addDays(rand(0, 6)),
            ]);
        }
        
        return $expenses;
    }

    private function generateDailyExpenses($date, $admins)
    {
        $expenses = [];
        
        // Random daily expenses (30% chance per day)
        if (rand(1, 100) <= 30) {
            $admin = $admins->random();
            
            $dailyExpenses = [
                [
                    'category' => 'equipment',
                    'subcategory' => 'Συντήρηση μηχανημάτων',
                    'amounts' => [50, 150, 300, 500],
                    'descriptions' => [
                        'Επισκευή treadmill',
                        'Συντήρηση ποδηλάτων',
                        'Αντικατάσταση καλωδίων',
                        'Βαθμονόμηση μηχανημάτων'
                    ],
                    'vendors' => ['TechnoGym Service', 'Matrix Support', 'Life Fitness', 'Τεχνικός συντήρησης'],
                    'payment_methods' => ['cash', 'card', 'transfer'],
                ],
                [
                    'category' => 'supplies',
                    'subcategory' => 'Αναλώσιμα',
                    'amounts' => [20, 50, 100, 200],
                    'descriptions' => [
                        'Χαρτικά γραφείου',
                        'Πετσέτες και υλικά',
                        'Πρώτες βοήθειες',
                        'Καλλυντικά αποδυτηρίων'
                    ],
                    'vendors' => ['Office Depot', 'Τοπικός προμηθευτής', 'Φαρμακείο', 'Wholesale supplier'],
                    'payment_methods' => ['cash', 'card'],
                ],
                [
                    'category' => 'marketing',
                    'subcategory' => 'Διαφήμιση',
                    'amounts' => [100, 250, 500, 1000],
                    'descriptions' => [
                        'Google Ads καμπάνια',
                        'Facebook διαφήμιση',
                        'Τοπικές εφημερίδες',
                        'Φυλλάδια και εκτυπώσεις'
                    ],
                    'vendors' => ['Google', 'Facebook', 'Τοπικό μέσο', 'Τυπογραφείο'],
                    'payment_methods' => ['card', 'transfer'],
                ],
                [
                    'category' => 'food_beverages',
                    'subcategory' => 'Καφές/Αναψυκτικά',
                    'amounts' => [30, 60, 120, 180],
                    'descriptions' => [
                        'Καφές για πελάτες',
                        'Αναψυκτικά και νερά',
                        'Πρωτεϊνούχα ποτά',
                        'Υγιεινά snacks'
                    ],
                    'vendors' => ['Καφεκοπτεία', 'Supermarket', 'Διανομέας', 'Προμηθευτής'],
                    'payment_methods' => ['cash', 'card'],
                ],
                [
                    'category' => 'professional_services',
                    'subcategory' => 'Επαγγελματικές υπηρεσίες',
                    'amounts' => [100, 300, 600, 1200],
                    'descriptions' => [
                        'Λογιστικές υπηρεσίες',
                        'Νομικές συμβουλές',
                        'Φοροτεχνικές υπηρεσίες',
                        'Συμβουλευτικές υπηρεσίες'
                    ],
                    'vendors' => ['Λογιστής', 'Δικηγόρος', 'Φοροτεχνικός', 'Σύμβουλος'],
                    'payment_methods' => ['transfer', 'card'],
                ],
                [
                    'category' => 'transportation',
                    'subcategory' => 'Μεταφορά',
                    'amounts' => [25, 50, 100, 200],
                    'descriptions' => [
                        'Καύσιμα υπηρεσιακού οχήματος',
                        'Παραδόσεις εξοπλισμού',
                        'Μεταφορά υλικών',
                        'Courier υπηρεσίες'
                    ],
                    'vendors' => ['Πρατήριο καυσίμων', 'Μεταφορική', 'Courier', 'Ταξί'],
                    'payment_methods' => ['cash', 'card'],
                ],
                [
                    'category' => 'training',
                    'subcategory' => 'Εκπαίδευση',
                    'amounts' => [150, 300, 600, 1000],
                    'descriptions' => [
                        'Σεμινάρια προπονητών',
                        'Πιστοποιήσεις',
                        'Εκπαιδευτικά βιβλία',
                        'Online courses'
                    ],
                    'vendors' => ['Εκπαιδευτικός οργανισμός', 'Πιστοποιητικός φορέας', 'Βιβλιοπωλείο', 'Online platform'],
                    'payment_methods' => ['card', 'transfer'],
                ],
                [
                    'category' => 'security',
                    'subcategory' => 'Ασφάλεια',
                    'amounts' => [80, 150, 300, 500],
                    'descriptions' => [
                        'Συντήρηση συστήματος συναγερμού',
                        'Κάμερες παρακολούθησης',
                        'Κλειδαριές και ασφάλεια',
                        'Φύλαξη νυχτερινή'
                    ],
                    'vendors' => ['Εταιρία ασφαλείας', 'Τεχνικός', 'Κλειδαράς', 'Security company'],
                    'payment_methods' => ['cash', 'transfer'],
                ],
                [
                    'category' => 'other',
                    'subcategory' => 'Διάφορα',
                    'amounts' => [30, 80, 150, 300],
                    'descriptions' => [
                        'Μικροεπισκευές',
                        'Εκτυπώσεις',
                        'Ταχυδρομικά',
                        'Λοιπά έξοδα'
                    ],
                    'vendors' => ['Διάφοροι', 'Τυπογραφείο', 'ΕΛΤΑ', 'Λοιποί'],
                    'payment_methods' => ['cash', 'card'],
                ],
            ];
            
            $expenseType = $dailyExpenses[array_rand($dailyExpenses)];
            $amount = $expenseType['amounts'][array_rand($expenseType['amounts'])];
            $description = $expenseType['descriptions'][array_rand($expenseType['descriptions'])];
            $vendor = $expenseType['vendors'][array_rand($expenseType['vendors'])];
            $paymentMethod = $expenseType['payment_methods'][array_rand($expenseType['payment_methods'])];
            
            // Some expenses need approval
            $needsApproval = $amount > 200 || rand(1, 100) <= 20;
            $approved = $needsApproval ? (rand(1, 100) <= 85) : true;
            
            $expenses[] = [
                'category' => $expenseType['category'],
                'subcategory' => $expenseType['subcategory'],
                'description' => $description,
                'amount' => $amount,
                'date' => $date->copy()->addHours(rand(8, 20)),
                'vendor' => $vendor,
                'payment_method' => $paymentMethod,
                'approved' => $approved,
                'approved_by' => $approved ? 'admin' : null,
                'notes' => $needsApproval && !$approved ? 'Εκκρεμεί έγκριση' : null,
                'user_id' => $admin->id,
                'created_at' => $date->copy()->addHours(rand(8, 20)),
                'updated_at' => $date->copy()->addHours(rand(8, 20)),
            ];
        }
        
        return $expenses;
    }

    private function generateSeasonalExpenses($admins)
    {
        $expenses = [];
        $admin = $admins->first();
        
        // Winter heating expenses (December-February)
        for ($month = 12; $month <= 12; $month++) {
            $date = Carbon::now()->subMonths(rand(0, 3))->setMonth($month);
            
            $expenses[] = [
                'category' => 'utilities',
                'subcategory' => 'Θέρμανση',
                'description' => 'Έξτρα θέρμανση - Χειμώνας',
                'amount' => rand(300, 600),
                'date' => $date,
                'vendor' => 'Προμηθευτής καυσίμων',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
                'user_id' => $admin->id,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
        
        // Summer cooling expenses (June-August)
        for ($month = 6; $month <= 8; $month++) {
            $date = Carbon::now()->subMonths(rand(0, 8))->setMonth($month);
            
            $expenses[] = [
                'category' => 'utilities',
                'subcategory' => 'Κλιματισμός',
                'description' => 'Έξτρα κλιματισμός - Καλοκαίρι',
                'amount' => rand(200, 400),
                'date' => $date,
                'vendor' => 'ΔΕΗ',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
                'user_id' => $admin->id,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
        
        // Holiday/special event expenses
        $specialEvents = [
            [
                'date' => Carbon::now()->subDays(rand(30, 90)),
                'description' => 'Χριστουγεννιάτικη διακόσμηση',
                'amount' => rand(150, 400),
                'category' => 'marketing',
                'subcategory' => 'Εκδηλώσεις',
            ],
            [
                'date' => Carbon::now()->subDays(rand(10, 60)),
                'description' => 'Πρωτοχρονιάτικη εκδήλωση',
                'amount' => rand(200, 500),
                'category' => 'marketing',
                'subcategory' => 'Εκδηλώσεις',
            ],
            [
                'date' => Carbon::now()->subDays(rand(5, 30)),
                'description' => 'Καρναβαλάτικη εκδήλωση',
                'amount' => rand(100, 300),
                'category' => 'marketing',
                'subcategory' => 'Εκδηλώσεις',
            ],
        ];
        
        foreach ($specialEvents as $event) {
            $expenses[] = array_merge($event, [
                'vendor' => 'Event organizer',
                'payment_method' => 'transfer',
                'approved' => true,
                'approved_by' => 'admin',
                'user_id' => $admin->id,
                'created_at' => $event['date'],
                'updated_at' => $event['date'],
            ]);
        }
        
        return $expenses;
    }
}