<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ChatConversation;
use App\Models\ChatMessage;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin and some regular users
        $admin = User::where('email', 'admin@sweat24.gr')->first();
        $users = User::where('role', 'member')->take(3)->get();
        
        foreach ($users as $user) {
            // Create conversation
            $conversation = ChatConversation::create([
                'user_id' => $user->id,
                'status' => 'active',
                'last_message_at' => now()->subMinutes(rand(5, 120))
            ]);
            
            // Create some messages
            $messages = [
                [
                    'sender_id' => $user->id,
                    'sender_type' => 'user',
                    'content' => 'Γεια σας, θα ήθελα να ρωτήσω για τις τιμές των πακέτων EMS.',
                    'created_at' => now()->subHours(2)
                ],
                [
                    'sender_id' => $admin->id ?? 1,
                    'sender_type' => 'admin',
                    'content' => 'Καλησπέρα! Το μηνιαίο πακέτο EMS κοστίζει 120€ και περιλαμβάνει 8 συνεδρίες.',
                    'created_at' => now()->subHours(1)->subMinutes(50)
                ],
                [
                    'sender_id' => $user->id,
                    'sender_type' => 'user',
                    'content' => 'Υπάρχει κάποια προσφορά για νέα μέλη;',
                    'created_at' => now()->subHours(1)->subMinutes(30)
                ],
                [
                    'sender_id' => $admin->id ?? 1,
                    'sender_type' => 'admin',
                    'content' => 'Ναι! Αυτή τη στιγμή έχουμε 20% έκπτωση για το πρώτο μήνα σε όλα τα πακέτα.',
                    'created_at' => now()->subHours(1)
                ]
            ];
            
            foreach ($messages as $messageData) {
                $conversation->messages()->create($messageData);
            }
            
            // Mark some as read
            if (rand(0, 1)) {
                $conversation->markAsRead('admin');
            }
        }
    }
}
