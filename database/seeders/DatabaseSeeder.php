<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting comprehensive database seeding...');
        
        // Core seeders (existing)
        $this->call([
            AdminSeeder::class,
            TrainerSeeder::class,
            GymDataSeeder::class,
            ActivityLogSeeder::class,
            CancellationPolicySeeder::class,
        ]);

        $this->command->info('✅ Core seeders completed');

        // New comprehensive seeders
        $this->command->info('🚀 Running comprehensive seeders...');
        
        $this->call([
            // Enhanced core data
            ComprehensiveUsersSeeder::class,
            EnhancedPackagesSeeder::class,
            EnhancedGymClassesSeeder::class,
            ComprehensiveBookingsSeeder::class,
            ComprehensiveUserPackagesSeeder::class,
            
            // Advanced features
            CancellationPoliciesSeeder::class,
            ComprehensiveNotificationsSeeder::class,
            EvaluationDataSeeder::class,
            
            // Activity and logs
            ComprehensiveActivityLogsSeeder::class,
            
            // Financial data
            EnhancedCashRegisterSeeder::class,
            EnhancedBusinessExpensesSeeder::class,
        ]);

        $this->command->info('✅ All comprehensive seeders completed');
        $this->command->info('🎉 Database seeding completed successfully!');
        
        // Display summary
        $this->displaySeedingSummary();
    }

    private function displaySeedingSummary()
    {
        $this->command->info('');
        $this->command->info('📊 SEEDING SUMMARY');
        $this->command->info('==================');
        
        // Count records in each table
        $tables = [
            'users' => \App\Models\User::count(),
            'packages' => \App\Models\Package::count(),
            'gym_classes' => \App\Models\GymClass::count(),
            'bookings' => \App\Models\Booking::count(),
            'user_packages' => \App\Models\UserPackage::count(),
            'cancellation_policies' => \App\Models\CancellationPolicy::count(),
            'notifications' => \App\Models\Notification::count(),
            'class_evaluations' => \App\Models\ClassEvaluation::count(),
            'activity_logs' => \App\Models\ActivityLog::count(),
            'cash_register_entries' => \App\Models\CashRegisterEntry::count(),
            'business_expenses' => \App\Models\BusinessExpense::count(),
        ];

        foreach ($tables as $table => $count) {
            $this->command->info("- {$table}: {$count} records");
        }

        $this->command->info('');
        $this->command->info('🎯 TESTING SCENARIOS INCLUDED:');
        $this->command->info('- Users with various roles (admin, trainer, member)');
        $this->command->info('- Packages with different expiry scenarios');
        $this->command->info('- Bookings with various statuses');
        $this->command->info('- Classes with different types and schedules');
        $this->command->info('- Notifications (sent, scheduled, draft)');
        $this->command->info('- Comprehensive activity logs');
        $this->command->info('- Realistic financial data');
        $this->command->info('- Evaluation and feedback data');
        $this->command->info('- Cancellation policies');
        $this->command->info('');
        $this->command->info('🚀 Ready for comprehensive testing!');
    }
}
