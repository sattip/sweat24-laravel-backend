<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;
use Carbon\Carbon;

class EnhancedPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            // Basic Membership Packages
            [
                'name' => 'Basic Membership 1 μήνας',
                'price' => 50.00,
                'sessions' => null, // Unlimited for membership
                'duration' => 30,
                'type' => 'membership',
                'status' => 'active',
                'description' => 'Βασική μηνιαία συνδρομή για ομαδικά μαθήματα και χρήση εξοπλισμού',
                'features' => json_encode([
                    'Πρόσβαση σε ομαδικά μαθήματα',
                    'Χρήση εξοπλισμού γυμναστηρίου',
                    'Αποδυτήρια και ντουζ',
                    'Δωρεάν WiFi'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 30 ημέρες από την ενεργοποίηση',
                    'Μη επιστρεπτό',
                    'Δεν περιλαμβάνει personal training'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Basic Membership 3 μήνες',
                'price' => 140.00,
                'sessions' => null,
                'duration' => 90,
                'type' => 'membership',
                'status' => 'active',
                'description' => 'Τριμηνιαία συνδρομή με έκπτωση 7% για ομαδικά μαθήματα',
                'features' => json_encode([
                    'Πρόσβαση σε ομαδικά μαθήματα',
                    'Χρήση εξοπλισμού γυμναστηρίου',
                    'Αποδυτήρια και ντουζ',
                    'Δωρεάν WiFi',
                    'Έκπτωση 7% σε σχέση με μηνιαία συνδρομή'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 90 ημέρες από την ενεργοποίηση',
                    'Μη επιστρεπτό',
                    'Δεν περιλαμβάνει personal training'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Premium Membership 6 μήνες',
                'price' => 300.00,
                'sessions' => null,
                'duration' => 180,
                'type' => 'membership',
                'status' => 'active',
                'description' => 'Εξαμηνιαία premium συνδρομή με έκπτωση 17% και επιπλέον προνόμια',
                'features' => json_encode([
                    'Πρόσβαση σε όλα τα ομαδικά μαθήματα',
                    'Χρήση εξοπλισμού γυμναστηρίου',
                    'Αποδυτήρια και ντουζ',
                    'Δωρεάν WiFi',
                    'Έκπτωση 17% σε σχέση με μηνιαία συνδρομή',
                    'Προτεραιότητα κρατήσεων',
                    'Δωρεάν nutritional consultation'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 180 ημέρες από την ενεργοποίηση',
                    'Μη επιστρεπτό',
                    'Δεν περιλαμβάνει personal training'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Personal Training Packages
            [
                'name' => 'Personal Training 4 συνεδρίες',
                'price' => 160.00,
                'sessions' => 4,
                'duration' => 60,
                'type' => 'personal',
                'status' => 'active',
                'description' => 'Πακέτο 4 προσωπικών προπονήσεων διάρκειας 60 λεπτών',
                'features' => json_encode([
                    '4 προσωπικές προπονήσεις 60 λεπτών',
                    'Εξατομικευμένο πρόγραμμα άσκησης',
                    'Αξιολόγηση φυσικής κατάστασης',
                    'Διατροφικές συμβουλές',
                    'Παρακολούθηση προόδου'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 60 ημέρες από την ενεργοποίηση',
                    'Ακύρωση τουλάχιστον 24 ώρες πριν',
                    'Μη επιστρεπτό',
                    'Μεταφορά σε άλλο άτομο μόνο με έγκριση'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Personal Training 8 συνεδρίες',
                'price' => 300.00,
                'sessions' => 8,
                'duration' => 90,
                'type' => 'personal',
                'status' => 'active',
                'description' => 'Πακέτο 8 προσωπικών προπονήσεων με έκπτωση 6%',
                'features' => json_encode([
                    '8 προσωπικές προπονήσεις 60 λεπτών',
                    'Εξατομικευμένο πρόγραμμα άσκησης',
                    'Αξιολόγηση φυσικής κατάστασης',
                    'Διατροφικές συμβουλές',
                    'Παρακολούθηση προόδου',
                    'Έκπτωση 6% σε σχέση με μονάδα'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 90 ημέρες από την ενεργοποίηση',
                    'Ακύρωση τουλάχιστον 24 ώρες πριν',
                    'Μη επιστρεπτό',
                    'Μεταφορά σε άλλο άτομο μόνο με έγκριση'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Personal Training 12 συνεδρίες',
                'price' => 420.00,
                'sessions' => 12,
                'duration' => 120,
                'type' => 'personal',
                'status' => 'active',
                'description' => 'Πακέτο 12 προσωπικών προπονήσεων με έκπτωση 13%',
                'features' => json_encode([
                    '12 προσωπικές προπονήσεις 60 λεπτών',
                    'Εξατομικευμένο πρόγραμμα άσκησης',
                    'Αξιολόγηση φυσικής κατάστασης',
                    'Διατροφικές συμβουλές',
                    'Παρακολούθηση προόδου',
                    'Έκπτωση 13% σε σχέση με μονάδα',
                    'Δωρεάν body composition analysis'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 120 ημέρες από την ενεργοποίηση',
                    'Ακύρωση τουλάχιστον 24 ώρες πριν',
                    'Μη επιστρεπτό',
                    'Μεταφορά σε άλλο άτομο μόνο με έγκριση'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Specialized Packages
            [
                'name' => 'Yoga & Pilates 10 συνεδρίες',
                'price' => 150.00,
                'sessions' => 10,
                'duration' => 90,
                'type' => 'yoga_pilates',
                'status' => 'active',
                'description' => 'Ειδικό πακέτο για μαθήματα Yoga και Pilates',
                'features' => json_encode([
                    '10 συνεδρίες Yoga ή Pilates',
                    'Μπορεί να χρησιμοποιηθεί σε οποιοδήποτε μάθημα',
                    'Περιλαμβάνει χρήση εξοπλισμού yoga/pilates',
                    'Δωρεάν meditation session',
                    'Έκπτωση 25% σε yoga accessories'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 90 ημέρες από την ενεργοποίηση',
                    'Ακύρωση τουλάχιστον 2 ώρες πριν',
                    'Μη επιστρεπτό',
                    'Ισχύει μόνο για yoga και pilates μαθήματα'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'EMS Training 6 συνεδρίες',
                'price' => 180.00,
                'sessions' => 6,
                'duration' => 60,
                'type' => 'ems',
                'status' => 'active',
                'description' => 'Πακέτο ηλεκτρομυοδιέγερσης για γρήγορα αποτελέσματα',
                'features' => json_encode([
                    '6 συνεδρίες EMS 25 λεπτών',
                    'Εξατομικευμένο πρόγραμμα EMS',
                    'Παρακολούθηση προόδου',
                    'Διατροφικές συμβουλές',
                    'Δωρεάν EMS suit cleaning',
                    'Καθοδήγηση από πιστοποιημένο trainer'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 60 ημέρες από την ενεργοποίηση',
                    'Ακύρωση τουλάχιστον 24 ώρες πριν',
                    'Μη επιστρεπτό',
                    'Απαιτείται medical clearance',
                    'Μόνο για EMS training'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // Trial and Special Packages
            [
                'name' => 'Δοκιμαστικό Πακέτο',
                'price' => 20.00,
                'sessions' => 3,
                'duration' => 14,
                'type' => 'trial',
                'status' => 'active',
                'description' => 'Δοκιμαστικό πακέτο για νέους πελάτες',
                'features' => json_encode([
                    '3 συνεδρίες σε ομαδικά μαθήματα',
                    'Χρήση εξοπλισμού γυμναστηρίου',
                    'Αποδυτήρια και ντουζ',
                    'Δωρεάν fitness assessment',
                    'Ειδική τιμή για νέους πελάτες'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 14 ημέρες από την ενεργοποίηση',
                    'Μόνο για νέους πελάτες',
                    'Μη επιστρεπτό',
                    'Δεν περιλαμβάνει personal training'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Student Package 1 μήνας',
                'price' => 35.00,
                'sessions' => null,
                'duration' => 30,
                'type' => 'student',
                'status' => 'active',
                'description' => 'Ειδική τιμή για φοιτητές με έκπτωση 30%',
                'features' => json_encode([
                    'Πρόσβαση σε ομαδικά μαθήματα',
                    'Χρήση εξοπλισμού γυμναστηρίου',
                    'Αποδυτήρια και ντουζ',
                    'Δωρεάν WiFi',
                    'Έκπτωση 30% για φοιτητές'
                ]),
                'terms' => json_encode([
                    'Ισχύει για 30 ημέρες από την ενεργοποίηση',
                    'Απαιτείται valid φοιτητική ταυτότητα',
                    'Μη επιστρεπτό',
                    'Δεν περιλαμβάνει personal training'
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }

        $this->command->info('Enhanced packages seeded successfully!');
        $this->command->info('- Total packages created: ' . count($packages));
        $this->command->info('- Package types: membership, personal, yoga_pilates, ems, trial, student');
        $this->command->info('- All packages include features and terms');
        $this->command->info('- Various pricing strategies and durations');
    }
}