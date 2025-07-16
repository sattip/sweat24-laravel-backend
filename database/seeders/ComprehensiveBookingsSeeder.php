<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\GymClass;
use Carbon\Carbon;

class ComprehensiveBookingsSeeder extends Seeder
{
    public function run(): void
    {
        // Get users and classes
        $members = User::where('role', 'member')->get();
        $classes = GymClass::where('date', '>=', Carbon::today()->subDays(7))
                          ->where('date', '<=', Carbon::today()->addDays(7))
                          ->get();

        if ($members->isEmpty() || $classes->isEmpty()) {
            $this->command->warn('No members or classes found. Please run ComprehensiveUsersSeeder and EnhancedGymClassesSeeder first.');
            return;
        }

        $bookings = [];
        $bookingStatuses = ['confirmed', 'pending', 'cancelled', 'completed', 'no_show'];
        $cancellationReasons = [
            'Ακύρωση εντός 6 ωρών - χρεώθηκε',
            'Ακύρωση εντός 24 ωρών - χρεώθηκε 50%',
            'Ακύρωση εγκαίρως - χωρίς χρέωση',
            'Ακύρωση λόγω ασθένειας',
            'Ακύρωση λόγω έκτακτης ανάγκης',
            'Ακύρωση από προπονητή',
            'Ακύρωση λόγω καιρού',
            'Αλλαγή σε άλλο μάθημα'
        ];

        foreach ($classes as $class) {
            $classDate = Carbon::parse($class->date);
            $classDateTime = $classDate->copy()->setTimeFromTimeString($class->time);
            
            // Determine how many bookings to create for this class
            $maxBookings = min($class->current_participants, $class->max_participants);
            
            // Add some random bookings beyond current participants for variety
            if ($class->type === 'group' && $maxBookings < $class->max_participants) {
                $additionalBookings = rand(0, min(3, $class->max_participants - $maxBookings));
                $maxBookings += $additionalBookings;
            }
            
            // Create bookings for this class
            $selectedMembers = $members->random(min($maxBookings, $members->count()));
            
            foreach ($selectedMembers as $index => $member) {
                // Determine booking status based on class date and other factors
                $status = 'confirmed';
                $attended = null;
                $cancellationReason = null;
                
                if ($classDateTime->isPast()) {
                    // Past classes
                    $randomOutcome = rand(1, 100);
                    if ($randomOutcome <= 75) {
                        $status = 'completed';
                        $attended = true;
                    } elseif ($randomOutcome <= 85) {
                        $status = 'no_show';
                        $attended = false;
                    } else {
                        $status = 'cancelled';
                        $attended = false;
                        $cancellationReason = $cancellationReasons[array_rand($cancellationReasons)];
                    }
                } else {
                    // Future classes
                    $randomOutcome = rand(1, 100);
                    if ($randomOutcome <= 80) {
                        $status = 'confirmed';
                    } elseif ($randomOutcome <= 90) {
                        $status = 'pending';
                    } else {
                        $status = 'cancelled';
                        $cancellationReason = $cancellationReasons[array_rand($cancellationReasons)];
                    }
                }
                
                // Determine booking time (when the booking was made)
                $bookingTime = $classDateTime->copy()->subDays(rand(1, 7))->subHours(rand(1, 24));
                
                // If cancelled, booking time should be more recent
                if ($status === 'cancelled' && $classDateTime->isFuture()) {
                    $bookingTime = $classDateTime->copy()->subHours(rand(1, 48));
                }
                
                $bookings[] = [
                    'user_id' => $member->id,
                    'class_id' => $class->id,
                    'customer_name' => $member->name,
                    'customer_email' => $member->email,
                    'class_name' => $class->name,
                    'instructor' => $class->instructor,
                    'date' => $class->date,
                    'time' => $class->time,
                    'status' => $status,
                    'type' => $class->type,
                    'attended' => $attended,
                    'booking_time' => $bookingTime,
                    'location' => $class->location,
                    'cancellation_reason' => $cancellationReason,
                    'created_at' => $bookingTime,
                    'updated_at' => $status === 'cancelled' ? $bookingTime->copy()->addMinutes(rand(1, 60)) : $bookingTime,
                ];
            }
        }

        // Add some additional random bookings for variety
        $additionalBookings = 15;
        for ($i = 0; $i < $additionalBookings; $i++) {
            $randomClass = $classes->random();
            $randomMember = $members->random();
            $classDate = Carbon::parse($randomClass->date);
            $classDateTime = $classDate->copy()->setTimeFromTimeString($randomClass->time);
            
            // Ensure we don't duplicate bookings
            $existingBooking = collect($bookings)->first(function($booking) use ($randomMember, $randomClass) {
                return $booking['user_id'] === $randomMember->id && $booking['class_id'] === $randomClass->id;
            });
            
            if (!$existingBooking) {
                $status = $classDateTime->isPast() ? 
                    (rand(1, 100) <= 80 ? 'completed' : 'no_show') : 
                    (rand(1, 100) <= 85 ? 'confirmed' : 'pending');
                
                $attended = null;
                if ($status === 'completed') {
                    $attended = true;
                } elseif ($status === 'no_show') {
                    $attended = false;
                }
                
                $bookingTime = $classDateTime->copy()->subDays(rand(1, 5))->subHours(rand(1, 12));
                
                $bookings[] = [
                    'user_id' => $randomMember->id,
                    'class_id' => $randomClass->id,
                    'customer_name' => $randomMember->name,
                    'customer_email' => $randomMember->email,
                    'class_name' => $randomClass->name,
                    'instructor' => $randomClass->instructor,
                    'date' => $randomClass->date,
                    'time' => $randomClass->time,
                    'status' => $status,
                    'type' => $randomClass->type,
                    'attended' => $attended,
                    'booking_time' => $bookingTime,
                    'location' => $randomClass->location,
                    'cancellation_reason' => null,
                    'created_at' => $bookingTime,
                    'updated_at' => $bookingTime,
                ];
            }
        }

        // Insert bookings in batches
        $chunks = array_chunk($bookings, 50);
        foreach ($chunks as $chunk) {
            Booking::insert($chunk);
        }

        // Count bookings by status
        $statusCounts = collect($bookings)->groupBy('status')->map->count();
        
        $this->command->info('Comprehensive bookings seeded successfully!');
        $this->command->info('- Total bookings created: ' . count($bookings));
        $this->command->info('- Booking status breakdown:');
        foreach ($statusCounts as $status => $count) {
            $this->command->info("  - {$status}: {$count}");
        }
        $this->command->info('- Mix of past and future bookings');
        $this->command->info('- Various cancellation scenarios');
        $this->command->info('- Attendance tracking for completed classes');
    }
}