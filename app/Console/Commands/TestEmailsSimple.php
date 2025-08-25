<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Carbon\Carbon;

class TestEmailsSimple extends Command
{
    protected $signature = 'email:test-simple {email : The email address to send test emails to}';
    
    protected $description = 'Send test emails directly to verify email system is working';

    public function handle()
    {
        $testEmail = $this->argument('email');
        
        $this->info("Sending test emails to: {$testEmail}");
        $this->newLine();

        // Test data - split name into first and last for templates
        $testUser = (object)[
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $testEmail,
            'phone' => '+30 123 456 7890',
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
            'package' => (object)[
                'name' => 'Premium Massage Package',
                'description' => 'A relaxing 60-minute full body massage',
                'duration' => 60,
                'price' => 150.00,
            ],
            'user' => $testUser,
        ];

        $testOrder = (object)[
            'id' => 99999,
            'order_number' => 'ORD-TEST-' . date('YmdHis'),
            'total_amount' => 250.00,
            'status' => 'confirmed',
            'payment_method' => 'credit_card',
            'notes' => 'Test order',
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
            'user' => $testUser,
            'order' => $testOrder,
        ];

        $this->info('Sending test emails:');

        // 1. Registration Confirmation
        try {
            Mail::send('emails.auth.registration-confirmation', ['user' => $testUser], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Welcome to Sweat93 - Registration Confirmation');
            });
            $this->line('  ✓ Registration Confirmation sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Registration Confirmation failed: ' . $e->getMessage());
        }

