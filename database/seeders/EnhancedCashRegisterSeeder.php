<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashRegisterEntry;
use App\Models\User;
use Carbon\Carbon;

class EnhancedCashRegisterSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::where('role', 'member')->get();
        $admins = User::where('role', 'admin')->get();
        
        if ($members->isEmpty() || $admins->isEmpty()) {
            $this->command->warn('No members or admins found. Please run ComprehensiveUsersSeeder first.');
            return;
        }

        $entries = [];
        
        // Generate entries for the last 60 days
        for ($day = 59; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day);
            
            // Generate daily entries
            $dailyEntries = $this->generateDailyEntries($date, $members, $admins);
            $entries = array_merge($entries, $dailyEntries);
        }

        // Insert entries in batches
        $chunks = array_chunk($entries, 50);
        foreach ($chunks as $chunk) {
            CashRegisterEntry::insert($chunk);
        }

        // Calculate totals
        $totalIncome = collect($entries)->where('type', 'income')->sum('amount');
        $totalWithdrawals = collect($entries)->where('type', 'withdrawal')->sum('amount');
        $netAmount = $totalIncome - $totalWithdrawals;

        $this->command->info('Enhanced cash register entries seeded successfully!');
        $this->command->info('- Total entries created: ' . count($entries));
        $this->command->info('- Total income: €' . number_format($totalIncome, 2));
        $this->command->info('- Total withdrawals: €' . number_format($totalWithdrawals, 2));
        $this->command->info('- Net amount: €' . number_format($netAmount, 2));
        $this->command->info('- Period: Last 60 days');
    }

    private function generateDailyEntries($date, $members, $admins)
    {
        $entries = [];
        $dayOfWeek = $date->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]);
        
        // Start of day balance setup (only for first day)
        if ($date->isToday() && $date->subDays(59)->isToday()) {
            $entries[] = [
                'type' => 'income',
                'amount' => 500.00,
                'description' => 'Υπόλοιπο έναρξης ημέρας',
                'category' => 'Opening Balance',
                'user_id' => $admins->first()->id,
                'payment_method' => 'cash',
                'created_at' => $date->copy()->setHour(8)->setMinute(0),
                'updated_at' => $date->copy()->setHour(8)->setMinute(0),
            ];
        }

        // Package payments (main income source)
        $packagePayments = $this->generatePackagePayments($date, $members, $admins, $isWeekend);
        $entries = array_merge($entries, $packagePayments);

        // Personal training payments
        $personalTrainingPayments = $this->generatePersonalTrainingPayments($date, $members, $admins, $isWeekend);
        $entries = array_merge($entries, $personalTrainingPayments);

        // Retail sales
        $retailSales = $this->generateRetailSales($date, $members, $admins, $isWeekend);
        $entries = array_merge($entries, $retailSales);

        // Additional services
        $additionalServices = $this->generateAdditionalServices($date, $members, $admins, $isWeekend);
        $entries = array_merge($entries, $additionalServices);

        // Day passes and trial payments
        $dayPasses = $this->generateDayPasses($date, $members, $admins, $isWeekend);
        $entries = array_merge($entries, $dayPasses);

        // Refunds and adjustments
        $refunds = $this->generateRefunds($date, $members, $admins);
        $entries = array_merge($entries, $refunds);

        // Owner withdrawals
        $ownerWithdrawals = $this->generateOwnerWithdrawals($date, $admins);
        $entries = array_merge($entries, $ownerWithdrawals);

        // Operational expenses paid from cash
        $operationalExpenses = $this->generateOperationalExpenses($date, $admins);
        $entries = array_merge($entries, $operationalExpenses);

        // Petty cash adjustments
        $pettyCashAdjustments = $this->generatePettyCashAdjustments($date, $admins);
        $entries = array_merge($entries, $pettyCashAdjustments);

        return $entries;
    }

    private function generatePackagePayments($date, $members, $admins, $isWeekend)
    {
        $entries = [];
        $count = $isWeekend ? rand(2, 5) : rand(4, 10);
        
        $packages = [
            'Basic Membership 1 μήνας' => ['price' => 50, 'installments' => false],
            'Basic Membership 3 μήνες' => ['price' => 140, 'installments' => true],
            'Premium Membership 6 μήνες' => ['price' => 300, 'installments' => true],
            'Personal Training 4 συνεδρίες' => ['price' => 160, 'installments' => false],
            'Personal Training 8 συνεδρίες' => ['price' => 300, 'installments' => true],
            'Personal Training 12 συνεδρίες' => ['price' => 420, 'installments' => true],
            'Yoga & Pilates 10 συνεδρίες' => ['price' => 150, 'installments' => false],
            'EMS Training 6 συνεδρίες' => ['price' => 180, 'installments' => false],
            'Student Package 1 μήνας' => ['price' => 35, 'installments' => false],
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $admin = $admins->random();
            $packageName = array_keys($packages)[array_rand($packages)];
            $packageInfo = $packages[$packageName];
            
            // Determine if this is a full payment or installment
            $isInstallment = $packageInfo['installments'] && rand(1, 100) <= 40;
            
            if ($isInstallment) {
                $amount = $packageInfo['price'] / rand(2, 4); // Split into 2-4 installments
                $description = "Δόση πακέτου: {$packageName} - {$member->name}";
            } else {
                $amount = $packageInfo['price'];
                $description = "Πληρωμή πακέτου: {$packageName} - {$member->name}";
            }
            
            // Apply random discount occasionally
            if (rand(1, 100) <= 15) {
                $discount = rand(5, 25);
                $amount = $amount * (1 - $discount / 100);
                $description .= " (Έκπτωση {$discount}%)";
            }
            
            $entries[] = [
                'type' => 'income',
                'amount' => round($amount, 2),
                'description' => $description,
                'category' => 'Package Payment',
                'user_id' => $admin->id,
                'payment_method' => collect(['cash', 'card', 'transfer'])->random(),
                'related_entity_id' => $member->id,
                'related_entity_type' => 'customer',
                'created_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }

    private function generatePersonalTrainingPayments($date, $members, $admins, $isWeekend)
    {
        $entries = [];
        $count = $isWeekend ? rand(1, 3) : rand(2, 6);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $admin = $admins->random();
            $amount = rand(30, 50); // Per session
            
            $trainers = ['Άλεξ Ροδρίγκεζ', 'Εμιλι Τσεν', 'Τζέιμς Τέιλορ', 'Σάρα Τζόνσον'];
            $trainer = $trainers[array_rand($trainers)];
            
            $entries[] = [
                'type' => 'income',
                'amount' => $amount,
                'description' => "Personal Training με {$trainer} - {$member->name}",
                'category' => 'Personal Training',
                'user_id' => $admin->id,
                'payment_method' => collect(['cash', 'card'])->random(),
                'related_entity_id' => $member->id,
                'related_entity_type' => 'customer',
                'created_at' => $date->copy()->addHours(rand(7, 21))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(7, 21))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }

    private function generateRetailSales($date, $members, $admins, $isWeekend)
    {
        $entries = [];
        $count = $isWeekend ? rand(1, 4) : rand(2, 8);
        
        $products = [
            'Protein Shake' => 8.00,
            'Energy Bar' => 3.50,
            'Γάντια προπόνησης' => 15.00,
            'Πετσέτα γυμναστηρίου' => 12.00,
            'Φιάλη νερού' => 2.50,
            'Pre-workout supplement' => 25.00,
            'Vitamins' => 18.00,
            'Resistance band' => 10.00,
            'Yoga mat' => 30.00,
            'Shaker' => 6.00,
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $admin = $admins->random();
            $productName = array_keys($products)[array_rand($products)];
            $price = $products[$productName];
            $quantity = rand(1, 3);
            $amount = $price * $quantity;
            
            $description = $quantity > 1 ? 
                "Πώληση: {$quantity}x {$productName} - {$member->name}" : 
                "Πώληση: {$productName} - {$member->name}";
            
            $entries[] = [
                'type' => 'income',
                'amount' => $amount,
                'description' => $description,
                'category' => 'Retail Sales',
                'user_id' => $admin->id,
                'payment_method' => collect(['cash', 'card'])->random(),
                'related_entity_id' => $member->id,
                'related_entity_type' => 'customer',
                'created_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }

    private function generateAdditionalServices($date, $members, $admins, $isWeekend)
    {
        $entries = [];
        $count = $isWeekend ? rand(0, 2) : rand(1, 4);
        
        $services = [
            'Διατροφική συμβουλή' => 40.00,
            'Body composition analysis' => 20.00,
            'Fitness assessment' => 30.00,
            'Massage therapy' => 50.00,
            'Φυσιοθεραπεία' => 45.00,
            'Meal prep planning' => 35.00,
            'Workout plan creation' => 25.00,
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $admin = $admins->random();
            $serviceName = array_keys($services)[array_rand($services)];
            $amount = $services[$serviceName];
            
            $entries[] = [
                'type' => 'income',
                'amount' => $amount,
                'description' => "Υπηρεσία: {$serviceName} - {$member->name}",
                'category' => 'Additional Services',
                'user_id' => $admin->id,
                'payment_method' => collect(['cash', 'card'])->random(),
                'related_entity_id' => $member->id,
                'related_entity_type' => 'customer',
                'created_at' => $date->copy()->addHours(rand(9, 20))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 20))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }

    private function generateDayPasses($date, $members, $admins, $isWeekend)
    {
        $entries = [];
        $count = $isWeekend ? rand(1, 5) : rand(2, 8);
        
        $dayPassPrice = 12.00;
        $trialPrice = 20.00;
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $admin = $admins->random();
            
            $isTrialPackage = rand(1, 100) <= 30;
            
            if ($isTrialPackage) {
                $amount = $trialPrice;
                $description = "Δοκιμαστικό πακέτο - {$member->name}";
                $category = 'Trial Package';
            } else {
                $amount = $dayPassPrice;
                $description = "Ημερήσιο εισιτήριο - {$member->name}";
                $category = 'Day Pass';
            }
            
            $entries[] = [
                'type' => 'income',
                'amount' => $amount,
                'description' => $description,
                'category' => $category,
                'user_id' => $admin->id,
                'payment_method' => collect(['cash', 'card'])->random(),
                'related_entity_id' => $member->id,
                'related_entity_type' => 'customer',
                'created_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }

    private function generateRefunds($date, $members, $admins)
    {
        $entries = [];
        
        // Refunds happen occasionally (10% chance per day)
        if (rand(1, 100) <= 10) {
            $member = $members->random();
            $admin = $admins->random();
            $amount = rand(25, 200);
            
            $refundReasons = [
                'Ακύρωση πακέτου',
                'Επιστροφή λόγω ιατρικών λόγων',
                'Overpayment correction',
                'Αλλαγή συνδρομής',
                'Παραδοτέο λάθος',
            ];
            
            $reason = $refundReasons[array_rand($refundReasons)];
            
            $entries[] = [
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => "Επιστροφή χρημάτων: {$reason} - {$member->name}",
                'category' => 'Refund',
                'user_id' => $admin->id,
                'payment_method' => 'cash',
                'related_entity_id' => $member->id,
                'related_entity_type' => 'customer',
                'created_at' => $date->copy()->addHours(rand(10, 18))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(10, 18))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }

    private function generateOwnerWithdrawals($date, $admins)
    {
        $entries = [];
        $dayOfWeek = $date->dayOfWeek;
        
        // Owner withdrawals typically happen on specific days
        if (in_array($dayOfWeek, [1, 4])) { // Monday and Thursday
            $admin = $admins->first(); // Usually the main admin
            $amount = rand(200, 1000);
            
            $entries[] = [
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => 'Ανάληψη ιδιοκτήτη',
                'category' => 'Owner Withdrawal',
                'user_id' => $admin->id,
                'payment_method' => 'cash',
                'created_at' => $date->copy()->addHours(rand(18, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(18, 22))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }

    private function generateOperationalExpenses($date, $admins)
    {
        $entries = [];
        
        // Operational expenses paid from cash (15% chance per day)
        if (rand(1, 100) <= 15) {
            $admin = $admins->random();
            
            $expenses = [
                'Καθαριστικά υλικά' => rand(20, 60),
                'Επισκευή εξοπλισμού' => rand(50, 200),
                'Χαρτικά γραφείου' => rand(15, 40),
                'Τρόφιμα/Ποτά για πελάτες' => rand(25, 80),
                'Μικροπαραδοτέα' => rand(10, 50),
                'Μεταφορικά' => rand(15, 45),
                'Πετρέλαιο για θέρμανση' => rand(100, 300),
                'Μικρές επισκευές' => rand(30, 150),
            ];
            
            $expenseName = array_keys($expenses)[array_rand($expenses)];
            $amount = $expenses[$expenseName];
            
            $entries[] = [
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => "Έξοδο: {$expenseName}",
                'category' => 'Operational Expense',
                'user_id' => $admin->id,
                'payment_method' => 'cash',
                'created_at' => $date->copy()->addHours(rand(9, 19))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 19))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }

    private function generatePettyCashAdjustments($date, $admins)
    {
        $entries = [];
        
        // Petty cash adjustments (5% chance per day)
        if (rand(1, 100) <= 5) {
            $admin = $admins->random();
            
            $adjustments = [
                'Διόρθωση σφάλματος ταμείου' => rand(-50, 50),
                'Αναπλήρωση ταμείου' => rand(50, 200),
                'Επιστροφή εφών' => rand(-20, -5),
                'Bonus σε προσωπικό' => rand(20, 100),
                'Δωρεάν υπηρεσία' => rand(-30, -10),
            ];
            
            $adjustmentName = array_keys($adjustments)[array_rand($adjustments)];
            $amount = abs($adjustments[$adjustmentName]);
            $type = $adjustments[$adjustmentName] > 0 ? 'income' : 'withdrawal';
            
            $entries[] = [
                'type' => $type,
                'amount' => $amount,
                'description' => "Προσαρμογή: {$adjustmentName}",
                'category' => 'Cash Adjustment',
                'user_id' => $admin->id,
                'payment_method' => 'cash',
                'created_at' => $date->copy()->addHours(rand(9, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 22))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $entries;
    }
}