<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationFilter;
use App\Models\User;
use Carbon\Carbon;

class ComprehensiveNotificationsSeeder extends Seeder
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

        $notifications = [
            // Sent notifications
            [
                'title' => 'Καλωσόρισμα νέων μελών',
                'message' => 'Καλώς ήρθατε στο SWEAT24! Είμαστε ενθουσιασμένοι που επιλέξατε να ξεκινήσετε το fitness journey σας μαζί μας. Η ομάδα μας είναι εδώ για να σας υποστηρίξει σε κάθε βήμα.',
                'type' => 'welcome',
                'priority' => 'medium',
                'status' => 'sent',
                'scheduled_for' => Carbon::now()->subDays(5),
                'sent_at' => Carbon::now()->subDays(5),
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'new_members',
                'delivery_method' => json_encode(['email', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => false,
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => 'Υπενθύμιση λήξης πακέτου',
                'message' => 'Το πακέτο σας λήγει σε 7 ημέρες. Μην χάσετε τη συνέχεια των προπονήσεων σας! Επικοινωνήστε μαζί μας για ανανέωση με ειδική έκπτωση.',
                'type' => 'package_expiry',
                'priority' => 'high',
                'status' => 'sent',
                'scheduled_for' => Carbon::now()->subDays(3),
                'sent_at' => Carbon::now()->subDays(3),
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'expiring_packages',
                'delivery_method' => json_encode(['email', 'sms', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => true,
                'created_at' => Carbon::now()->subDays(7),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'title' => 'Νέα μαθήματα Yoga & Pilates',
                'message' => 'Ξεκινούν νέα μαθήματα Yoga Flow και Pilates Core! Δείτε το νέο πρόγραμμα και κάντε κράτηση. Περιορισμένες θέσεις διαθέσιμες.',
                'type' => 'new_classes',
                'priority' => 'medium',
                'status' => 'sent',
                'scheduled_for' => Carbon::now()->subDays(2),
                'sent_at' => Carbon::now()->subDays(2),
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'all_members',
                'delivery_method' => json_encode(['email', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => false,
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'title' => 'Ειδική προσφορά Personal Training',
                'message' => 'Μόνο αυτή την εβδομάδα! Έκπτωση 20% σε όλα τα πακέτα Personal Training. Εξατομικευμένες προπονήσεις με πιστοποιημένους trainers.',
                'type' => 'promotion',
                'priority' => 'high',
                'status' => 'sent',
                'scheduled_for' => Carbon::now()->subDays(1),
                'sent_at' => Carbon::now()->subDays(1),
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'active_members',
                'delivery_method' => json_encode(['email', 'sms', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => false,
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(1),
            ],

            // Scheduled notifications
            [
                'title' => 'Εβδομαδιαία υπενθύμιση προπόνησης',
                'message' => 'Η εβδομάδα ξεκινά! Μην ξεχάσετε τις προπονήσεις σας. Δείτε το πρόγραμμά σας και κάντε κράτηση για τα αγαπημένα σας μαθήματα.',
                'type' => 'workout_reminder',
                'priority' => 'medium',
                'status' => 'scheduled',
                'scheduled_for' => Carbon::now()->addDays(1)->setHour(8)->setMinute(0),
                'sent_at' => null,
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'active_members',
                'delivery_method' => json_encode(['email', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => true,
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'title' => 'Μηνιαία αξιολόγηση προόδου',
                'message' => 'Ήρθε η ώρα για την μηνιαία αξιολόγηση! Δείτε την πρόοδό σας και λάβετε εξατομικευμένες συμβουλές από τους trainers μας.',
                'type' => 'assessment_reminder',
                'priority' => 'medium',
                'status' => 'scheduled',
                'scheduled_for' => Carbon::now()->addDays(3)->setHour(10)->setMinute(0),
                'sent_at' => null,
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'long_term_members',
                'delivery_method' => json_encode(['email', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => true,
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'title' => 'Νέα EMS Training πακέτα',
                'message' => 'Ανακαλύψτε την τεχνολογία EMS! Πιο αποτελεσματικές προπονήσεις σε λιγότερο χρόνο. Δοκιμάστε το νέο μας EMS πακέτο.',
                'type' => 'new_service',
                'priority' => 'high',
                'status' => 'scheduled',
                'scheduled_for' => Carbon::now()->addDays(2)->setHour(14)->setMinute(0),
                'sent_at' => null,
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'premium_members',
                'delivery_method' => json_encode(['email', 'sms', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => false,
                'created_at' => Carbon::now()->subHours(12),
                'updated_at' => Carbon::now()->subHours(12),
            ],

            // Draft notifications
            [
                'title' => 'Καλοκαιρινή προσφορά 2024',
                'message' => 'Ετοιμαστείτε για το καλοκαίρι! Ειδικές τιμές σε όλα τα πακέτα. Περισσότερες λεπτομέρειες σύντομα...',
                'type' => 'seasonal_promotion',
                'priority' => 'high',
                'status' => 'draft',
                'scheduled_for' => null,
                'sent_at' => null,
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'all_members',
                'delivery_method' => json_encode(['email', 'sms', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => false,
                'created_at' => Carbon::now()->subHours(6),
                'updated_at' => Carbon::now()->subHours(3),
            ],
            [
                'title' => 'Νέες διατροφικές συμβουλές',
                'message' => 'Σύντομα θα σας παρουσιάσουμε νέες υπηρεσίες διατροφικής καθοδήγησης. Μείνετε συντονισμένοι για περισσότερα...',
                'type' => 'nutrition_service',
                'priority' => 'medium',
                'status' => 'draft',
                'scheduled_for' => null,
                'sent_at' => null,
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'interested_in_nutrition',
                'delivery_method' => json_encode(['email', 'in_app']),
                'tracking_enabled' => true,
                'auto_send' => false,
                'created_at' => Carbon::now()->subHours(4),
                'updated_at' => Carbon::now()->subHours(2),
            ],
            [
                'title' => 'Εορταστικό πρόγραμμα',
                'message' => 'Ειδικό πρόγραμμα για τις γιορτές. Κρατήστε τη φόρμα σας και κατά τις γιορτές!',
                'type' => 'holiday_program',
                'priority' => 'medium',
                'status' => 'draft',
                'scheduled_for' => null,
                'sent_at' => null,
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'all_members',
                'delivery_method' => json_encode(['email', 'in_app']),
                'tracking_enabled' => false,
                'auto_send' => false,
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(1),
            ],

            // System notifications
            [
                'title' => 'Ενημέρωση συστήματος',
                'message' => 'Η εφαρμογή θα είναι προσωρινά μη διαθέσιμη το Σάββατο 2:00-4:00 πμ για συντήρηση.',
                'type' => 'system_maintenance',
                'priority' => 'high',
                'status' => 'scheduled',
                'scheduled_for' => Carbon::now()->addDays(1)->setHour(20)->setMinute(0),
                'sent_at' => null,
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'all_users',
                'delivery_method' => json_encode(['email', 'in_app']),
                'tracking_enabled' => false,
                'auto_send' => true,
                'created_at' => Carbon::now()->subHours(8),
                'updated_at' => Carbon::now()->subHours(8),
            ],
            [
                'title' => 'Νέα χαρακτηριστικά εφαρμογής',
                'message' => 'Ανακαλύψτε τα νέα χαρακτηριστικά της εφαρμογής! Πιο εύκολες κρατήσεις, καλύτερη παρακολούθηση προόδου και πολλά άλλα.',
                'type' => 'app_update',
                'priority' => 'medium',
                'status' => 'sent',
                'scheduled_for' => Carbon::now()->subHours(12),
                'sent_at' => Carbon::now()->subHours(12),
                'created_by' => $admins->first()->id ?? 1,
                'target_audience' => 'app_users',
                'delivery_method' => json_encode(['in_app', 'push']),
                'tracking_enabled' => true,
                'auto_send' => false,
                'created_at' => Carbon::now()->subDay(),
                'updated_at' => Carbon::now()->subHours(12),
            ],
        ];

        // Create notifications
        foreach ($notifications as $notificationData) {
            $notification = Notification::create($notificationData);
            
            // Create recipients based on target audience
            $this->createRecipients($notification, $users, $members, $trainers, $admins);
            
            // Create filters for some notifications
            if (in_array($notification->target_audience, ['expiring_packages', 'long_term_members', 'premium_members'])) {
                $this->createFilters($notification);
            }
        }

        $this->command->info('Comprehensive notifications seeded successfully!');
        $this->command->info('- Total notifications created: ' . count($notifications));
        $this->command->info('- Status breakdown:');
        $this->command->info('  - Sent: ' . collect($notifications)->where('status', 'sent')->count());
        $this->command->info('  - Scheduled: ' . collect($notifications)->where('status', 'scheduled')->count());
        $this->command->info('  - Draft: ' . collect($notifications)->where('status', 'draft')->count());
        $this->command->info('- Various types: welcome, package_expiry, promotion, workout_reminder, etc.');
        $this->command->info('- Different target audiences and delivery methods');
    }

    private function createRecipients($notification, $users, $members, $trainers, $admins)
    {
        $recipients = [];
        
        switch ($notification->target_audience) {
            case 'all_users':
                $targetUsers = $users;
                break;
            case 'all_members':
            case 'active_members':
                $targetUsers = $members->where('status', 'active');
                break;
            case 'new_members':
                $targetUsers = $members->where('status', 'active')
                    ->where('created_at', '>=', Carbon::now()->subDays(30));
                break;
            case 'expiring_packages':
                $targetUsers = $members->random(min(5, $members->count()));
                break;
            case 'long_term_members':
                $targetUsers = $members->where('created_at', '<=', Carbon::now()->subMonths(6));
                break;
            case 'premium_members':
                $targetUsers = $members->where('membership_type', 'Premium');
                break;
            case 'trainers':
                $targetUsers = $trainers;
                break;
            case 'app_users':
                $targetUsers = $members->random(min(8, $members->count()));
                break;
            case 'interested_in_nutrition':
                $targetUsers = $members->random(min(6, $members->count()));
                break;
            default:
                $targetUsers = $members->random(min(5, $members->count()));
        }

        foreach ($targetUsers as $user) {
            $status = 'pending';
            $deliveredAt = null;
            $readAt = null;
            
            if ($notification->status === 'sent') {
                $status = rand(1, 100) <= 95 ? 'delivered' : 'failed';
                $deliveredAt = $notification->sent_at->copy()->addMinutes(rand(1, 30));
                
                if ($status === 'delivered' && rand(1, 100) <= 70) {
                    $readAt = $deliveredAt->copy()->addMinutes(rand(1, 120));
                }
            }
            
            $recipients[] = [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'status' => $status,
                'delivered_at' => $deliveredAt,
                'read_at' => $readAt,
                'delivery_method' => collect(json_decode($notification->delivery_method))->random(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        
        NotificationRecipient::insert($recipients);
    }

    private function createFilters($notification)
    {
        $filters = [];
        
        switch ($notification->target_audience) {
            case 'expiring_packages':
                $filters[] = [
                    'notification_id' => $notification->id,
                    'filter_type' => 'package_expiry',
                    'filter_value' => json_encode(['days' => 7]),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                break;
                
            case 'long_term_members':
                $filters[] = [
                    'notification_id' => $notification->id,
                    'filter_type' => 'membership_duration',
                    'filter_value' => json_encode(['months' => 6]),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                break;
                
            case 'premium_members':
                $filters[] = [
                    'notification_id' => $notification->id,
                    'filter_type' => 'membership_type',
                    'filter_value' => json_encode(['type' => 'Premium']),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                break;
        }
        
        if (!empty($filters)) {
            NotificationFilter::insert($filters);
        }
    }
}