        // 2. Account Approved
        try {
            Mail::send('emails.auth.account-approved', ['user' => $testUser], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Your Sweat93 Account Has Been Approved');
            });
            $this->line('  ✓ Account Approved sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Account Approved failed: ' . $e->getMessage());
        }

        // 3. Account Rejected
        try {
            Mail::send('emails.auth.account-rejected', [
                'user' => $testUser,
                'reason' => 'This is a test rejection reason'
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Your Sweat93 Registration Status');
            });
            $this->line('  ✓ Account Rejected sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Account Rejected failed: ' . $e->getMessage());
        }

        // 4. Password Reset
        try {
            Mail::send('emails.auth.password-reset', [
                'user' => $testUser,
                'token' => 'TEST-TOKEN-123456',
                'resetUrl' => 'https://example.com/reset-password?token=TEST-TOKEN-123456'
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Reset Your Sweat93 Password');
            });
            $this->line('  ✓ Password Reset sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Password Reset failed: ' . $e->getMessage());
        }

        // 5. Booking Confirmation
        try {
            Mail::send('emails.bookings.booking-confirmation', [
                'booking' => $testBooking,
                'user' => $testUser
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Booking Confirmation - Sweat93');
            });
            $this->line('  ✓ Booking Confirmation sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Booking Confirmation failed: ' . $e->getMessage());
        }

        // 6. Booking Cancelled
        try {
            Mail::send('emails.bookings.booking-cancelled', [
                'booking' => $testBooking,
                'user' => $testUser,
                'reason' => 'Schedule conflict',
                'cancelledBy' => 'user'
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Booking Cancellation - Sweat93');
            });
            $this->line('  ✓ Booking Cancelled sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Booking Cancelled failed: ' . $e->getMessage());
        }

        // 7. Appointment Scheduled
        try {
            Mail::send('emails.bookings.appointment-scheduled', [
                'booking' => $testBooking,
                'user' => $testUser,
                'appointmentType' => 'personal_training'
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Appointment Scheduled - Sweat93');
            });
            $this->line('  ✓ Appointment Scheduled sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Appointment Scheduled failed: ' . $e->getMessage());
        }

        // 8. Order Confirmation
        try {
            Mail::send('emails.orders.order-confirmation', [
                'order' => $testOrder,
                'user' => $testUser
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Order Confirmation - Sweat93');
            });
            $this->line('  ✓ Order Confirmation sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Order Confirmation failed: ' . $e->getMessage());
        }

        // 9. Order Ready
        try {
            Mail::send('emails.orders.order-ready', [
                'order' => $testOrder,
                'user' => $testUser,
                'pickupInstructions' => 'Please pick up at the front desk'
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Your Order is Ready - Sweat93');
            });
            $this->line('  ✓ Order Ready sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Order Ready failed: ' . $e->getMessage());
        }

        // 10. Payment Received
        try {
            Mail::send('emails.payments.payment-received', [
                'payment' => $testPayment,
                'user' => $testUser
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Payment Received - Sweat93');
            });
            $this->line('  ✓ Payment Received sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Payment Received failed: ' . $e->getMessage());
        }

        // 11. Payment Overdue
        try {
            $overduePayment = clone $testPayment;
            $overduePayment->status = 'pending';
            $overduePayment->due_date = Carbon::now()->subDays(7);
            
            Mail::send('emails.payments.payment-overdue', [
                'payment' => $overduePayment,
                'user' => $testUser,
                'daysOverdue' => 7
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Payment Overdue - Sweat93');
            });
            $this->line('  ✓ Payment Overdue sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Payment Overdue failed: ' . $e->getMessage());
        }

        // 12. New Registration (Admin)
        try {
            Mail::send('emails.admin.new-registration', ['user' => $testUser], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: New User Registration - Sweat93 Admin');
            });
            $this->line('  ✓ New Registration (Admin) sent');
        } catch (\Exception $e) {
            $this->error('  ✗ New Registration (Admin) failed: ' . $e->getMessage());
        }

        // 13. New Booking Request (Admin)
        try {
            Mail::send('emails.admin.new-booking-request', [
                'booking' => $testBooking,
                'user' => $testUser,
                'requestType' => 'class_booking'
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: New Booking Request - Sweat93 Admin');
            });
            $this->line('  ✓ New Booking Request (Admin) sent');
        } catch (\Exception $e) {
            $this->error('  ✗ New Booking Request (Admin) failed: ' . $e->getMessage());
        }
        
        // 14. New Booking Request (User)
        try {
            $bookingRequest = (object)[
                'id' => 77777,
                'preferred_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'preferred_time' => '14:00',
                'alternative_date' => Carbon::now()->addDays(6)->format('Y-m-d'),
                'alternative_time' => '16:00',
                'status' => 'pending',
                'notes' => 'Test booking request',
                'package' => $testBooking->package,
                'user' => $testUser,
            ];
            
            Mail::send('emails.booking-requests.new-request', [
                'bookingRequest' => $bookingRequest,
                'user' => $testUser
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: New Booking Request - Sweat93');
            });
            $this->line('  ✓ New Booking Request (User) sent');
        } catch (\Exception $e) {
            $this->error('  ✗ New Booking Request (User) failed: ' . $e->getMessage());
        }
        
        // 15. Booking Request Appointment Scheduled
        try {
            $bookingRequest = (object)[
                'id' => 77777,
                'preferred_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'preferred_time' => '14:00',
                'scheduled_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'scheduled_time' => '14:00',
                'status' => 'scheduled',
                'notes' => 'Test booking request',
                'package' => $testBooking->package,
                'user' => $testUser,
            ];
            
            Mail::send('emails.booking-requests.appointment-scheduled', [
                'bookingRequest' => $bookingRequest,
                'user' => $testUser,
                'scheduledDate' => $bookingRequest->scheduled_date,
                'scheduledTime' => $bookingRequest->scheduled_time
            ], function (Message $message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('TEST: Your Appointment Has Been Scheduled - Sweat93');
            });
            $this->line('  ✓ Booking Request Appointment Scheduled sent');
        } catch (\Exception $e) {
            $this->error('  ✗ Booking Request Appointment Scheduled failed: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('Test emails have been sent!');
        $this->info("Check your inbox at: {$testEmail}");
        $this->info('Note: Emails with "TEST:" prefix are test emails.');
    }
}