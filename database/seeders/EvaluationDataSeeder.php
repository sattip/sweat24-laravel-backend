<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassEvaluation;
use App\Models\User;
use App\Models\GymClass;
use App\Models\Booking;
use Carbon\Carbon;

class EvaluationDataSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::where('role', 'member')->get();
        $trainers = User::where('role', 'trainer')->get();
        $completedBookings = Booking::where('status', 'completed')
            ->where('attended', true)
            ->get();

        if ($members->isEmpty() || $trainers->isEmpty() || $completedBookings->isEmpty()) {
            $this->command->warn('Insufficient data found. Please run other seeders first.');
            return;
        }

        $evaluations = [];
        $evaluationQuestions = [
            [
                'question' => 'Πώς βαθμολογείτε τη συνολική εμπειρία του μαθήματος;',
                'type' => 'rating',
                'scale' => 5
            ],
            [
                'question' => 'Ήταν κατάλληλη η ένταση του μαθήματος για εσάς;',
                'type' => 'rating',
                'scale' => 5
            ],
            [
                'question' => 'Πώς βαθμολογείτε τον εκπαιδευτή;',
                'type' => 'rating',
                'scale' => 5
            ],
            [
                'question' => 'Ήταν καθαρός και οργανωμένος ο χώρος;',
                'type' => 'rating',
                'scale' => 5
            ],
            [
                'question' => 'Θα συνιστούσατε αυτό το μάθημα σε φίλους;',
                'type' => 'rating',
                'scale' => 5
            ],
            [
                'question' => 'Τι σας άρεσε περισσότερο στο μάθημα;',
                'type' => 'text'
            ],
            [
                'question' => 'Τι θα μπορούσε να βελτιωθεί;',
                'type' => 'text'
            ]
        ];

        $positiveComments = [
            'Εξαιρετικός εκπαιδευτής, πολύ καθοδηγητικός!',
            'Το μάθημα ήταν πολύ καλά οργανωμένο.',
            'Αισθάνομαι ότι έχω πολύ καλή πρόοδο.',
            'Η ατμόσφαιρα είναι πολύ φιλική και υποστηρικτική.',
            'Τέλεια ένταση, όχι πολύ δύσκολο αλλά αρκετά προκλητικό.',
            'Η μουσική και το περιβάλλον ήταν τέλεια.',
            'Πολύ καλές εξηγήσεις των ασκήσεων.',
            'Αισθάνομαι πιο δυνατός/η μετά από κάθε μάθημα.',
            'Εξαιρετική καθοδήγηση και παρακολούθηση.',
            'Το μάθημα ήταν ακριβώς αυτό που χρειαζόμουν!'
        ];

        $improvementComments = [
            'Θα μπορούσε να είναι λίγο πιο προκλητικό.',
            'Περισσότερες παραλλαγές στις ασκήσεις.',
            'Καλύτερος αερισμός στο χώρο.',
            'Περισσότερο χρόνο για stretching στο τέλος.',
            'Πιο λεπτομερείς οδηγίες για αρχάριους.',
            'Μικρότερες ομάδες για πιο εξατομικευμένη προσοχή.',
            'Περισσότερη ποικιλία στη μουσική.',
            'Καλύτερος φωτισμός στο χώρο.',
            'Πιο σταδιακή αύξηση της έντασης.',
            'Περισσότερα breaks κατά τη διάρκεια του μαθήματος.'
        ];

        // Create evaluations for random completed bookings
        $selectedBookings = $completedBookings->random(min(25, $completedBookings->count()));
        
        foreach ($selectedBookings as $booking) {
            $user = $members->find($booking->user_id);
            if (!$user) continue;

            $responses = [];
            $overallRating = rand(3, 5); // Generally positive ratings
            
            foreach ($evaluationQuestions as $question) {
                if ($question['type'] === 'rating') {
                    // Generate rating with bias towards the overall rating
                    $rating = $overallRating + rand(-1, 1);
                    $rating = max(1, min(5, $rating)); // Ensure 1-5 range
                    
                    $responses[] = [
                        'question' => $question['question'],
                        'type' => $question['type'],
                        'rating' => $rating,
                        'scale' => $question['scale']
                    ];
                } else {
                    // Generate text response
                    $comment = null;
                    if ($question['question'] === 'Τι σας άρεσε περισσότερο στο μάθημα;') {
                        $comment = $positiveComments[array_rand($positiveComments)];
                    } elseif ($question['question'] === 'Τι θα μπορούσε να βελτιωθεί;') {
                        // 60% chance of improvement comment
                        if (rand(1, 100) <= 60) {
                            $comment = $improvementComments[array_rand($improvementComments)];
                        }
                    }
                    
                    $responses[] = [
                        'question' => $question['question'],
                        'type' => $question['type'],
                        'comment' => $comment
                    ];
                }
            }

            $evaluations[] = [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'class_id' => $booking->class_id,
                'class_name' => $booking->class_name,
                'instructor_name' => $booking->instructor,
                'class_date' => $booking->date,
                'overall_rating' => $overallRating,
                'responses' => json_encode($responses),
                'additional_comments' => $this->generateAdditionalComments($overallRating),
                'submitted_at' => Carbon::parse($booking->date)->addHours(rand(1, 48)),
                'created_at' => Carbon::parse($booking->date)->addHours(rand(1, 48)),
                'updated_at' => Carbon::parse($booking->date)->addHours(rand(1, 48)),
            ];
        }

        // Add some additional standalone evaluations
        $additionalEvaluations = 10;
        for ($i = 0; $i < $additionalEvaluations; $i++) {
            $randomMember = $members->random();
            $randomTrainer = $trainers->random();
            $randomDate = Carbon::now()->subDays(rand(1, 30));
            
            $classNames = ['HIIT Blast', 'Yoga Flow', 'Personal Training', 'Pilates Core', 'EMS Training'];
            $className = $classNames[array_rand($classNames)];
            
            $responses = [];
            $overallRating = rand(2, 5);
            
            foreach ($evaluationQuestions as $question) {
                if ($question['type'] === 'rating') {
                    $rating = $overallRating + rand(-1, 1);
                    $rating = max(1, min(5, $rating));
                    
                    $responses[] = [
                        'question' => $question['question'],
                        'type' => $question['type'],
                        'rating' => $rating,
                        'scale' => $question['scale']
                    ];
                } else {
                    $comment = null;
                    if ($question['question'] === 'Τι σας άρεσε περισσότερο στο μάθημα;') {
                        $comment = $positiveComments[array_rand($positiveComments)];
                    } elseif ($question['question'] === 'Τι θα μπορούσε να βελτιωθεί;') {
                        if (rand(1, 100) <= 50) {
                            $comment = $improvementComments[array_rand($improvementComments)];
                        }
                    }
                    
                    $responses[] = [
                        'question' => $question['question'],
                        'type' => $question['type'],
                        'comment' => $comment
                    ];
                }
            }

            $evaluations[] = [
                'booking_id' => null,
                'user_id' => $randomMember->id,
                'class_id' => null,
                'class_name' => $className,
                'instructor_name' => $randomTrainer->name,
                'class_date' => $randomDate->format('Y-m-d'),
                'overall_rating' => $overallRating,
                'responses' => json_encode($responses),
                'additional_comments' => $this->generateAdditionalComments($overallRating),
                'submitted_at' => $randomDate->addHours(rand(1, 24)),
                'created_at' => $randomDate->addHours(rand(1, 24)),
                'updated_at' => $randomDate->addHours(rand(1, 24)),
            ];
        }

        // Insert evaluations
        foreach ($evaluations as $evaluation) {
            ClassEvaluation::create($evaluation);
        }

        // Calculate statistics
        $ratingStats = $this->calculateRatingStats($evaluations);

        $this->command->info('Evaluation data seeded successfully!');
        $this->command->info('- Total evaluations created: ' . count($evaluations));
        $this->command->info('- Rating distribution:');
        foreach ($ratingStats as $rating => $count) {
            $this->command->info("  - {$rating} stars: {$count} evaluations");
        }
        $this->command->info('- Includes both rating and text responses');
        $this->command->info('- Realistic feedback patterns');
    }

    private function generateAdditionalComments($overallRating)
    {
        $excellentComments = [
            'Το καλύτερο γυμναστήριο στην περιοχή! Συνεχίστε έτσι!',
            'Η ομάδα είναι εξαιρετική, πολύ επαγγελματική.',
            'Θα συνιστούσα ανεπιφύλακτα σε όλους!',
            'Πολύ ευχαριστημένος/η με την εμπειρία μου.'
        ];

        $goodComments = [
            'Γενικά πολύ καλή εμπειρία, θα επιστρέψω.',
            'Καλή οργάνωση και φιλικό περιβάλλον.',
            'Ικανοποιητικό μάθημα, χωρίς ιδιαίτερα παράπονα.',
            'Καλή αναλογία ποιότητας-τιμής.'
        ];

        $averageComments = [
            'Μέτριο μάθημα, έχει περιθώρια βελτίωσης.',
            'Όχι κακό, αλλά όχι και εξαιρετικό.',
            'Αναμενόμενη ποιότητα, τίποτα ιδιαίτερο.',
            'Θα δοκιμάσω ξανά σε διαφορετικό μάθημα.'
        ];

        if ($overallRating >= 5) {
            return $excellentComments[array_rand($excellentComments)];
        } elseif ($overallRating >= 4) {
            return $goodComments[array_rand($goodComments)];
        } elseif ($overallRating >= 3) {
            return $averageComments[array_rand($averageComments)];
        } else {
            return 'Χρειάζεται βελτίωση σε πολλά σημεία.';
        }
    }

    private function calculateRatingStats($evaluations)
    {
        $stats = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        foreach ($evaluations as $evaluation) {
            $rating = $evaluation['overall_rating'];
            $stats[$rating]++;
        }
        
        return $stats;
    }
}