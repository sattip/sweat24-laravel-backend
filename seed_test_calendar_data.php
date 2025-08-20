<?php
/**
 * Script για δημιουργία test data για το calendar endpoint
 * Run: php seed_test_calendar_data.php
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BookingRequest;
use App\Models\User;
use App\Models\Instructor;

echo "=====================================\n";
echo "   CREATING TEST CALENDAR DATA\n";
echo "=====================================\n\n";

$today = \Carbon\Carbon::today();
$tomorrow = \Carbon\Carbon::tomorrow();

// Get or create test instructors
$instructor1 = Instructor::first();
$instructor2 = Instructor::skip(1)->first();

if (!$instructor1 || !$instructor2) {
    echo "❌ Need at least 2 instructors in database\n";
    exit(1);
}

echo "Using instructors:\n";
echo "  - {$instructor1->name} (ID: {$instructor1->id})\n";
echo "  - {$instructor2->name} (ID: {$instructor2->id})\n\n";

// Get a test user
$user = User::where('role', 'client')->first();

echo "Creating test bookings for TODAY ({$today->format('Y-m-d')}):\n";

// Create confirmed bookings for today
$bookings = [
    [
        'instructor_id' => $instructor1->id,
        'service_type' => 'personal',
        'client_name' => 'Μαρία Παπαδοπούλου',
        'client_email' => 'maria@test.com',
        'client_phone' => '6900000001',
        'confirmed_time' => '09:00',
        'user_id' => $user ? $user->id : null, // Test with user
    ],
    [
        'instructor_id' => $instructor1->id,
        'service_type' => 'ems',
        'client_name' => 'Γιώργος Νικολάου',
        'client_email' => 'george@test.com',
        'client_phone' => '6900000002',
        'confirmed_time' => '10:00',
        'user_id' => null, // Test without user
    ],
    [
        'instructor_id' => $instructor2->id,
        'service_type' => 'personal',
        'client_name' => 'Ελένη Δημητρίου',
        'client_email' => 'eleni@test.com',
        'client_phone' => '6900000003',
        'confirmed_time' => '11:30',
        'user_id' => null,
    ],
    [
        'instructor_id' => $instructor1->id,
        'service_type' => 'ems',
        'client_name' => 'Νίκος Αντωνίου',
        'client_email' => 'nikos@test.com',
        'client_phone' => '6900000004',
        'confirmed_time' => '14:00',
        'user_id' => null,
    ],
    [
        'instructor_id' => $instructor2->id,
        'service_type' => 'personal',
        'client_name' => 'Άννα Σταύρου',
        'client_email' => 'anna@test.com',
        'client_phone' => '6900000005',
        'confirmed_time' => '15:30',
        'user_id' => null,
    ],
];

foreach ($bookings as $bookingData) {
    $booking = BookingRequest::create([
        'user_id' => $bookingData['user_id'],
        'instructor_id' => $bookingData['instructor_id'],
        'service_type' => $bookingData['service_type'],
        'client_name' => $bookingData['client_name'],
        'client_email' => $bookingData['client_email'],
        'client_phone' => $bookingData['client_phone'],
        'preferred_time_slots' => [
            ['date' => $today->format('Y-m-d'), 'start_time' => $bookingData['confirmed_time'], 'end_time' => '18:00']
        ],
        'status' => BookingRequest::STATUS_CONFIRMED,
        'confirmed_date' => $today->format('Y-m-d'),
        'confirmed_time' => $bookingData['confirmed_time'],
        'notes' => 'Test booking for calendar view',
    ]);
    
    $instructor = Instructor::find($bookingData['instructor_id']);
    echo "  ✅ Created {$bookingData['service_type']} at {$bookingData['confirmed_time']} with {$instructor->name}\n";
}

echo "\nCreating test bookings for TOMORROW ({$tomorrow->format('Y-m-d')}):\n";

// Create some for tomorrow too
$tomorrowBookings = [
    [
        'instructor_id' => $instructor1->id,
        'service_type' => 'personal',
        'client_name' => 'Κώστας Ιωάννου',
        'confirmed_time' => '10:00',
    ],
    [
        'instructor_id' => $instructor2->id,
        'service_type' => 'ems',
        'client_name' => 'Σοφία Μιχαήλ',
        'confirmed_time' => '11:00',
    ],
];

foreach ($tomorrowBookings as $bookingData) {
    BookingRequest::create([
        'instructor_id' => $bookingData['instructor_id'],
        'service_type' => $bookingData['service_type'],
        'client_name' => $bookingData['client_name'],
        'client_email' => 'test@test.com',
        'client_phone' => '6900000000',
        'preferred_time_slots' => [
            ['date' => $tomorrow->format('Y-m-d'), 'start_time' => $bookingData['confirmed_time'], 'end_time' => '18:00']
        ],
        'status' => BookingRequest::STATUS_CONFIRMED,
        'confirmed_date' => $tomorrow->format('Y-m-d'),
        'confirmed_time' => $bookingData['confirmed_time'],
        'notes' => 'Test booking for tomorrow',
    ]);
    
    $instructor = Instructor::find($bookingData['instructor_id']);
    echo "  ✅ Created {$bookingData['service_type']} at {$bookingData['confirmed_time']} with {$instructor->name}\n";
}

echo "\n=====================================\n";
echo "✅ Test data created successfully!\n";
echo "=====================================\n";
echo "\nNow you can test the endpoint with:\n";
echo "  - Today: ?date={$today->format('Y-m-d')}\n";
echo "  - Tomorrow: ?date={$tomorrow->format('Y-m-d')}\n";