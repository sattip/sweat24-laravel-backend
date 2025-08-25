<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Carbon\Carbon;

class TestEmailTemplates extends Command
{
    protected $signature = 'email:test {email : The email address to send test emails to} {--template= : Specific template to test}';
    
    protected $description = 'Send test emails with comprehensive test data';

    public function handle()
    {
        $testEmail = $this->argument('email');
        $template = $this->option('template');
        
        $this->info("Sending test emails to: {$testEmail}");
        if ($template) {
            $this->info("Testing template: {$template}");
        }
        $this->newLine();

        // Comprehensive test data
        $testUser = (object)[
            'id' => 99999,
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $testEmail,
            'phone' => '+30 123 456 7890',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'date_of_birth' => Carbon::now()->subYears(30)->format('Y-m-d'),
            'membership_type' => 'Premium',
            'address' => '123 Test Street, Athens',
            'emergency_contact' => 'Emergency Contact Name',
            'emergency_phone' => '+30 987 654 3210',
            'medical_history' => 'No known conditions',
            'gender' => 'male',
            'weight' => 75,
            'height' => 175,
            'registration_status' => 'approved',
            'status' => 'active',
        ];

        $gymClass = (object)[
            'id' => 1,
            'name' => 'Morning Yoga',
            'instructor' => 'Jane Doe',
            'date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'time' => '08:00',
            'duration' => 60,
            'capacity' => 20,
            'description' => 'Start your day with energizing yoga',
        ];

        $testBooking = (object)[
            'id' => 99999,
            'appointment_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'appointment_time' => '10:00',
            'duration' => 60,
            'status' => 'confirmed',
            'total_price' => 150.00,
            'notes' => 'Test booking',
            'type' => 'class_booking',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'requested_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'requested_time' => '10:00',
            'alternative_date' => Carbon::now()->addDays(8)->format('Y-m-d'),
            'alternative_time' => '14:00',
            'package' => (object)[
                'id' => 1,
                'name' => 'Premium Massage Package',
                'description' => 'A relaxing 60-minute full body massage',
                'duration' => 60,
                'price' => 150.00,
            ],
            'user' => $testUser,
            'classSchedule' => $gymClass,
            'gymClass' => $gymClass,
        ];

        $appointment = (object)[
            'id' => 88888,
            'date' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'time' => '14:00',
            'duration' => 60,
            'type' => 'personal_training',
            'trainer' => 'John Trainer',
            'location' => 'Training Room 1',
            'notes' => 'Please bring your own water bottle',
        ];

        $bookingRequest = (object)[
            'id' => 77777,
            'preferred_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'preferred_time' => '14:00',
            'alternative_date' => Carbon::now()->addDays(6)->format('Y-m-d'),
            'alternative_time' => '16:00',
            'status' => 'pending',
            'notes' => 'Test booking request',
            'created_at' => Carbon::now(),
            'package' => $testBooking->package,
            'user' => $testUser,
        ];

        $testOrder = (object)[
            'id' => 99999,
            'order_number' => 'ORD-TEST-' . date('YmdHis'),
            'total_amount' => 250.00,
            'status' => 'confirmed',
            'payment_method' => 'credit_card',
            'notes' => 'Test order',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'delivery_type' => 'pickup',
            'delivery_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'delivery_time' => '14:00-16:00',
            'delivery_address' => '123 Test Street, Athens',
            'user' => $testUser,
            'items' => collect([
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
            ]),
        ];

        $testPayment = (object)[
            'id' => 99999,
            'amount' => 250.00,
            'payment_method' => 'credit_card',
            'status' => 'completed',
            'transaction_id' => 'TXN-TEST-' . date('YmdHis'),
            'paid_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'late_fees' => 0,
            'user' => $testUser,
            'order' => $testOrder,
        ];

        $paymentInstallment = (object)[
            'id' => 66666,
            'payment_id' => $testPayment->id,
            'amount' => 50.00,
            'due_date' => Carbon::now()->addDays(30),
            'paid_at' => Carbon::now(),
            'status' => 'paid',
            'installment_number' => 1,
            'total_installments' => 5,
            'payment' => $testPayment,
            'user' => $testUser,
        ];

        $waitlistSpot = (object)[
            'position' => 1,
            'gymClass' => $gymClass,
            'booking' => $testBooking,
            'expiresAt' => Carbon::now()->addHours(24),
        ];

        $templates = [
            'auth.registration-confirmation' => [
                'view' => 'emails.auth.registration-confirmation',
                'data' => ['user' => $testUser],
                'subject' => 'TEST: Welcome to Sweat93 - Registration Confirmation',
            ],
            'auth.account-approved' => [
                'view' => 'emails.auth.account-approved',
                'data' => ['user' => $testUser],
                'subject' => 'TEST: Your Sweat93 Account Has Been Approved',
            ],
            'auth.account-rejected' => [
                'view' => 'emails.auth.account-rejected',
                'data' => ['user' => $testUser, 'reason' => 'This is a test rejection reason'],
                'subject' => 'TEST: Your Sweat93 Registration Status',
            ],
            'auth.password-reset' => [
                'view' => 'emails.auth.password-reset',
                'data' => [
                    'user' => $testUser,
                    'token' => 'TEST-TOKEN-123456',
                    'resetUrl' => 'https://example.com/reset-password?token=TEST-TOKEN-123456'
                ],
                'subject' => 'TEST: Reset Your Sweat93 Password',
            ],
            'bookings.booking-confirmation' => [
                'view' => 'emails.bookings.booking-confirmation',
                'data' => ['booking' => $testBooking, 'user' => $testUser],
                'subject' => 'TEST: Booking Confirmation - Sweat93',
            ],
            'bookings.booking-cancelled' => [
                'view' => 'emails.bookings.booking-cancelled',
                'data' => [
                    'booking' => $testBooking,
                    'user' => $testUser,
                    'reason' => 'Schedule conflict',
                    'cancelledBy' => 'user'
                ],
                'subject' => 'TEST: Booking Cancellation - Sweat93',
            ],
            'bookings.appointment-scheduled' => [
                'view' => 'emails.bookings.appointment-scheduled',
                'data' => [
                    'booking' => $testBooking,
                    'appointment' => $appointment,
                    'user' => $testUser,
                    'appointmentType' => 'personal_training'
                ],
                'subject' => 'TEST: Appointment Scheduled - Sweat93',
            ],
            'booking-requests.new-booking-request' => [
                'view' => 'emails.booking-requests.new-booking-request',
                'data' => ['bookingRequest' => $bookingRequest, 'user' => $testUser],
                'subject' => 'TEST: New Booking Request - Sweat93',
            ],
            'booking-requests.appointment-scheduled' => [
                'view' => 'emails.booking-requests.appointment-scheduled',
                'data' => [
                    'bookingRequest' => $bookingRequest,
                    'user' => $testUser,
                    'scheduledDate' => Carbon::now()->addDays(5)->format('Y-m-d'),
                    'scheduledTime' => '14:00'
                ],
                'subject' => 'TEST: Your Appointment Has Been Scheduled - Sweat93',
            ],
            'orders.order-confirmation' => [
                'view' => 'emails.orders.order-confirmation',
                'data' => ['order' => $testOrder, 'user' => $testUser],
                'subject' => 'TEST: Order Confirmation - Sweat93',
            ],
            'orders.order-ready' => [
                'view' => 'emails.orders.order-ready',
                'data' => [
                    'order' => $testOrder,
                    'user' => $testUser,
                    'pickupInstructions' => 'Please pick up at the front desk'
                ],
                'subject' => 'TEST: Your Order is Ready - Sweat93',
            ],
            'payments.payment-received' => [
                'view' => 'emails.payments.payment-received',
                'data' => ['payment' => $testPayment, 'user' => $testUser],
                'subject' => 'TEST: Payment Received - Sweat93',
            ],
            'payments.payment-installment-received' => [
                'view' => 'emails.payments.payment-installment-received',
                'data' => ['paymentInstallment' => $paymentInstallment, 'user' => $testUser],
                'subject' => 'TEST: Payment Installment Received - Sweat93',
            ],
            'payments.payment-overdue' => [
                'view' => 'emails.payments.payment-overdue',
                'data' => [
                    'payment' => (object)array_merge((array)$testPayment, [
                        'status' => 'pending',
                        'due_date' => Carbon::now()->subDays(7),
                        'late_fees' => 25.00,
                    ]),
                    'user' => $testUser,
                    'daysOverdue' => 7
                ],
                'subject' => 'TEST: Payment Overdue - Sweat93',
            ],
            'admin.new-registration' => [
                'view' => 'emails.admin.new-registration',
                'data' => ['user' => $testUser],
                'subject' => 'TEST: New User Registration - Sweat93 Admin',
            ],
            'admin.new-booking-request' => [
                'view' => 'emails.admin.new-booking-request',
                'data' => [
                    'booking' => $testBooking,
                    'user' => $testUser,
                    'requestType' => 'class_booking'
                ],
                'subject' => 'TEST: New Booking Request - Sweat93 Admin',
            ],
            'waitlist.spot-available' => [
                'view' => 'emails.waitlist.spot-available',
                'data' => [
                    'gymClass' => $gymClass,
                    'booking' => $testBooking,
                    'user' => $testUser,
                    'expiresAt' => Carbon::now()->addHours(24),
                ],
                'subject' => 'TEST: A Spot is Available! - Sweat93',
            ],
        ];

        // Filter templates if specific one requested
        if ($template) {
            $templates = array_filter($templates, function($key) use ($template) {
                return str_contains($key, $template);
            }, ARRAY_FILTER_USE_KEY);
            
            if (empty($templates)) {
                $this->error("Template '{$template}' not found.");
                return 1;
            }
        }

        $this->info('Sending test emails:');
        $successCount = 0;
        $failCount = 0;

        foreach ($templates as $name => $config) {
            try {
                Mail::send($config['view'], $config['data'], function (Message $message) use ($testEmail, $config) {
                    $message->to($testEmail)->subject($config['subject']);
                });
                $this->line("  ✓ {$name} sent");
                $successCount++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$name} failed: " . $e->getMessage());
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("Results: {$successCount} sent, {$failCount} failed");
        
        if ($successCount > 0) {
            $this->info("Check your inbox at: {$testEmail}");
            $this->info('Emails with "TEST:" prefix are test emails.');
        }
        
        return $failCount > 0 ? 1 : 0;
    }
}