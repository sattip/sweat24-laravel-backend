<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Booking;
use App\Models\UserPackage;
use App\Models\GymClass;
use Carbon\Carbon;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $users = User::all();
        $bookings = Booking::all();
        $userPackages = UserPackage::all();
        $gymClasses = GymClass::all();

        // Create sample activity logs
        $activities = [
            // User registrations
            [
                'user_id' => $users->first()->id ?? null,
                'activity_type' => ActivityLog::TYPE_REGISTRATION,
                'action' => 'New user registered: John Doe',
                'properties' => ['user_name' => 'John Doe', 'user_email' => 'john@example.com'],
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => Carbon::now()->subDays(5),
            ],
            
            // User logins
            [
                'user_id' => $users->skip(1)->first()->id ?? null,
                'activity_type' => ActivityLog::TYPE_LOGIN,
                'action' => 'User logged in: Jane Smith',
                'properties' => ['user_name' => 'Jane Smith', 'user_email' => 'jane@example.com'],
                'ip_address' => '192.168.1.2',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'created_at' => Carbon::now()->subHours(2),
            ],
            
            // Bookings
            [
                'user_id' => $users->first()->id ?? null,
                'activity_type' => ActivityLog::TYPE_BOOKING,
                'model_type' => 'App\\Models\\Booking',
                'model_id' => $bookings->first()->id ?? null,
                'action' => 'Class booked: Morning Yoga',
                'properties' => [
                    'class_title' => 'Morning Yoga',
                    'class_date' => '2025-07-08 09:00',
                    'user_name' => 'John Doe',
                ],
                'ip_address' => '192.168.1.3',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15',
                'created_at' => Carbon::now()->subHours(3),
            ],
            
            // Package purchase
            [
                'user_id' => $users->first()->id ?? null,
                'activity_type' => ActivityLog::TYPE_PACKAGE_PURCHASE,
                'model_type' => 'App\\Models\\UserPackage',
                'model_id' => $userPackages->first()->id ?? null,
                'action' => 'Package purchased: Basic Monthly',
                'properties' => [
                    'package_name' => 'Basic Monthly',
                    'user_name' => 'John Doe',
                    'price' => 50.00,
                    'duration_months' => 1,
                ],
                'ip_address' => '192.168.1.4',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => Carbon::now()->subDays(1),
            ],
            
            // Class creation
            [
                'user_id' => $users->where('membership_type', 'Admin')->first()->id ?? null,
                'activity_type' => ActivityLog::TYPE_CLASS_CREATED,
                'model_type' => 'App\\Models\\GymClass',
                'model_id' => $gymClasses->first()->id ?? null,
                'action' => 'Class created: Evening Pilates',
                'properties' => [
                    'class_title' => 'Evening Pilates',
                    'instructor' => 'Maria Instructor',
                    'start_time' => '2025-07-08 18:00',
                ],
                'ip_address' => '192.168.1.5',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => Carbon::now()->subDays(2),
            ],
            
            // Recent activities for demo
            [
                'user_id' => $users->first()->id ?? null,
                'activity_type' => ActivityLog::TYPE_LOGIN,
                'action' => 'User logged in: Demo User',
                'properties' => ['user_name' => 'Demo User'],
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => Carbon::now()->subMinutes(10),
            ],
            
            [
                'user_id' => $users->first()->id ?? null,
                'activity_type' => ActivityLog::TYPE_BOOKING,
                'action' => 'Class booked: Afternoon Zumba',
                'properties' => [
                    'class_title' => 'Afternoon Zumba',
                    'class_date' => '2025-07-08 15:00',
                    'user_name' => 'Demo User',
                ],
                'ip_address' => '192.168.1.11',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => Carbon::now()->subMinutes(5),
            ],
            
            [
                'user_id' => $users->first()->id ?? null,
                'activity_type' => ActivityLog::TYPE_PAYMENT,
                'action' => 'Payment received: €25.00',
                'properties' => [
                    'amount' => 25.00,
                    'description' => 'Single class fee',
                    'payment_method' => 'Credit Card',
                ],
                'ip_address' => '192.168.1.12',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => Carbon::now()->subMinutes(3),
            ],
        ];

        foreach ($activities as $activity) {
            // Only create if user exists
            if ($activity['user_id']) {
                ActivityLog::create($activity);
            }
        }
    }
}