<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashRegisterEntry;
use App\Models\User;
use App\Models\Store;
use Carbon\Carbon;

class MultiStoreCashRegisterSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::where('role', 'member')->get();
        $admins = User::where('role', 'admin')->get();
        $stores = Store::all();

        if ($members->isEmpty() || $admins->isEmpty() || $stores->isEmpty()) {
            $this->command->warn('Missing required data. Please ensure users and stores exist.');
            return;
        }

        $entries = [];

        // Generate entries for the last 30 days for both stores
        for ($day = 29; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day);

            foreach ($stores as $store) {
                $storeEntries = $this->generateDailyEntriesForStore($date, $members, $admins, $store);
                $entries = array_merge($entries, $storeEntries);
            }
        }

        // Insert entries in batches
        $chunks = array_chunk($entries, 50);
        foreach ($chunks as $chunk) {
            CashRegisterEntry::insert($chunk);
        }

        // Calculate totals per store
        foreach ($stores as $store) {
            $storeEntries = collect($entries)->where('store_id', $store->id);
            $totalIncome = $storeEntries->where('type', 'income')->sum('amount');
            $totalWithdrawals = $storeEntries->where('type', 'withdrawal')->sum('amount');
            $netAmount = $totalIncome - $totalWithdrawals;

            $this->command->info("Store: {$store->name}");
            $this->command->info("- Entries: {$storeEntries->count()}");
            $this->command->info("- Income: €" . number_format($totalIncome, 2));
            $this->command->info("- Withdrawals: €" . number_format($totalWithdrawals, 2));
            $this->command->info("- Net: €" . number_format($netAmount, 2));
            $this->command->info('');
        }

        $this->command->info('Multi-store cash register entries seeded successfully!');
        $this->command->info('- Total entries: ' . count($entries));
        $this->command->info('- Period: Last 30 days');
        $this->command->info('- Stores covered: ' . $stores->count());
    }

    private function generateDailyEntriesForStore($date, $members, $admins, $store)
    {
        $entries = [];
        $dayOfWeek = $date->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]);

        // Adjust activity based on store - Vary is busier than Lagonesi
        $activityMultiplier = $store->name === 'Βάρη' ? 1.0 : 0.7;

        // Package payments (main income source)
        $packagePayments = $this->generatePackagePayments($date, $members, $admins, $store, $isWeekend, $activityMultiplier);
        $entries = array_merge($entries, $packagePayments);

        // Personal training payments
        $personalTrainingPayments = $this->generatePersonalTrainingPayments($date, $members, $admins, $store, $isWeekend, $activityMultiplier);
        $entries = array_merge($entries, $personalTrainingPayments);

        // Retail sales
        $retailSales = $this->generateRetailSales($date, $members, $admins, $store, $isWeekend, $activityMultiplier);
        $entries = array_merge($entries, $retailSales);

        // Additional services
        $additionalServices = $this->generateAdditionalServices($date, $members, $admins, $store, $isWeekend, $activityMultiplier);
        $entries = array_merge($entries, $additionalServices);

        // Day passes and trial payments
        $dayPasses = $this->generateDayPasses($date, $members, $admins, $store, $isWeekend, $activityMultiplier);
        $entries = array_merge($entries, $dayPasses);

        // Refunds and adjustments (less frequent)
        $refunds = $this->generateRefunds($date, $members, $admins, $store);
        $entries = array_merge($entries, $refunds);

        // Owner withdrawals (weekly)
        $ownerWithdrawals = $this->generateOwnerWithdrawals($date, $admins, $store);
        $entries = array_merge($entries, $ownerWithdrawals);

        // Operational expenses
        $operationalExpenses = $this->generateOperationalExpenses($date, $admins, $store);
        $entries = array_merge($entries, $operationalExpenses);

        return $entries;
    }

    private function generatePackagePayments($date, $members, $admins, $store, $isWeekend, $activityMultiplier)
    {
        $entries = [];
        $baseCount = $isWeekend ? rand(2, 4) : rand(3, 7);
        $count = round($baseCount * $activityMultiplier);

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
            $packageKeys = array_keys($packages);
            $packageName = $packageKeys[array_rand($packageKeys)];
            $packageInfo = $packages[$packageName];

            // Determine if this is a full payment or installment
            $isInstallment = $packageInfo['installments'] && rand(1, 100) <= 40;

            if ($isInstallment) {
                $amount = $packageInfo['price'] / rand(2, 4);
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
                'related_entity_id' => (string)$member->id,
                'related_entity_type' => 'customer',
                'store_id' => $store->id,
                'created_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
            ];
        }

        return $entries;
    }

    private function generatePersonalTrainingPayments($date, $members, $admins, $store, $isWeekend, $activityMultiplier)
    {
        $entries = [];
        $baseCount = $isWeekend ? rand(1, 3) : rand(2, 5);
        $count = round($baseCount * $activityMultiplier);

        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $admin = $admins->random();
            $amount = rand(30, 50);

            $trainers = ['Άλεξ Ροδρίγκεζ', 'Εμιλι Τσεν', 'Τζέιμς Τέιλορ', 'Σάρα Τζόνσον'];
            $trainer = $trainers[array_rand($trainers)];

            $entries[] = [
                'type' => 'income',
                'amount' => $amount,
                'description' => "Personal Training με {$trainer} - {$member->name}",
                'category' => 'Personal Training',
                'user_id' => $admin->id,
                'payment_method' => collect(['cash', 'card'])->random(),
                'related_entity_id' => (string)$member->id,
                'related_entity_type' => 'customer',
                'store_id' => $store->id,
                'created_at' => $date->copy()->addHours(rand(7, 21))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(7, 21))->addMinutes(rand(0, 59)),
            ];
        }

        return $entries;
    }

    private function generateRetailSales($date, $members, $admins, $store, $isWeekend, $activityMultiplier)
    {
        $entries = [];
        $baseCount = $isWeekend ? rand(1, 3) : rand(2, 6);
        $count = round($baseCount * $activityMultiplier);

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
            $productKeys = array_keys($products);
            $productName = $productKeys[array_rand($productKeys)];
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
                'related_entity_id' => (string)$member->id,
                'related_entity_type' => 'customer',
                'store_id' => $store->id,
                'created_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
            ];
        }

        return $entries;
    }

    private function generateAdditionalServices($date, $members, $admins, $store, $isWeekend, $activityMultiplier)
    {
        $entries = [];
        $baseCount = $isWeekend ? rand(0, 2) : rand(1, 3);
        $count = round($baseCount * $activityMultiplier);

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
            $serviceKeys = array_keys($services);
            $serviceName = $serviceKeys[array_rand($serviceKeys)];
            $amount = $services[$serviceName];

            $entries[] = [
                'type' => 'income',
                'amount' => $amount,
                'description' => "Υπηρεσία: {$serviceName} - {$member->name}",
                'category' => 'Additional Services',
                'user_id' => $admin->id,
                'payment_method' => collect(['cash', 'card'])->random(),
                'related_entity_id' => (string)$member->id,
                'related_entity_type' => 'customer',
                'store_id' => $store->id,
                'created_at' => $date->copy()->addHours(rand(9, 20))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 20))->addMinutes(rand(0, 59)),
            ];
        }

        return $entries;
    }

    private function generateDayPasses($date, $members, $admins, $store, $isWeekend, $activityMultiplier)
    {
        $entries = [];
        $baseCount = $isWeekend ? rand(1, 4) : rand(2, 6);
        $count = round($baseCount * $activityMultiplier);

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
                'related_entity_id' => (string)$member->id,
                'related_entity_type' => 'customer',
                'store_id' => $store->id,
                'created_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
            ];
        }

        return $entries;
    }

    private function generateRefunds($date, $members, $admins, $store)
    {
        $entries = [];

        // Refunds happen occasionally (8% chance per store per day)
        if (rand(1, 100) <= 8) {
            $member = $members->random();
            $admin = $admins->random();
            $amount = rand(25, 150);

            $refundReasons = [
                'Ακύρωση πακέτου',
                'Επιστροφή λόγω ιατρικών λόγων',
                'Overpayment correction',
                'Αλλαγή συνδρομής',
            ];

            $reason = $refundReasons[array_rand($refundReasons)];

            $entries[] = [
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => "Επιστροφή χρημάτων: {$reason} - {$member->name}",
                'category' => 'Refund',
                'user_id' => $admin->id,
                'payment_method' => 'cash',
                'related_entity_id' => (string)$member->id,
                'related_entity_type' => 'customer',
                'store_id' => $store->id,
                'created_at' => $date->copy()->addHours(rand(10, 18))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(10, 18))->addMinutes(rand(0, 59)),
            ];
        }

        return $entries;
    }

    private function generateOwnerWithdrawals($date, $admins, $store)
    {
        $entries = [];
        $dayOfWeek = $date->dayOfWeek;

        // Owner withdrawals happen on specific days (Monday and Thursday)
        if (in_array($dayOfWeek, [1, 4])) {
            $admin = $admins->first();
            $amount = rand(300, 800); // Higher amounts for store withdrawals

            $entries[] = [
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => "Ανάληψη ιδιοκτήτη - {$store->name}",
                'category' => 'Owner Withdrawal',
                'user_id' => $admin->id,
                'payment_method' => 'cash',
                'related_entity_id' => null,
                'related_entity_type' => null,
                'store_id' => $store->id,
                'created_at' => $date->copy()->addHours(rand(18, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(18, 22))->addMinutes(rand(0, 59)),
            ];
        }

        return $entries;
    }

    private function generateOperationalExpenses($date, $admins, $store)
    {
        $entries = [];

        // Operational expenses (12% chance per store per day)
        if (rand(1, 100) <= 12) {
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

            $expenseKeys = array_keys($expenses);
            $expenseName = $expenseKeys[array_rand($expenseKeys)];
            $amount = $expenses[$expenseName];

            $entries[] = [
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => "Έξοδο: {$expenseName} - {$store->name}",
                'category' => 'Operational Expense',
                'user_id' => $admin->id,
                'payment_method' => 'cash',
                'related_entity_id' => null,
                'related_entity_type' => null,
                'store_id' => $store->id,
                'created_at' => $date->copy()->addHours(rand(9, 19))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 19))->addMinutes(rand(0, 59)),
            ];
        }

        return $entries;
    }
}
