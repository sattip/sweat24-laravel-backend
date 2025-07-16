<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserPackage;
use App\Models\User;
use App\Models\Package;
use Carbon\Carbon;

class ComprehensiveUserPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::where('role', 'member')->get();
        $packages = Package::all();

        if ($members->isEmpty() || $packages->isEmpty()) {
            $this->command->warn('No members or packages found. Please run ComprehensiveUsersSeeder and EnhancedPackagesSeeder first.');
            return;
        }

        $userPackages = [];
        
        foreach ($members as $member) {
            // Each member gets 1-3 packages with different scenarios
            $packageCount = rand(1, 3);
            $memberPackages = $packages->random($packageCount);
            
            foreach ($memberPackages as $index => $package) {
                // Determine package scenario based on index and random factors
                $scenario = $this->determinePackageScenario($index, $packageCount);
                
                $userPackage = $this->createUserPackage($member, $package, $scenario);
                $userPackages[] = $userPackage;
            }
        }

        // Add some additional packages for variety
        $additionalPackages = 20;
        for ($i = 0; $i < $additionalPackages; $i++) {
            $randomMember = $members->random();
            $randomPackage = $packages->random();
            $randomScenario = $this->getRandomScenario();
            
            $userPackage = $this->createUserPackage($randomMember, $randomPackage, $randomScenario);
            $userPackages[] = $userPackage;
        }

        // Insert user packages
        foreach ($userPackages as $userPackage) {
            UserPackage::create($userPackage);
        }

        // Count packages by status
        $statusCounts = collect($userPackages)->groupBy('status')->map->count();
        $expiryCounts = $this->analyzeExpiryScenarios($userPackages);

        $this->command->info('Comprehensive user packages seeded successfully!');
        $this->command->info('- Total user packages created: ' . count($userPackages));
        $this->command->info('- Package status breakdown:');
        foreach ($statusCounts as $status => $count) {
            $this->command->info("  - {$status}: {$count}");
        }
        $this->command->info('- Expiry scenarios:');
        foreach ($expiryCounts as $scenario => $count) {
            $this->command->info("  - {$scenario}: {$count}");
        }
    }

    private function determinePackageScenario($index, $totalPackages)
    {
        $scenarios = [
            'active_long_term',    // Active with plenty of time
            'active_medium_term',  // Active with moderate time
            'expiring_soon',       // Expires within 7 days
            'expiring_very_soon',  // Expires within 3 days
            'expired_recently',    // Expired within last 30 days
            'expired_long_ago',    // Expired more than 30 days ago
            'suspended',           // Suspended/paused
            'fully_used',          // All sessions used but still valid
            'partially_used',      // Some sessions used
            'unused',              // No sessions used yet
        ];

        // First package is usually active
        if ($index === 0) {
            return collect(['active_long_term', 'active_medium_term', 'expiring_soon', 'partially_used'])->random();
        }

        // Additional packages have more variety
        return collect($scenarios)->random();
    }

    private function getRandomScenario()
    {
        $scenarios = [
            'active_long_term' => 25,
            'active_medium_term' => 20,
            'expiring_soon' => 15,
            'expiring_very_soon' => 10,
            'expired_recently' => 10,
            'expired_long_ago' => 5,
            'suspended' => 5,
            'fully_used' => 5,
            'partially_used' => 3,
            'unused' => 2,
        ];

        $random = rand(1, 100);
        $cumulative = 0;
        
        foreach ($scenarios as $scenario => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $scenario;
            }
        }
        
        return 'active_long_term';
    }

    private function createUserPackage($member, $package, $scenario)
    {
        $now = Carbon::now();
        $userPackage = [
            'user_id' => $member->id,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'package_type' => $package->type,
            'total_sessions' => $package->sessions,
            'remaining_sessions' => $package->sessions,
            'price_paid' => $package->price,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        switch ($scenario) {
            case 'active_long_term':
                $userPackage['start_date'] = $now->copy()->subDays(rand(1, 30));
                $userPackage['end_date'] = $now->copy()->addDays(rand(30, 90));
                $userPackage['status'] = 'active';
                $userPackage['remaining_sessions'] = $package->sessions ? 
                    rand(intval($package->sessions * 0.6), $package->sessions) : null;
                break;

            case 'active_medium_term':
                $userPackage['start_date'] = $now->copy()->subDays(rand(15, 60));
                $userPackage['end_date'] = $now->copy()->addDays(rand(7, 30));
                $userPackage['status'] = 'active';
                $userPackage['remaining_sessions'] = $package->sessions ? 
                    rand(intval($package->sessions * 0.3), intval($package->sessions * 0.7)) : null;
                break;

            case 'expiring_soon':
                $userPackage['start_date'] = $now->copy()->subDays(rand(30, 90));
                $userPackage['end_date'] = $now->copy()->addDays(rand(1, 7));
                $userPackage['status'] = 'active';
                $userPackage['remaining_sessions'] = $package->sessions ? 
                    rand(1, intval($package->sessions * 0.4)) : null;
                $userPackage['expiry_warning_sent'] = true;
                break;

            case 'expiring_very_soon':
                $userPackage['start_date'] = $now->copy()->subDays(rand(30, 90));
                $userPackage['end_date'] = $now->copy()->addDays(rand(0, 3));
                $userPackage['status'] = 'active';
                $userPackage['remaining_sessions'] = $package->sessions ? 
                    rand(0, intval($package->sessions * 0.3)) : null;
                $userPackage['expiry_warning_sent'] = true;
                $userPackage['final_warning_sent'] = true;
                break;

            case 'expired_recently':
                $userPackage['start_date'] = $now->copy()->subDays(rand(60, 120));
                $userPackage['end_date'] = $now->copy()->subDays(rand(1, 30));
                $userPackage['status'] = 'expired';
                $userPackage['remaining_sessions'] = $package->sessions ? 
                    rand(0, intval($package->sessions * 0.5)) : null;
                $userPackage['expiry_warning_sent'] = true;
                $userPackage['final_warning_sent'] = true;
                break;

            case 'expired_long_ago':
                $userPackage['start_date'] = $now->copy()->subDays(rand(120, 365));
                $userPackage['end_date'] = $now->copy()->subDays(rand(30, 180));
                $userPackage['status'] = 'expired';
                $userPackage['remaining_sessions'] = $package->sessions ? 
                    rand(0, intval($package->sessions * 0.3)) : null;
                $userPackage['expiry_warning_sent'] = true;
                $userPackage['final_warning_sent'] = true;
                break;

            case 'suspended':
                $userPackage['start_date'] = $now->copy()->subDays(rand(10, 60));
                $userPackage['end_date'] = $now->copy()->addDays(rand(10, 60));
                $userPackage['status'] = 'suspended';
                $userPackage['remaining_sessions'] = $package->sessions ? 
                    rand(intval($package->sessions * 0.4), intval($package->sessions * 0.8)) : null;
                $userPackage['suspension_reason'] = collect([
                    'Αναστολή λόγω ιατρικών λόγων',
                    'Αναστολή λόγω διακοπών',
                    'Αναστολή λόγω οικονομικών λόγων',
                    'Αναστολή κατόπιν αιτήματος πελάτη'
                ])->random();
                break;

            case 'fully_used':
                $userPackage['start_date'] = $now->copy()->subDays(rand(20, 80));
                $userPackage['end_date'] = $now->copy()->addDays(rand(5, 30));
                $userPackage['status'] = 'active';
                $userPackage['remaining_sessions'] = 0;
                break;

            case 'partially_used':
                $userPackage['start_date'] = $now->copy()->subDays(rand(5, 40));
                $userPackage['end_date'] = $now->copy()->addDays(rand(10, 60));
                $userPackage['status'] = 'active';
                $userPackage['remaining_sessions'] = $package->sessions ? 
                    rand(intval($package->sessions * 0.2), intval($package->sessions * 0.6)) : null;
                break;

            case 'unused':
                $userPackage['start_date'] = $now->copy()->subDays(rand(1, 7));
                $userPackage['end_date'] = $now->copy()->addDays(rand(20, 90));
                $userPackage['status'] = 'active';
                $userPackage['remaining_sessions'] = $package->sessions;
                break;
        }

        // Add some random fields
        $userPackage['notes'] = $this->generateRandomNotes($scenario);
        $userPackage['assigned_by'] = rand(1, 3); // Random admin ID
        $userPackage['payment_status'] = collect(['paid', 'partially_paid', 'pending'])->random();
        
        // Add extension history for some packages
        if (rand(1, 100) <= 20) {
            $userPackage['extension_count'] = rand(1, 3);
            $userPackage['total_extensions_days'] = $userPackage['extension_count'] * rand(7, 30);
        }

        return $userPackage;
    }

    private function generateRandomNotes($scenario)
    {
        $notes = [
            'active_long_term' => [
                'Πελάτης ενεργός και τακτικός',
                'Καλή πρόοδος στις προπονήσεις',
                'Ευχαριστημένος με τις υπηρεσίες',
                null
            ],
            'expiring_soon' => [
                'Στάλθηκε υπενθύμιση ανανέωσης',
                'Πελάτης ενδιαφέρεται για ανανέωση',
                'Περιμένει απάντηση για ανανέωση',
                'Προσφορά ανανέωσης με έκπτωση'
            ],
            'expired_recently' => [
                'Δεν ανανέωσε εγκαίρως',
                'Περιμένει οικονομική βελτίωση',
                'Θα ανανεώσει τον επόμενο μήνα',
                'Πιθανή επιστροφή'
            ],
            'suspended' => [
                'Αναστολή κατόπιν αιτήματος',
                'Προσωρινή αναστολή',
                'Αναστολή λόγω ιατρικών λόγων',
                'Αναστολή λόγω διακοπών'
            ],
            'default' => [
                'Τακτικός πελάτης',
                'Καλή συνεργασία',
                'Ευχαριστημένος με τις υπηρεσίες',
                null
            ]
        ];

        $scenarioNotes = $notes[$scenario] ?? $notes['default'];
        return collect($scenarioNotes)->random();
    }

    private function analyzeExpiryScenarios($userPackages)
    {
        $now = Carbon::now();
        $counts = [
            'Active (>30 days)' => 0,
            'Active (7-30 days)' => 0,
            'Expiring soon (1-7 days)' => 0,
            'Expiring very soon (0-1 days)' => 0,
            'Expired recently (0-30 days)' => 0,
            'Expired long ago (>30 days)' => 0,
            'Suspended' => 0,
        ];

        foreach ($userPackages as $package) {
            $endDate = Carbon::parse($package['end_date']);
            $status = $package['status'];

            if ($status === 'suspended') {
                $counts['Suspended']++;
            } elseif ($status === 'expired') {
                $daysSinceExpiry = $now->diffInDays($endDate);
                if ($daysSinceExpiry <= 30) {
                    $counts['Expired recently (0-30 days)']++;
                } else {
                    $counts['Expired long ago (>30 days)']++;
                }
            } elseif ($status === 'active') {
                $daysUntilExpiry = $endDate->diffInDays($now);
                if ($daysUntilExpiry > 30) {
                    $counts['Active (>30 days)']++;
                } elseif ($daysUntilExpiry > 7) {
                    $counts['Active (7-30 days)']++;
                } elseif ($daysUntilExpiry > 1) {
                    $counts['Expiring soon (1-7 days)']++;
                } else {
                    $counts['Expiring very soon (0-1 days)']++;
                }
            }
        }

        return $counts;
    }
}