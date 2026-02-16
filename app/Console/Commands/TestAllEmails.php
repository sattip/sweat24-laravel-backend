<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Package;
use App\Models\WaitlistBooking;
use App\Models\GymClass;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Auth Notifications
use App\Notifications\Auth\RegistrationConfirmationNotification;
use App\Notifications\Auth\AccountApprovedNotification;
use App\Notifications\Auth\AccountRejectedNotification;
use App\Notifications\Auth\ResetPasswordNotification;

// Admin Notifications
use App\Notifications\Admin\NewRegistrationNotification;
use App\Notifications\Admin\NewBookingRequestNotification as AdminNewBookingRequestNotification;

// Booking Notifications
use App\Notifications\Bookings\BookingConfirmationNotification;
use App\Notifications\Bookings\BookingCancelledNotification;
use App\Notifications\Bookings\AppointmentScheduledNotification as BookingAppointmentScheduledNotification;

// Booking Request Notifications
use App\Notifications\BookingRequests\NewBookingRequestNotification;
use App\Notifications\BookingRequests\AppointmentScheduledNotification as BookingRequestAppointmentScheduledNotification;

// Order Notifications
use App\Notifications\Orders\OrderConfirmationNotification;
use App\Notifications\Orders\OrderReadyNotification;

// Payment Notifications
use App\Notifications\Payments\PaymentReceivedNotification;
use App\Notifications\Payments\PaymentInstallmentReceivedNotification;
use App\Notifications\Payments\PaymentOverdueNotification;

// Waitlist Notifications
use App\Notifications\WaitlistSpotAvailableNotification;

class TestAllEmails extends Command
{
    protected $signature = 'email:test-all {email : The email address to send test emails to} {--group=all : Specific group to test (auth|admin|bookings|requests|orders|payments|waitlist|all)} {--sync : Send emails synchronously without queueing} {--keep : Keep test data after sending emails}';
    
    protected $description = 'Send all notification emails to a test email address';

    private $testEmail;
    private $testUser;
    private $adminUser;
    private $createdRecords = [];

    public function handle()
    {
        $this->testEmail = $this->argument('email');
        $group = $this->option('group');
        $sync = $this->option('sync');
        
        $this->info("Sending test emails to: {$this->testEmail}");
        $this->info("Testing group: {$group}");
        if ($sync) {
            $this->info("Mode: Synchronous (not queued)");
            // Disable queueing for testing
            config(['queue.default' => 'sync']);
        }
        $this->newLine();

        // Create test users
        $this->setupTestUsers();

        // Send emails based on group
        switch ($group) {
            case 'auth':
                $this->sendAuthEmails();
                break;
            case 'admin':
                $this->sendAdminEmails();
                break;
            case 'bookings':
                $this->sendBookingEmails();
                break;
            case 'requests':
                $this->sendBookingRequestEmails();
                break;
            case 'orders':
                $this->sendOrderEmails();
                break;
            case 'payments':
                $this->sendPaymentEmails();
                break;
            case 'waitlist':
                $this->sendWaitlistEmails();
                break;
            case 'all':
            default:
                $this->sendAuthEmails();
                $this->sendAdminEmails();
                $this->sendBookingEmails();
                $this->sendBookingRequestEmails();
                $this->sendOrderEmails();
                $this->sendPaymentEmails();
                $this->sendWaitlistEmails();
                break;
        }

        $this->newLine();
        $this->info('All test emails have been queued for sending!');
        $this->info("Check your inbox at: {$this->testEmail}");
        
        // Clean up test data unless --keep option is used
        if (!$this->option('keep')) {
            $this->cleanupTestData();
        }
    }
    
    private function cleanupTestData()
    {
        $this->info('Cleaning up test data...');
        
        // Delete test records in reverse order to avoid foreign key constraints
        foreach (array_reverse($this->createdRecords) as $record) {
            try {
                $record->delete();
            } catch (\Exception $e) {
                // Ignore deletion errors
            }
        }
        
        // Delete test users
        if ($this->testUser && $this->testUser->exists) {
            $this->testUser->delete();
        }
        if ($this->adminUser && $this->adminUser->exists) {
            $this->adminUser->delete();
        }
        
        $this->line('  ✓ Test data cleaned up');
    }

