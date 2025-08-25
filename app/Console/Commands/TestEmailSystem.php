<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentInstallment;
use App\Models\BookingRequest;
use App\Notifications\Auth\RegistrationConfirmationNotification;
use App\Notifications\Auth\AccountApprovedNotification;
use App\Notifications\Auth\AccountRejectedNotification;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Bookings\BookingConfirmationNotification;
use App\Notifications\Bookings\BookingCancelledNotification;
use App\Notifications\Bookings\AppointmentScheduledNotification;
use App\Notifications\Orders\OrderConfirmationNotification;
use App\Notifications\Orders\OrderReadyNotification;
use App\Notifications\Payments\PaymentReceivedNotification;
use App\Notifications\Admin\NewRegistrationNotification;
use App\Notifications\Admin\NewBookingRequestNotification;
use Carbon\Carbon;

class TestEmailSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test 
                            {--email= : Email address to send test emails to}
                            {--type= : Type of email to test (all, registration, booking, order, payment)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the email notification system by sending sample emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $type = $this->option('type') ?? 'all';

        if (!$email) {
            $email = $this->ask('Ποια διεύθυνση email θέλετε να χρησιμοποιήσετε για τα test emails;');
        }

        // Create or find test user
        $testUser = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Test User',
                'password' => bcrypt('password123'),
                'phone' => '6912345678',
                'membership_type' => 'Basic',
                'role' => 'member',
                'status' => 'active',
                'remaining_sessions' => 10,
                'total_sessions' => 20,
            ]
        );

        $this->info("Στέλνοντας test emails στο: {$email}");
        $this->info("Τύπος: {$type}");
        $this->newLine();

        try {
            if ($type === 'all' || $type === 'registration') {
                $this->testRegistrationEmails($testUser);
            }

            if ($type === 'all' || $type === 'booking') {
                $this->testBookingEmails($testUser);
            }

            if ($type === 'all' || $type === 'order') {
                $this->testOrderEmails($testUser);
            }

            if ($type === 'all' || $type === 'payment') {
                $this->testPaymentEmails($testUser);
            }

            $this->newLine();
            $this->info('✅ Όλα τα test emails στάλθηκαν επιτυχώς!');
            $this->info('Ελέγξτε το inbox σας (και τον φάκελο spam) για τα emails.');
            
        } catch (\Exception $e) {
            $this->error('❌ Σφάλμα κατά την αποστολή emails: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    private function testRegistrationEmails($user)
    {
        $this->info('📧 Στέλνοντας Registration Emails...');

        // Registration Confirmation
        $this->line('  - Registration Confirmation');
        $user->notify(new RegistrationConfirmationNotification($user));

        // Account Approved
        $this->line('  - Account Approved');
        $user->notify(new AccountApprovedNotification($user));

        // Account Rejected
        $this->line('  - Account Rejected');
        $user->notify(new AccountRejectedNotification($user, 'Τεστ λόγος απόρριψης'));

        // Password Reset
        $this->line('  - Password Reset');
        $user->notify(new ResetPasswordNotification($user, 'test-token-123'));

        // Admin notification for new registration
        $this->line('  - New Registration (Admin)');
        $user->notify(new NewRegistrationNotification($user));

        $this->info('  ✓ Registration emails στάλθηκαν');
    }

    private function testBookingEmails($user)
    {
        $this->info('📧 Στέλνοντας Booking Emails...');

        // Create test gym class
        $gymClass = GymClass::firstOrCreate(
            ['name' => 'Test Class'],
            [
                'instructor_id' => 1,
                'date' => Carbon::tomorrow(),
                'time' => '10:00',
                'duration' => 60,
                'max_capacity' => 20,
                'current_bookings' => 5,
                'class_type' => 'Yoga',
                'difficulty_level' => 'Beginner',
                'location' => 'Studio A',
            ]
        );

        // Create test booking
        $booking = Booking::firstOrCreate(
            [
                'user_id' => $user->id,
                'gym_class_id' => $gymClass->id,
            ],
            [
                'booking_date' => now(),
                'status' => 'confirmed',
            ]
        );

        // Booking Confirmation
        $this->line('  - Booking Confirmation');
        $user->notify(new BookingConfirmationNotification($booking));

        // Booking Cancelled
        $this->line('  - Booking Cancelled');
        $user->notify(new BookingCancelledNotification($booking, 'user', 'Τεστ λόγος ακύρωσης'));

        // Appointment Scheduled
        $this->line('  - Appointment Scheduled');
        $appointmentData = (object) [
            'id' => 1,
            'service_type' => 'Personal Training',
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'appointment_time' => '14:00',
            'duration' => 60,
            'instructor_name' => 'Γιάννης Παπαδόπουλος',
            'location' => 'Αίθουσα PT',
            'notes' => 'Φέρτε πετσέτα και νερό',
        ];
        $user->notify(new AppointmentScheduledNotification($appointmentData));

        $this->info('  ✓ Booking emails στάλθηκαν');
    }

    private function testOrderEmails($user)
    {
        $this->info('📧 Στέλνοντας Order Emails...');

        // Create test order
        $order = Order::firstOrCreate(
            ['user_id' => $user->id],
            [
                'order_number' => 'ORD-' . date('YmdHis'),
                'status' => 'pending',
                'total' => 50.00,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'delivery_method' => 'pickup',
                'notes' => 'Τεστ παραγγελία',
            ]
        );

        // Create test order items
        OrderItem::firstOrCreate(
            ['order_id' => $order->id],
            [
                'product_id' => 1,
                'product_name' => 'Protein Shake',
                'quantity' => 2,
                'price' => 25.00,
                'subtotal' => 50.00,
            ]
        );

        // Order Confirmation
        $this->line('  - Order Confirmation');
        $user->notify(new OrderConfirmationNotification($order));

        // Order Ready
        $this->line('  - Order Ready');
        $user->notify(new OrderReadyNotification($order));

        $this->info('  ✓ Order emails στάλθηκαν');
    }

    private function testPaymentEmails($user)
    {
        $this->info('📧 Στέλνοντας Payment Emails...');

        // Create test payment
        $payment = PaymentInstallment::firstOrCreate(
            ['user_id' => $user->id],
            [
                'amount' => 100.00,
                'due_date' => Carbon::now()->addDays(7),
                'status' => 'paid',
                'payment_method' => 'credit_card',
                'description' => 'Μηνιαία συνδρομή',
                'paid_at' => now(),
                'transaction_id' => 'TXN-' . uniqid(),
            ]
        );

        // Payment Received
        $this->line('  - Payment Received');
        $user->notify(new PaymentReceivedNotification($payment));

        // Payment Overdue
        $this->line('  - Payment Overdue');
        $overduePayment = clone $payment;
        $overduePayment->status = 'overdue';
        $overduePayment->due_date = Carbon::now()->subDays(7);
        $user->notify(new \App\Notifications\Payments\PaymentOverdueNotification($overduePayment));

        $this->info('  ✓ Payment emails στάλθηκαν');
    }
}