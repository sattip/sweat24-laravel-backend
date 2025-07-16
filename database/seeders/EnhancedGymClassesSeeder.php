<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GymClass;
use Carbon\Carbon;

class EnhancedGymClassesSeeder extends Seeder
{
    public function run(): void
    {
        // Define class templates with various types
        $classTemplates = [
            // Group Fitness Classes
            [
                'name' => 'HIIT Blast',
                'type' => 'group',
                'instructor' => 'Άλεξ Ροδρίγκεζ',
                'duration' => 45,
                'max_participants' => 12,
                'location' => 'Main Floor',
                'description' => 'Υψηλής έντασης καρδιοαγγειακή προπόνηση με διαστήματα',
                'times' => ['07:00:00', '09:00:00', '18:00:00', '19:30:00'],
                'days' => [1, 3, 5] // Monday, Wednesday, Friday
            ],
            [
                'name' => 'Yoga Flow',
                'type' => 'group',
                'instructor' => 'Εμιλι Τσεν',
                'duration' => 60,
                'max_participants' => 15,
                'location' => 'Studio A',
                'description' => 'Ήρεμη ροή yoga για χαλάρωση και ευελιξία',
                'times' => ['07:30:00', '10:00:00', '17:00:00'],
                'days' => [2, 4, 6] // Tuesday, Thursday, Saturday
            ],
            [
                'name' => 'Pilates Core',
                'type' => 'group',
                'instructor' => 'Εμιλι Τσεν',
                'duration' => 50,
                'max_participants' => 10,
                'location' => 'Studio B',
                'description' => 'Ενδυνάμωση κορμού με pilates τεχνικές',
                'times' => ['08:00:00', '11:00:00', '16:00:00'],
                'days' => [1, 3, 5]
            ],
            [
                'name' => 'Strength Circuit',
                'type' => 'group',
                'instructor' => 'Άλεξ Ροδρίγκεζ',
                'duration' => 50,
                'max_participants' => 8,
                'location' => 'Weight Room',
                'description' => 'Κυκλική προπόνηση με βάρη και αντίσταση',
                'times' => ['08:30:00', '17:30:00'],
                'days' => [2, 4]
            ],
            [
                'name' => 'Aqua Fitness',
                'type' => 'group',
                'instructor' => 'Σάρα Τζόνσον',
                'duration' => 45,
                'max_participants' => 20,
                'location' => 'Pool Area',
                'description' => 'Προπόνηση στο νερό για όλες τις ηλικίες',
                'times' => ['10:00:00', '18:00:00'],
                'days' => [1, 3, 5]
            ],
            [
                'name' => 'Spinning',
                'type' => 'group',
                'instructor' => 'Σάρα Τζόνσον',
                'duration' => 45,
                'max_participants' => 16,
                'location' => 'Cycling Room',
                'description' => 'Έντονη καρδιοαγγειακή προπόνηση με στατικό ποδήλατο',
                'times' => ['07:00:00', '18:30:00'],
                'days' => [2, 4, 6]
            ],
            [
                'name' => 'Zumba',
                'type' => 'group',
                'instructor' => 'Σάρα Τζόνσον',
                'duration' => 60,
                'max_participants' => 25,
                'location' => 'Main Floor',
                'description' => 'Χορευτική προπόνηση με λατίνικους ρυθμούς',
                'times' => ['19:00:00'],
                'days' => [1, 3, 5]
            ],
            [
                'name' => 'EMS Training',
                'type' => 'group',
                'instructor' => 'Τζέιμς Τέιλορ',
                'duration' => 25,
                'max_participants' => 6,
                'location' => 'EMS Room',
                'description' => 'Ηλεκτρομυοδιέγερση για αποτελεσματική ενδυνάμωση',
                'times' => ['08:00:00', '12:00:00', '16:00:00', '20:00:00'],
                'days' => [1, 2, 3, 4, 5]
            ],
            [
                'name' => 'Functional Training',
                'type' => 'group',
                'instructor' => 'Τζέιμς Τέιλορ',
                'duration' => 50,
                'max_participants' => 10,
                'location' => 'Functional Area',
                'description' => 'Λειτουργική προπόνηση για καθημερινές κινήσεις',
                'times' => ['09:00:00', '17:00:00'],
                'days' => [1, 3, 5]
            ],
            [
                'name' => 'CrossFit',
                'type' => 'group',
                'instructor' => 'Τζέιμς Τέιλορ',
                'duration' => 60,
                'max_participants' => 12,
                'location' => 'CrossFit Box',
                'description' => 'Υψηλής έντασης λειτουργική προπόνηση',
                'times' => ['06:00:00', '18:00:00'],
                'days' => [2, 4, 6]
            ],
        ];

        // Personal Training slots
        $personalTrainingSlots = [
            [
                'name' => 'Personal Training',
                'type' => 'personal',
                'instructor' => 'Άλεξ Ροδρίγκεζ',
                'duration' => 60,
                'max_participants' => 1,
                'location' => 'Personal Training Area',
                'description' => 'Εξατομικευμένη προπόνηση με έμφαση στη δύναμη',
                'times' => ['10:00:00', '11:00:00', '14:00:00', '15:00:00'],
                'days' => [1, 2, 3, 4, 5]
            ],
            [
                'name' => 'Personal Yoga',
                'type' => 'personal',
                'instructor' => 'Εμιλι Τσεν',
                'duration' => 60,
                'max_participants' => 1,
                'location' => 'Studio A',
                'description' => 'Προσωπική καθοδήγηση σε yoga και meditation',
                'times' => ['09:00:00', '13:00:00', '15:00:00'],
                'days' => [1, 3, 5]
            ],
            [
                'name' => 'Personal Pilates',
                'type' => 'personal',
                'instructor' => 'Εμιλι Τσεν',
                'duration' => 60,
                'max_participants' => 1,
                'location' => 'Studio B',
                'description' => 'Εξατομικευμένη προπόνηση pilates με εξοπλισμό',
                'times' => ['12:00:00', '16:00:00'],
                'days' => [2, 4, 6]
            ],
            [
                'name' => 'Personal Strength',
                'type' => 'personal',
                'instructor' => 'Τζέιμς Τέιλορ',
                'duration' => 60,
                'max_participants' => 1,
                'location' => 'Weight Room',
                'description' => 'Εξατομικευμένη προπόνηση δύναμης και μυικής μάζας',
                'times' => ['07:00:00', '11:00:00', '13:00:00', '19:00:00'],
                'days' => [1, 2, 3, 4, 5]
            ],
            [
                'name' => 'Personal Cardio',
                'type' => 'personal',
                'instructor' => 'Σάρα Τζόνσον',
                'duration' => 45,
                'max_participants' => 1,
                'location' => 'Cardio Area',
                'description' => 'Εξατομικευμένη καρδιοαγγειακή προπόνηση',
                'times' => ['08:00:00', '14:00:00', '17:00:00'],
                'days' => [1, 3, 5]
            ],
        ];

        // Combine all templates
        $allTemplates = array_merge($classTemplates, $personalTrainingSlots);

        // Generate classes for the next 14 days
        $classes = [];
        $today = Carbon::today();
        
        for ($day = 0; $day < 14; $day++) {
            $currentDate = $today->copy()->addDays($day);
            $dayOfWeek = $currentDate->dayOfWeek; // 0=Sunday, 1=Monday, etc.
            
            // Convert to our format (1=Monday, 2=Tuesday, etc.)
            $dayIndex = $dayOfWeek == 0 ? 7 : $dayOfWeek;
            
            foreach ($allTemplates as $template) {
                if (in_array($dayIndex, $template['days'])) {
                    foreach ($template['times'] as $time) {
                        // Determine participants count
                        $participants = 0;
                        if ($template['type'] === 'personal') {
                            // Personal training - 80% chance to be booked
                            $participants = rand(1, 100) <= 80 ? 1 : 0;
                        } else {
                            // Group classes - random participation
                            $maxParticipants = $template['max_participants'];
                            
                            // Peak hours (7-9 AM, 5-8 PM) have higher attendance
                            $hour = intval(substr($time, 0, 2));
                            if (($hour >= 7 && $hour <= 9) || ($hour >= 17 && $hour <= 20)) {
                                $participants = rand(intval($maxParticipants * 0.6), $maxParticipants);
                            } else {
                                $participants = rand(0, intval($maxParticipants * 0.7));
                            }
                        }
                        
                        // Determine status based on various factors
                        $status = 'active';
                        
                        // Past classes
                        if ($currentDate->isPast()) {
                            $status = 'cancelled';
                        }
                        // Some random cancellations for future classes (5% chance)
                        elseif (rand(1, 100) <= 5) {
                            $status = 'cancelled';
                            $participants = 0;
                        }
                        // Full classes
                        elseif ($participants >= $template['max_participants']) {
                            $status = 'booked';
                        }
                        
                        $classes[] = [
                            'name' => $template['name'],
                            'type' => $template['type'],
                            'instructor' => $template['instructor'],
                            'date' => $currentDate->format('Y-m-d'),
                            'time' => $time,
                            'duration' => $template['duration'],
                            'max_participants' => $template['max_participants'],
                            'current_participants' => $participants,
                            'location' => $template['location'],
                            'description' => $template['description'],
                            'status' => $status,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    }
                }
            }
        }

        // Insert classes in batches for better performance
        $chunks = array_chunk($classes, 50);
        foreach ($chunks as $chunk) {
            GymClass::insert($chunk);
        }

        $this->command->info('Enhanced gym classes seeded successfully!');
        $this->command->info('- ' . count($classes) . ' classes created for the next 14 days');
        $this->command->info('- Group fitness classes: HIIT, Yoga, Pilates, Strength, Aqua, Spinning, Zumba, EMS, Functional, CrossFit');
        $this->command->info('- Personal training slots for all trainers');
        $this->command->info('- Various class statuses and participation levels');
    }
}