    private function setupTestUsers()
    {
        // Create or find a test user
        $this->testUser = User::firstOrCreate(
            ['email' => 'testuser_' . Str::random(8) . '@test.local'],
            [
                'name' => 'Test User',
                'phone' => '+30 123 456 7890',
                'password' => bcrypt('password'),
                'status' => 'active',
                'registration_status' => 'approved',
                'role' => 'member',
                'membership_type' => 'basic',
                'join_date' => Carbon::now()->format('Y-m-d'),
                'remaining_sessions' => 10,
                'total_sessions' => 10,
            ]
        );
        
        // Override email for notifications
        $this->testUser->email = $this->testEmail;

        // Create or find an admin user  
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_' . Str::random(8) . '@test.local'],
            [
                'name' => 'Admin User',
                'phone' => '+30 987 654 3210',
                'password' => bcrypt('password'),
                'status' => 'active',
                'registration_status' => 'approved',
                'role' => 'admin',
                'membership_type' => 'premium',
                'join_date' => Carbon::now()->format('Y-m-d'),
                'remaining_sessions' => 100,
                'total_sessions' => 100,
            ]
        );
        
        // Override email for notifications
        $this->adminUser->email = $this->testEmail;
    }

    private function sendAuthEmails()
    {
        $this->info('Sending Auth Notifications:');
        
        // Registration Confirmation
        $this->testUser->notify(new RegistrationConfirmationNotification($this->testUser));
        $this->line('  ✓ Registration Confirmation sent');

        // Account Approved
        $this->testUser->notify(new AccountApprovedNotification($this->testUser));
        $this->line('  ✓ Account Approved sent');

        // Account Rejected
        $rejectedUser = clone $this->testUser;
        $rejectedUser->notify(new AccountRejectedNotification($rejectedUser, 'Your account does not meet our requirements.'));
        $this->line('  ✓ Account Rejected sent');

        // Password Reset
        $token = Str::random(60);
        $this->testUser->notify(new ResetPasswordNotification($this->testUser, $token));
        $this->line('  ✓ Password Reset sent');
    }

    private function sendAdminEmails()
    {
        $this->info('Sending Admin Notifications:');
        
        // New Registration (to admin)
        Notification::route('mail', $this->testEmail)
            ->notify(new NewRegistrationNotification($this->testUser));
        $this->line('  ✓ New Registration (Admin) sent');

        // New Booking Request (to admin) - uses Booking model, not BookingRequest
        $booking = $this->createTestBooking();
        Notification::route('mail', $this->testEmail)
            ->notify(new AdminNewBookingRequestNotification($booking, 'class_booking'));
        $this->line('  ✓ New Booking Request (Admin) sent');
    }

    private function sendBookingEmails()
    {
        $this->info('Sending Booking Notifications:');
        
        $booking = $this->createTestBooking();
        
        // Booking Confirmation
        $this->testUser->notify(new BookingConfirmationNotification($booking));
        $this->line('  ✓ Booking Confirmation sent');

        // Booking Cancelled
        $this->testUser->notify(new BookingCancelledNotification($booking, 'Schedule conflict', 'user'));
        $this->line('  ✓ Booking Cancelled sent');

        // Appointment Scheduled
        $this->testUser->notify(new BookingAppointmentScheduledNotification($booking, 'personal_training'));
        $this->line('  ✓ Appointment Scheduled (Booking) sent');
    }

    private function sendBookingRequestEmails()
    {
        $this->info('Sending Booking Request Notifications:');
        
        $bookingRequest = $this->createTestBookingRequest();
        
        // New Booking Request (to user)
        $this->testUser->notify(new NewBookingRequestNotification($bookingRequest));
        $this->line('  ✓ New Booking Request (User) sent');

        // Appointment Scheduled from Request
        $bookingRequest->status = 'scheduled';
        $bookingRequest->scheduled_date = Carbon::now()->addDays(3);
        $bookingRequest->scheduled_time = '14:00';
        $this->testUser->notify(new BookingRequestAppointmentScheduledNotification($bookingRequest));
        $this->line('  ✓ Appointment Scheduled (Request) sent');
    }

    private function sendOrderEmails()
    {
        $this->info('Sending Order Notifications:');
        
        $order = $this->createTestOrder();
        
        // Order Confirmation
        $this->testUser->notify(new OrderConfirmationNotification($order));
        $this->line('  ✓ Order Confirmation sent');

        // Order Ready
        $this->testUser->notify(new OrderReadyNotification($order, 'Please pick up at the front desk'));
        $this->line('  ✓ Order Ready sent');
    }

    private function sendPaymentEmails()
    {
        $this->info('Sending Payment Notifications:');
        
        $payment = $this->createTestPayment();
        
        // Payment Received
        $this->testUser->notify(new PaymentReceivedNotification($payment));
        $this->line('  ✓ Payment Received sent');

        // Payment Installment Received
        $installment = $this->createTestPaymentInstallment();
        $this->testUser->notify(new PaymentInstallmentReceivedNotification($installment));
        $this->line('  ✓ Payment Installment Received sent');

        // Payment Overdue
        $overduePayment = clone $payment;
        $overduePayment->status = 'pending';
        $overduePayment->due_date = Carbon::now()->subDays(7);
        $this->testUser->notify(new PaymentOverdueNotification($overduePayment, 7));
        $this->line('  ✓ Payment Overdue sent');
    }

    private function sendWaitlistEmails()
    {
        $this->info('Sending Waitlist Notifications:');
        
        $gymClass = $this->createTestGymClass();
        $booking = $this->createTestBooking();
        $expiresAt = Carbon::now()->addHours(24);
        
        // Waitlist Spot Available
        $this->testUser->notify(new WaitlistSpotAvailableNotification($gymClass, $booking, $expiresAt));
        $this->line('  ✓ Waitlist Spot Available sent');
    }

    private function createTestBooking()
    {
        // Ensure we have a package first
        $package = $this->createTestPackage();
        
        $booking = Booking::create([
            'user_id' => $this->testUser->id,
            'package_id' => $package->id,
            'appointment_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration' => 60,
            'status' => 'confirmed',
            'total_price' => 150.00,
            'notes' => 'Test booking for email testing',
        ]);

        // Load relationships
        $booking->load(['package', 'user']);
        
        // Track for cleanup
        $this->createdRecords[] = $booking;

        return $booking;
    }

    private function createTestBookingRequest()
    {
        // Ensure we have a package first
        $package = $this->createTestPackage();
        
        $request = BookingRequest::create([
            'user_id' => $this->testUser->id,
            'package_id' => $package->id,
            'preferred_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'preferred_time' => '14:00',
            'alternative_date' => Carbon::now()->addDays(6)->format('Y-m-d'),
            'alternative_time' => '16:00',
            'status' => 'pending',
            'notes' => 'Test booking request',
        ]);

        // Load relationships
        $request->load(['package', 'user']);
        
        // Track for cleanup
        $this->createdRecords[] = $request;

        return $request;
    }

    private function createTestPackage()
    {
        return Package::firstOrCreate(
            ['name' => 'Test Premium Massage Package'],
            [
                'description' => 'A relaxing 60-minute full body massage',
                'duration' => 60,
                'price' => 150.00,
                'is_active' => true,
            ]
        );
    }

    private function createTestOrder()
    {
        $order = Order::create([
            'user_id' => $this->testUser->id,
            'order_number' => 'ORD-TEST-' . date('YmdHis'),
            'total_amount' => 250.00,
            'status' => 'confirmed',
            'payment_method' => 'credit_card',
            'notes' => 'Test order for email testing',
        ]);

        // Load relationships
        $order->load('user');

        // Add mock items as a property (not saved to DB)
        $order->items = collect([
            (object)[
                'name' => 'Premium Massage Package',
                'quantity' => 1,
                'price' => 150.00,
            ],
            (object)[
                'name' => 'Aromatherapy Add-on',
                'quantity' => 1,
                'price' => 100.00,
            ],
        ]);
        
        // Track for cleanup
        $this->createdRecords[] = $order;

        return $order;
    }

    private function createTestPayment()
    {
        $order = $this->createTestOrder();
        
        $payment = Payment::create([
            'user_id' => $this->testUser->id,
            'order_id' => $order->id,
            'amount' => 250.00,
            'payment_method' => 'credit_card',
            'status' => 'completed',
            'transaction_id' => 'TXN-TEST-' . date('YmdHis'),
            'paid_at' => Carbon::now(),
        ]);

        // Load relationships
        $payment->load(['user', 'order']);
        
        // Track for cleanup
        $this->createdRecords[] = $payment;

        return $payment;
    }

    private function createTestPaymentInstallment()
    {
        $payment = $this->createTestPayment();
        
        $installment = PaymentInstallment::create([
            'payment_id' => $payment->id,
            'user_id' => $this->testUser->id,
            'amount' => 50.00,
            'due_date' => Carbon::now()->addDays(30),
            'paid_at' => Carbon::now(),
            'status' => 'paid',
            'installment_number' => 1,
            'total_installments' => 5,
        ]);

        // Load relationships
        $installment->load(['user', 'payment']);
        
        // Track for cleanup
        $this->createdRecords[] = $installment;

        return $installment;
    }

    private function createTestWaitlistBooking()
    {
        $waitlist = new WaitlistBooking([
            'id' => 99999,
            'user_id' => $this->testUser->id,
            'booking_id' => 99999,
            'position' => 1,
            'status' => 'active',
            'notified_at' => null,
        ]);

        $waitlist->setRelation('user', $this->testUser);
        $waitlist->setRelation('booking', $this->createTestBooking());

        return $waitlist;
    }

    private function createTestGymClass()
    {
        return GymClass::firstOrCreate(
            ['name' => 'Test Morning Yoga Class'],
            [
                'description' => 'Start your day with energizing yoga',
                'instructor' => 'Jane Doe',
                'date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'time' => '08:00',
                'duration' => 60,
                'capacity' => 20,
                'current_bookings' => 19,
                'is_active' => true,
            ]
        );
    }
}