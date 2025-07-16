<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ClientTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test member user
        $member = User::updateOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password123'),
                'phone' => '+1 (555) 123-4567',
                'address' => '123 Main Street, Apt 4B, New York, NY 10001',
                'date_of_birth' => '1990-05-15',
                'membership_type' => 'Premium',
                'role' => 'member',
                'join_date' => now()->subMonths(6),
                'remaining_sessions' => 12,
                'total_sessions' => 20,
                'status' => 'active',
                'last_visit' => now()->subDays(2),
                'medical_history' => 'Mild knee injury in 2020, fully recovered. No allergies.',
                'emergency_contact' => 'Jane Doe',
                'emergency_phone' => '+1 (555) 987-6543',
                'notes' => 'Prefers morning classes. Working on weight loss goals.',
                'notification_preferences' => [
                    'email_notifications' => true,
                    'sms_notifications' => true,
                    'push_notifications' => true,
                    'booking_reminders' => true,
                    'package_expiry_alerts' => true,
                    'promotional_emails' => false,
                ],
                'privacy_settings' => [
                    'show_profile_to_trainers' => true,
                    'show_attendance_history' => true,
                    'allow_photo_in_gym' => true,
                    'share_progress_reports' => true,
                ],
            ]
        );

        // Create some additional test members
        $members = [
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.j@example.com',
                'phone' => '+1 (555) 234-5678',
                'membership_type' => 'Basic',
                'remaining_sessions' => 5,
                'total_sessions' => 10,
            ],
            [
                'name' => 'Mike Wilson',
                'email' => 'mike.w@example.com',
                'phone' => '+1 (555) 345-6789',
                'membership_type' => 'Premium',
                'remaining_sessions' => 15,
                'total_sessions' => 30,
            ],
            [
                'name' => 'Emily Brown',
                'email' => 'emily.b@example.com',
                'phone' => '+1 (555) 456-7890',
                'membership_type' => 'VIP',
                'remaining_sessions' => 25,
                'total_sessions' => 50,
            ],
        ];

        foreach ($members as $memberData) {
            User::updateOrCreate(
                ['email' => $memberData['email']],
                array_merge($memberData, [
                    'password' => Hash::make('password123'),
                    'role' => 'member',
                    'join_date' => now()->subMonths(rand(1, 12)),
                    'status' => 'active',
                    'last_visit' => now()->subDays(rand(1, 7)),
                ])
            );
        }

        $this->command->info('Test client users created successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('Email: member@example.com');
        $this->command->info('Password: password123');
    }
}