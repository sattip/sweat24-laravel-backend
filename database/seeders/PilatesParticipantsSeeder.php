<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\GymClass;
use Carbon\Carbon;

class PilatesParticipantsSeeder extends Seeder
{
    /**
     * Seed participants for the Pilates class on 2026-01-23 at 13:00
     */
    public function run(): void
    {
        // Find or create the Pilates class for 2026-01-23 at 13:00
        $class = GymClass::where('date', '2026-01-23')
            ->where('time', '13:00:00')
            ->whereIn('name', ['Pilates Core', 'πιλάτες', 'Pilates'])
            ->first();

        if (!$class) {
            // Create the class if it doesn't exist
            $class = GymClass::create([
                'name' => 'πιλάτες',
                'type' => 'group',
                'instructor' => 'Εμιλι Τσεν',
                'date' => '2026-01-23',
                'time' => '13:00:00',
                'duration' => 60,
                'max_participants' => 12,
                'current_participants' => 0,
                'location' => 'Studio B',
                'description' => 'Ενδυνάμωση κορμού με pilates τεχνικές',
                'status' => 'active',
            ]);

            $this->command->info('Created new Pilates class for 2026-01-23 at 13:00');
        }

        // Get all member users
        $members = User::where('role', 'member')->get();

        if ($members->isEmpty()) {
            $this->command->warn('No members found. Please run ComprehensiveUsersSeeder first.');
            return;
        }

        // Check existing bookings for this class
        $existingBookings = Booking::where('class_id', $class->id)->pluck('user_id')->toArray();
        $availableMembers = $members->whereNotIn('id', $existingBookings);

        if ($availableMembers->count() < 4) {
            $this->command->warn('Not enough available members to add participants.');
            return;
        }

        // Select 5 random members
        $selectedMembers = $availableMembers->random(5);

        $bookings = [];
        $bookingBaseTime = Carbon::parse('2026-01-20 10:00:00'); // Booking made 3 days before

        foreach ($selectedMembers as $index => $member) {
            $bookings[] = [
                'user_id' => $member->id,
                'class_id' => $class->id,
                'customer_name' => $member->name,
                'customer_email' => $member->email,
                'class_name' => $class->name,
                'instructor' => $class->instructor,
                'date' => $class->date,
                'time' => $class->time,
                'status' => 'confirmed',
                'type' => $class->type,
                'attended' => null, // Future class
                'booking_time' => $bookingBaseTime->copy()->addMinutes($index * 30),
                'location' => $class->location,
                'cancellation_reason' => null,
                'created_at' => $bookingBaseTime->copy()->addMinutes($index * 30),
                'updated_at' => $bookingBaseTime->copy()->addMinutes($index * 30),
            ];
        }

        // Insert bookings
        Booking::insert($bookings);

        // Update class current_participants
        $class->current_participants = $class->current_participants + count($bookings);
        $class->save();

        $this->command->info('✓ Successfully added ' . count($bookings) . ' participants to Pilates class');
        $this->command->info('✓ Class: ' . $class->name . ' on ' . $class->date . ' at ' . $class->time);
        $this->command->info('✓ Current participants: ' . $class->current_participants . '/' . $class->max_participants);
        $this->command->info('');
        $this->command->info('Participants added:');
        foreach ($selectedMembers as $member) {
            $this->command->info('  - ' . $member->name . ' (' . $member->email . ')');
        }
    }
}
