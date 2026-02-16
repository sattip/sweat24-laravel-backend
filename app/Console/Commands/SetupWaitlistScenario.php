<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\GymClass;
use App\Models\Booking;
use App\Models\ClassWaitlist;
use App\Models\Signature;
use App\Models\Instructor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class SetupWaitlistScenario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:setup-waitlist-scenario {--reset : Reset and delete existing test data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Δημιουργεί ένα πλήρες σενάριο για το testing της λίστας αναμονής με 2 χρήστες, 1 μάθημα και waitlist setup';

    private $testPassword = 'password123';
    private $userA;
    private $userB;
    private $gymClass;
    private $booking;
    private $waitlistEntry;

    public function handle()
    {
        $this->info('🚀 Εκκίνηση δημιουργίας waitlist testing scenario...');

        // Check for reset option
        if ($this->option('reset')) {
            $this->resetTestData();
        }

        try {
            // Step 1: Create test users
            $this->createTestUsers();

            // Step 2: Complete registration process for both users
            $this->completeRegistrationProcess();

            // Step 3: Create a gym class for today
            $this->createTestGymClass();

            // Step 4: Create booking for User A
            $this->createBookingForUserA();

            // Step 5: Add User B to waitlist
            $this->addUserBToWaitlist();

            // Step 6: Display success summary
            $this->displaySuccessSummary();

        } catch (\Exception $e) {
            $this->error('❌ Σφάλμα κατά τη δημιουργία του scenario: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function resetTestData()
    {
        $this->info('🧹 Καθαρισμός προηγούμενων test δεδομένων...');

        // Delete test users and related data
        $testEmails = ['test-user-a@sweat24.gr', 'test-user-b@sweat24.gr'];
        
        foreach ($testEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                // Delete related data
                Booking::where('user_id', $user->id)->delete();
                ClassWaitlist::where('user_id', $user->id)->delete();
                Signature::where('user_id', $user->id)->delete();
                
                // Delete user
                $user->delete();
                $this->info("   Διαγράφηκε χρήστης: {$email}");
            }
        }

        // Delete test gym class
        $testClass = GymClass::where('name', 'LIKE', 'Test Waitlist Class%')->first();
        if ($testClass) {
            Booking::where('class_id', $testClass->id)->delete();
            ClassWaitlist::where('class_id', $testClass->id)->delete();
            $testClass->delete();
            $this->info('   Διαγράφηκε test μάθημα');
        }

        $this->info('✅ Καθαρισμός ολοκληρώθηκε');
        $this->line('');
    }

    private function createTestUsers()
    {
        $this->info('👥 Δημιουργία δοκιμαστικών χρηστών...');

        // Create User A
        $this->userA = User::create([
            'name' => 'Test User A (Κρατήσεις)',
            'email' => 'test-user-a@sweat24.gr',
            'password' => Hash::make($this->testPassword),
            'phone' => '+30 691 0000001',
            'membership_type' => 'Premium',
            'role' => 'member',
            'join_date' => now()->toDateString(),
            'remaining_sessions' => 10,
            'total_sessions' => 10,
            'status' => 'active',
            'registration_status' => 'completed',
            'registration_completed_at' => now(),
            'approved_at' => now(),
            'approved_by' => 1, // Admin
            'medical_history' => 'Καμία ιδιαίτερη ιατρική ιστορία. Γενικά καλή υγεία.',
            'address' => 'Τεστ Διεύθυνση 123, Αθήνα',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'emergency_contact' => 'Επαφή Έκτακτης Ανάγκης A',
            'emergency_phone' => '+30 210 0000001',
            'notes' => 'Test χρήστης για waitlist testing - User A',
        ]);

        // Create User B
        $this->userB = User::create([
            'name' => 'Test User B (Waitlist)',
            'email' => 'test-user-b@sweat24.gr',
            'password' => Hash::make($this->testPassword),
            'phone' => '+30 691 0000002',
            'membership_type' => 'Basic',
            'role' => 'member',
            'join_date' => now()->toDateString(),
            'remaining_sessions' => 8,
            'total_sessions' => 8,
            'status' => 'active',
            'registration_status' => 'completed',
            'registration_completed_at' => now(),
            'approved_at' => now(),
            'approved_by' => 1, // Admin
            'medical_history' => 'Καμία ιδιαίτερη ιατρική ιστορία. Ελαφριά αλλεργία σε σκόνη.',
            'address' => 'Τεστ Διεύθυνση 456, Θεσσαλονίκη',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'emergency_contact' => 'Επαφή Έκτακτης Ανάγκης B',
            'emergency_phone' => '+30 231 0000002',
            'notes' => 'Test χρήστης για waitlist testing - User B',
        ]);

        $this->info("   ✅ Δημιουργήθηκε User A: {$this->userA->name} ({$this->userA->email})");
        $this->info("   ✅ Δημιουργήθηκε User B: {$this->userB->name} ({$this->userB->email})");
    }

    private function completeRegistrationProcess()
    {
        $this->info('📝 Ολοκλήρωση διαδικασίας εγγραφής...');

        // Create dummy signatures for both users
        $this->createDummySignature($this->userA, 'User A Test Signature Data');
        $this->createDummySignature($this->userB, 'User B Test Signature Data');

        $this->info('   ✅ Δημιουργήθηκαν ψηφιακές υπογραφές');
        $this->info('   ✅ Ολοκληρώθηκε η διαδικασία εγγραφής για όλους τους χρήστες');
    }

    private function createDummySignature($user, $signatureData)
    {
        Signature::create([
            'user_id' => $user->id,
            'signature_data' => base64_encode($signatureData),
            'ip_address' => '127.0.0.1',
            'signed_at' => now(),
            'document_type' => 'terms_and_conditions',
            'document_version' => '1.0',
        ]);
    }

    private function createTestGymClass()
    {
        $this->info('🏋️ Δημιουργία test μαθήματος...');

        // Get a random instructor or use default name
        $instructor = Instructor::first();
        $instructorName = $instructor ? $instructor->name : 'Test Instructor';

        // Create gym class for DAY AFTER TOMORROW (to allow cancellation testing)
        $classDate = now()->addDays(2); // +2 days to bypass cancellation policy
        $classTime = $classDate->copy()->setHour(18)->setMinute(0)->setSecond(0);
        
        $this->gymClass = GymClass::create([
            'name' => 'Test Waitlist Class - ' . $classDate->format('d/m/Y H:i'),
            'description' => 'Δοκιμαστικό μάθημα για testing της λίστας αναμονής (μεθαύριο)',
            'instructor' => $instructorName, // Use string field instead of instructor_id
            'date' => $classDate->toDateString(), // Day after tomorrow
            'time' => $classTime->format('H:i:s'),
            'duration' => 60,
            'max_participants' => 1, // ΚΛΕΙΔΙ: Μόνο 1 άτομο χωρητικότητα
            'current_participants' => 0,
            'status' => 'active', // Use valid status from enum
            'location' => 'Test Studio A',
            'type' => 'group',
        ]);

        $this->info("   ✅ Δημιουργήθηκε μάθημα: {$this->gymClass->name}");
        $this->info("   📅 Ημερομηνία: {$this->gymClass->date} στις {$this->gymClass->time}");
        $this->info("   👥 Χωρητικότητα: {$this->gymClass->max_participants} άτομο");
        $this->info("   ⏰ ΣΗΜΕΙΩΣΗ: Μάθημα σε +2 μέρες για να επιτρέπεται ακύρωση!");
    }

    private function createBookingForUserA()
    {
        $this->info('📅 Δημιουργία κράτησης για User A...');

        $this->booking = Booking::create([
            'user_id' => $this->userA->id,
            'class_id' => $this->gymClass->id,
            'customer_name' => $this->userA->name,
            'customer_email' => $this->userA->email,
            'class_name' => $this->gymClass->name,
            'instructor' => $this->gymClass->instructor, // Use string field from gym_class
            'date' => $this->gymClass->date, // Use gym class date
            'time' => $this->gymClass->time,
            'status' => 'confirmed',
            'type' => 'group',
            'attended' => null,
            'booking_time' => now(),
            'location' => $this->gymClass->location,
            'avatar' => null,
            'cancellation_reason' => null,
        ]);

        // Update class participant count
        $this->gymClass->increment('current_participants');

        // Deduct session from user
        $this->userA->decrement('remaining_sessions');

        $this->info("   ✅ Δημιουργήθηκε κράτηση για {$this->userA->name}");
        $this->info("   🎫 Booking ID: {$this->booking->id}");
        $this->info("   🔢 Τρέχουσες κρατήσεις στο μάθημα: {$this->gymClass->fresh()->current_participants}/{$this->gymClass->max_participants}");
    }

    private function addUserBToWaitlist()
    {
        $this->info('⏳ Προσθήκη User B στη λίστα αναμονής...');

        // Create waitlist booking first (matching production logic)
        $waitlistBooking = Booking::create([
            'user_id' => $this->userB->id,
            'class_id' => $this->gymClass->id,
            'customer_name' => $this->userB->name,
            'customer_email' => $this->userB->email,
            'class_name' => $this->gymClass->name,
            'instructor' => $this->gymClass->instructor,
            'date' => $this->gymClass->date, // Use gym class date
            'time' => $this->gymClass->time,
            'status' => 'waitlist', // KEY: waitlist status
            'type' => 'group',
            'attended' => null,
            'booking_time' => now(),
            'location' => $this->gymClass->location,
            'avatar' => null,
            'cancellation_reason' => null,
        ]);

        // Create waitlist entry (matching production logic)
        $this->waitlistEntry = ClassWaitlist::create([
            'class_id' => $this->gymClass->id,
            'user_id' => $this->userB->id,
            'position' => 1, // First in waitlist
            'status' => 'waiting',
            'notified_at' => null,
            'expires_at' => null,
        ]);

        $this->info("   ✅ Προστέθηκε {$this->userB->name} στη λίστα αναμονής");
        $this->info("   🎫 Waitlist Booking ID: {$waitlistBooking->id}");
        $this->info("   📍 Θέση στη λίστα: {$this->waitlistEntry->position}");
        $this->info("   📊 Status: {$this->waitlistEntry->status}");
    }

    private function displaySuccessSummary()
    {
        $this->line('');
        $this->info('🎉 =====================================================');
        $this->info('✅ WAITLIST TESTING SCENARIO ΔΗΜΙΟΥΡΓΗΘΗΚΕ ΕΠΙΤΥΧΩΣ!');
        $this->info('🎉 =====================================================');
        $this->line('');

        // Class Information
        $this->info('📚 ΠΛΗΡΟΦΟΡΙΕΣ ΜΑΘΗΜΑΤΟΣ:');
        $this->line("   📝 Όνομα: {$this->gymClass->name}");
        $this->line("   📅 Ημερομηνία: {$this->gymClass->date}");
        $this->line("   ⏰ Ώρα: {$this->gymClass->time}");
        $this->line("   👥 Χωρητικότητα: {$this->gymClass->max_participants} άτομο");
        $this->line("   📍 Τοποθεσία: {$this->gymClass->location}");
        $this->line("   🆔 Class ID: {$this->gymClass->id}");
        $this->line('');

        // User A (Has Booking)
        $this->info('👤 ΧΡΗΣΤΗΣ Α (ΕΧΕΙ ΚΡΑΤΗΣΗ):');
        $this->line("   📧 Email: {$this->userA->email}");
        $this->line("   🔑 Password: {$this->testPassword}");
        $this->line("   👤 Όνομα: {$this->userA->name}");
        $this->line("   📱 Τηλέφωνο: {$this->userA->phone}");
        $this->line("   🎫 Booking ID: {$this->booking->id}");
        $this->line("   📊 Status: {$this->booking->status}");
        $this->line("   🎯 Εναπομείνασες συνεδρίες: {$this->userA->fresh()->remaining_sessions}");
        $this->line('');

        // User B (On Waitlist)
        $this->info('👤 ΧΡΗΣΤΗΣ Β (ΣΕ ΑΝΑΜΟΝΗ):');
        $this->line("   📧 Email: {$this->userB->email}");
        $this->line("   🔑 Password: {$this->testPassword}");
        $this->line("   👤 Όνομα: {$this->userB->name}");
        $this->line("   📱 Τηλέφωνο: {$this->userB->phone}");
        $this->line("   ⏳ Waitlist ID: {$this->waitlistEntry->id}");
        $this->line("   📍 Θέση στη λίστα: {$this->waitlistEntry->position}");
        $this->line("   📊 Status: {$this->waitlistEntry->status}");
        $this->line("   🎯 Εναπομείνασες συνεδρίες: {$this->userB->fresh()->remaining_sessions}");
        $this->line('');

        // Testing Instructions
        $this->info('🧪 ΟΔΗΓΙΕΣ TESTING:');
        $this->line('   1. Συνδεθείτε ως User A και ακυρώστε την κράτηση');
        $this->line('   2. Ο User B θα πρέπει να λάβει αυτόματα notification');
        $this->line('   3. Ο User B θα έχει περιορισμένο χρόνο να κάνει κράτηση');
        $this->line('   4. Ελέγξτε τα logs για το waitlist notification system');
        $this->line('');

        // Database IDs for reference
        $this->info('🗄️ DATABASE REFERENCE:');
        $this->line("   👤 User A ID: {$this->userA->id}");
        $this->line("   👤 User B ID: {$this->userB->id}");
        $this->line("   📚 Class ID: {$this->gymClass->id}");
        $this->line("   🎫 Booking ID: {$this->booking->id}");
        $this->line("   ⏳ Waitlist ID: {$this->waitlistEntry->id}");
        $this->line('');

        // Reset command
        $this->info('🧹 ΓΙΑ ΚΑΘΑΡΙΣΜΟ:');
        $this->line('   php artisan test:setup-waitlist-scenario --reset');
        $this->line('');

        $this->info('🎯 Το waitlist testing scenario είναι έτοιμο για χρήση!');
    }
}
