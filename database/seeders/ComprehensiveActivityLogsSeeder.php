<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserPackage;
use App\Models\Booking;
use Carbon\Carbon;

class ComprehensiveActivityLogsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $members = User::where('role', 'member')->get();
        $trainers = User::where('role', 'trainer')->get();
        $admins = User::where('role', 'admin')->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run ComprehensiveUsersSeeder first.');
            return;
        }

        $activities = [];
        
        // Create activity logs for the last 30 days
        for ($day = 29; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day);
            
            // Generate daily activities
            $dailyActivities = $this->generateDailyActivities($date, $users, $members, $trainers, $admins);
            $activities = array_merge($activities, $dailyActivities);
        }

        // Insert activities in batches
        $chunks = array_chunk($activities, 100);
        foreach ($chunks as $chunk) {
            ActivityLog::insert($chunk);
        }

        $this->command->info('Comprehensive activity logs seeded successfully!');
        $this->command->info('- Total activities created: ' . count($activities));
        $this->command->info('- Activity coverage: Last 30 days');
        $this->command->info('- Activity types: user_registration, package_purchase, booking_created, class_attended, etc.');
        $this->command->info('- Realistic daily patterns and user behaviors');
    }

    private function generateDailyActivities($date, $users, $members, $trainers, $admins)
    {
        $activities = [];
        $dayOfWeek = $date->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]); // Sunday = 0, Saturday = 6
        
        // Activity types and their probabilities
        $activityTypes = [
            'user_registration' => $this->getUserRegistrationActivities($date, $members),
            'package_purchase' => $this->getPackagePurchaseActivities($date, $members),
            'booking_created' => $this->getBookingCreatedActivities($date, $members, $isWeekend),
            'class_attended' => $this->getClassAttendedActivities($date, $members, $isWeekend),
            'class_cancelled' => $this->getClassCancelledActivities($date, $members),
            'payment_received' => $this->getPaymentReceivedActivities($date, $members),
            'package_expired' => $this->getPackageExpiredActivities($date, $members),
            'trainer_checkin' => $this->getTrainerCheckinActivities($date, $trainers, $isWeekend),
            'admin_action' => $this->getAdminActionActivities($date, $admins),
            'system_maintenance' => $this->getSystemMaintenanceActivities($date, $admins),
            'user_profile_updated' => $this->getUserProfileUpdatedActivities($date, $members),
            'class_evaluation' => $this->getClassEvaluationActivities($date, $members),
            'package_extended' => $this->getPackageExtendedActivities($date, $members),
            'refund_processed' => $this->getRefundProcessedActivities($date, $members),
            'membership_upgraded' => $this->getMembershipUpgradedActivities($date, $members),
        ];

        // Combine all activities
        foreach ($activityTypes as $type => $typeActivities) {
            $activities = array_merge($activities, $typeActivities);
        }

        return $activities;
    }

    private function getUserRegistrationActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'user_registration', 1, 3);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'user_registration',
                'description' => "Νέα εγγραφή μέλους: {$member->name}",
                'entity_type' => 'user',
                'entity_id' => $member->id,
                'metadata' => json_encode([
                    'membership_type' => $member->membership_type,
                    'registration_source' => collect(['website', 'mobile_app', 'in_person', 'referral'])->random(),
                    'referrer' => rand(1, 100) <= 30 ? $members->random()->name : null,
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getPackagePurchaseActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'package_purchase', 2, 8);
        
        $packageNames = [
            'Basic Membership 1 μήνας',
            'Premium Membership 6 μήνες',
            'Personal Training 8 συνεδρίες',
            'Yoga & Pilates 10 συνεδρίες',
            'EMS Training 6 συνεδρίες'
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $packageName = $packageNames[array_rand($packageNames)];
            $price = rand(50, 400);
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'package_purchase',
                'description' => "Αγορά πακέτου: {$packageName} από {$member->name}",
                'entity_type' => 'package',
                'entity_id' => rand(1, 10),
                'metadata' => json_encode([
                    'package_name' => $packageName,
                    'price' => $price,
                    'payment_method' => collect(['card', 'cash', 'transfer'])->random(),
                    'discount_applied' => rand(1, 100) <= 20 ? rand(5, 25) : null,
                    'duration_days' => rand(30, 180),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getBookingCreatedActivities($date, $members, $isWeekend)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'booking_created', 5, $isWeekend ? 8 : 15);
        
        $classNames = ['HIIT Blast', 'Yoga Flow', 'Personal Training', 'Pilates Core', 'EMS Training', 'Spinning', 'Zumba'];
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $className = $classNames[array_rand($classNames)];
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'booking_created',
                'description' => "Νέα κράτηση: {$className} από {$member->name}",
                'entity_type' => 'booking',
                'entity_id' => rand(1, 100),
                'metadata' => json_encode([
                    'class_name' => $className,
                    'class_date' => $date->copy()->addDays(rand(1, 7))->format('Y-m-d'),
                    'class_time' => collect(['07:00', '09:00', '11:00', '17:00', '19:00'])->random(),
                    'booking_source' => collect(['mobile_app', 'website', 'phone', 'in_person'])->random(),
                    'advance_booking_days' => rand(1, 7),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(6, 23))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(6, 23))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(6, 23))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getClassAttendedActivities($date, $members, $isWeekend)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'class_attended', 8, $isWeekend ? 12 : 20);
        
        $classNames = ['HIIT Blast', 'Yoga Flow', 'Personal Training', 'Pilates Core', 'EMS Training', 'Spinning', 'Zumba'];
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $className = $classNames[array_rand($classNames)];
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'class_attended',
                'description' => "Παρουσία στο μάθημα: {$className} - {$member->name}",
                'entity_type' => 'booking',
                'entity_id' => rand(1, 100),
                'metadata' => json_encode([
                    'class_name' => $className,
                    'class_date' => $date->format('Y-m-d'),
                    'class_time' => collect(['07:00', '09:00', '11:00', 17:00', '19:00'])->random(),
                    'check_in_time' => $date->copy()->addHours(rand(7, 20))->format('H:i'),
                    'duration_minutes' => rand(30, 90),
                    'calories_burned' => rand(200, 600),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(7, 20))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(7, 20))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(7, 20))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getClassCancelledActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'class_cancelled', 1, 4);
        
        $classNames = ['HIIT Blast', 'Yoga Flow', 'Personal Training', 'Pilates Core', 'EMS Training'];
        $cancellationReasons = [
            'Ακύρωση εγκαίρως',
            'Ακύρωση λόγω ασθένειας',
            'Ακύρωση λόγω έκτακτης ανάγκης',
            'Ακύρωση εντός 6 ωρών'
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $className = $classNames[array_rand($classNames)];
            $reason = $cancellationReasons[array_rand($cancellationReasons)];
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'class_cancelled',
                'description' => "Ακύρωση μαθήματος: {$className} από {$member->name}",
                'entity_type' => 'booking',
                'entity_id' => rand(1, 100),
                'metadata' => json_encode([
                    'class_name' => $className,
                    'cancellation_reason' => $reason,
                    'cancelled_hours_before' => rand(1, 48),
                    'charge_applied' => rand(1, 100) <= 30,
                    'charge_percentage' => rand(1, 100) <= 30 ? rand(25, 100) : 0,
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getPaymentReceivedActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'payment_received', 3, 10);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $amount = rand(25, 400);
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'payment_received',
                'description' => "Πληρωμή {$amount}€ από {$member->name}",
                'entity_type' => 'payment',
                'entity_id' => rand(1, 100),
                'metadata' => json_encode([
                    'amount' => $amount,
                    'payment_method' => collect(['card', 'cash', 'transfer'])->random(),
                    'payment_type' => collect(['package', 'installment', 'personal_training', 'late_fee'])->random(),
                    'transaction_id' => 'TXN_' . strtoupper(substr(md5(rand()), 0, 8)),
                    'currency' => 'EUR',
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getPackageExpiredActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'package_expired', 0, 3);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $packageName = collect(['Basic Membership 1 μήνας', 'Personal Training 8 συνεδρίες'])->random();
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'package_expired',
                'description' => "Λήξη πακέτου: {$packageName} - {$member->name}",
                'entity_type' => 'user_package',
                'entity_id' => rand(1, 50),
                'metadata' => json_encode([
                    'package_name' => $packageName,
                    'remaining_sessions' => rand(0, 5),
                    'auto_notification_sent' => true,
                    'extension_offered' => rand(1, 100) <= 50,
                ]),
                'ip_address' => null,
                'user_agent' => 'system',
                'timestamp' => $date->copy()->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getTrainerCheckinActivities($date, $trainers, $isWeekend)
    {
        $activities = [];
        $count = $trainers->count();
        
        // Trainers work more on weekdays
        if ($isWeekend) {
            $count = intval($count * 0.6);
        }
        
        for ($i = 0; $i < $count; $i++) {
            $trainer = $trainers->random();
            
            $activities[] = [
                'user_id' => $trainer->id,
                'activity_type' => 'trainer_checkin',
                'description' => "Check-in προπονητή: {$trainer->name}",
                'entity_type' => 'user',
                'entity_id' => $trainer->id,
                'metadata' => json_encode([
                    'check_in_time' => $date->copy()->addHours(rand(6, 9))->format('H:i'),
                    'scheduled_classes' => rand(3, 8),
                    'location' => collect(['Main Floor', 'Studio A', 'Studio B', 'Personal Training Area'])->random(),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(6, 9))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(6, 9))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(6, 9))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getAdminActionActivities($date, $admins)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'admin_action', 2, 6);
        
        $actions = [
            'Ενημέρωση προφίλ μέλους',
            'Έγκριση επιστροφής χρημάτων',
            'Δημιουργία νέου μαθήματος',
            'Ενημέρωση τιμοκαταλόγου',
            'Αναστολή μελών',
            'Δημιουργία αναφοράς'
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $admin = $admins->random();
            $action = $actions[array_rand($actions)];
            
            $activities[] = [
                'user_id' => $admin->id,
                'activity_type' => 'admin_action',
                'description' => "Ενέργεια διαχειριστή: {$action} από {$admin->name}",
                'entity_type' => 'admin',
                'entity_id' => $admin->id,
                'metadata' => json_encode([
                    'action' => $action,
                    'affected_entity' => collect(['user', 'package', 'class', 'booking'])->random(),
                    'severity' => collect(['low', 'medium', 'high'])->random(),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(9, 18))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(9, 18))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 18))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getSystemMaintenanceActivities($date, $admins)
    {
        $activities = [];
        
        // System maintenance happens rarely
        if (rand(1, 100) <= 5) {
            $admin = $admins->random();
            
            $activities[] = [
                'user_id' => $admin->id,
                'activity_type' => 'system_maintenance',
                'description' => "Συντήρηση συστήματος από {$admin->name}",
                'entity_type' => 'system',
                'entity_id' => 1,
                'metadata' => json_encode([
                    'maintenance_type' => collect(['database_backup', 'system_update', 'security_patch'])->random(),
                    'duration_minutes' => rand(30, 180),
                    'downtime_required' => rand(1, 100) <= 30,
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(2, 6))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(2, 6))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(2, 6))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getUserProfileUpdatedActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'user_profile_updated', 1, 5);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'user_profile_updated',
                'description' => "Ενημέρωση προφίλ: {$member->name}",
                'entity_type' => 'user',
                'entity_id' => $member->id,
                'metadata' => json_encode([
                    'updated_fields' => collect(['phone', 'email', 'medical_history', 'emergency_contact'])->random(rand(1, 3))->toArray(),
                    'update_source' => collect(['self', 'admin', 'trainer'])->random(),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getClassEvaluationActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'class_evaluation', 1, 6);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $rating = rand(3, 5);
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'class_evaluation',
                'description' => "Αξιολόγηση μαθήματος από {$member->name} - {$rating}/5",
                'entity_type' => 'evaluation',
                'entity_id' => rand(1, 100),
                'metadata' => json_encode([
                    'rating' => $rating,
                    'class_name' => collect(['HIIT Blast', 'Yoga Flow', 'Personal Training'])->random(),
                    'has_comment' => rand(1, 100) <= 70,
                    'evaluation_source' => collect(['mobile_app', 'email_link', 'website'])->random(),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(14, 23))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(14, 23))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(14, 23))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getPackageExtendedActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'package_extended', 0, 2);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $extensionDays = rand(7, 30);
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'package_extended',
                'description' => "Επέκταση πακέτου κατά {$extensionDays} ημέρες - {$member->name}",
                'entity_type' => 'user_package',
                'entity_id' => rand(1, 50),
                'metadata' => json_encode([
                    'extension_days' => $extensionDays,
                    'extension_reason' => collect(['goodwill', 'technical_issue', 'medical', 'loyalty'])->random(),
                    'approved_by' => collect(['admin', 'trainer', 'system'])->random(),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(9, 18))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(9, 18))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 18))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getRefundProcessedActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'refund_processed', 0, 1);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            $amount = rand(50, 300);
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'refund_processed',
                'description' => "Επιστροφή χρημάτων {$amount}€ - {$member->name}",
                'entity_type' => 'refund',
                'entity_id' => rand(1, 20),
                'metadata' => json_encode([
                    'amount' => $amount,
                    'refund_reason' => collect(['medical', 'relocation', 'dissatisfaction', 'technical_issue'])->random(),
                    'refund_method' => collect(['original_payment', 'bank_transfer', 'cash'])->random(),
                    'processed_by' => 'admin',
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(10, 17))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(10, 17))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(10, 17))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getMembershipUpgradedActivities($date, $members)
    {
        $activities = [];
        $count = $this->getRandomActivityCount($date, 'membership_upgraded', 0, 2);
        
        for ($i = 0; $i < $count; $i++) {
            $member = $members->random();
            
            $activities[] = [
                'user_id' => $member->id,
                'activity_type' => 'membership_upgraded',
                'description' => "Αναβάθμιση συνδρομής σε Premium - {$member->name}",
                'entity_type' => 'user',
                'entity_id' => $member->id,
                'metadata' => json_encode([
                    'from_type' => 'Basic',
                    'to_type' => 'Premium',
                    'price_difference' => rand(50, 150),
                    'upgrade_reason' => collect(['more_features', 'discount_offer', 'trainer_recommendation'])->random(),
                ]),
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'timestamp' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
                'created_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(9, 21))->addMinutes(rand(0, 59)),
            ];
        }
        
        return $activities;
    }

    private function getRandomActivityCount($date, $type, $min, $max)
    {
        $dayOfWeek = $date->dayOfWeek;
        $isWeekend = in_array($dayOfWeek, [0, 6]);
        
        // Adjust activity levels based on day of week
        if ($isWeekend) {
            $max = intval($max * 0.7); // Less activity on weekends
        }
        
        // Special cases for certain activity types
        switch ($type) {
            case 'class_attended':
                if ($isWeekend) {
                    $max = intval($max * 1.2); // More classes on weekends
                }
                break;
            case 'admin_action':
                if ($isWeekend) {
                    $max = intval($max * 0.3); // Much less admin activity on weekends
                }
                break;
        }
        
        return rand($min, max($min, $max));
    }

    private function generateRandomIP()
    {
        return rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
    }

    private function generateRandomUserAgent()
    {
        $userAgents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Android 11; Mobile; rv:92.0) Gecko/92.0 Firefox/92.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36',
            'SWEAT24-Mobile-App/1.0 (iOS)',
            'SWEAT24-Mobile-App/1.0 (Android)',
        ];
        
        return $userAgents[array_rand($userAgents)];
    }
}