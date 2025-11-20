<?php

/**
 * Automated Test Script for Muscle Groups Feature
 * 
 * This script performs actual API calls to test all endpoints
 * and validation scenarios for the muscle groups feature.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Booking;
use App\Models\WorkoutMuscleGroup;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test configuration
$baseUrl = env('APP_URL', 'http://localhost');
$testResults = [];
$passedTests = 0;
$failedTests = 0;

// Helper functions
function makeRequest($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;
    
    $ch = curl_init();
    $url = $baseUrl . $endpoint;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

function runTest($name, $testFunction) {
    global $testResults, $passedTests, $failedTests;
    
    echo "\n🧪 Testing: $name\n";
    
    try {
        $result = $testFunction();
        if ($result === true) {
            echo "   ✅ PASSED\n";
            $passedTests++;
            $testResults[] = ['test' => $name, 'status' => 'PASSED'];
        } else {
            echo "   ❌ FAILED: $result\n";
            $failedTests++;
            $testResults[] = ['test' => $name, 'status' => 'FAILED', 'error' => $result];
        }
    } catch (Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
        $failedTests++;
        $testResults[] = ['test' => $name, 'status' => 'ERROR', 'error' => $e->getMessage()];
    }
}

// Setup test data
echo "🔧 Setting up test data...\n";

// Create test user
$testUser = User::create([
    'name' => 'Test User',
    'email' => 'test_muscle_' . time() . '@example.com',
    'password' => bcrypt('password'),
    'phone' => '6900000000',
    'date_of_birth' => '1990-01-01',
    'height' => 180,
    'weight' => 75,
    'role' => 'client',
    'join_date' => now(),
    'registration_status' => 'approved',
]);

// Create auth token
$token = $testUser->createToken('test-token')->plainTextToken;

// Create test bookings
$attendedBooking = Booking::create([
    'user_id' => $testUser->id,
    'customer_name' => $testUser->name,
    'customer_email' => $testUser->email,
    'class_id' => 1,
    'class_name' => 'Test Class',
    'instructor' => 'Test Instructor',
    'date' => now()->subDay(),
    'time' => '10:00',
    'status' => 'confirmed',
    'type' => 'HIIT',
    'attended' => 1,
    'booking_time' => now()->subDay(),
    'location' => 'Main Gym',
    'booking_type' => 'class',
]);

$notAttendedBooking = Booking::create([
    'user_id' => $testUser->id,
    'customer_name' => $testUser->name,
    'customer_email' => $testUser->email,
    'class_id' => 2,
    'class_name' => 'Test Class 2',
    'instructor' => 'Test Instructor',
    'date' => now()->subDays(2),
    'time' => '11:00',
    'status' => 'confirmed',
    'type' => 'Yoga',
    'attended' => 0,
    'booking_time' => now()->subDays(2),
    'location' => 'Main Gym',
    'booking_type' => 'class',
]);

// Create another user's booking for testing unauthorized access
$otherUser = User::create([
    'name' => 'Other User',
    'email' => 'other_muscle_' . time() . '@example.com',
    'password' => bcrypt('password'),
    'phone' => '6900000001',
    'date_of_birth' => '1990-01-01',
    'height' => 180,
    'weight' => 75,
    'role' => 'client',
    'join_date' => now(),
    'registration_status' => 'approved',
]);

$otherUserBooking = Booking::create([
    'user_id' => $otherUser->id,
    'customer_name' => $otherUser->name,
    'customer_email' => $otherUser->email,
    'class_id' => 3,
    'class_name' => 'Other User Class',
    'instructor' => 'Test Instructor',
    'date' => now()->subDay(),
    'time' => '12:00',
    'status' => 'confirmed',
    'type' => 'Pilates',
    'attended' => 1,
    'booking_time' => now()->subDay(),
    'location' => 'Main Gym',
    'booking_type' => 'class',
]);

echo "✅ Test data created\n";
echo "   User ID: {$testUser->id}\n";
echo "   Attended Booking ID: {$attendedBooking->id}\n";
echo "   Not Attended Booking ID: {$notAttendedBooking->id}\n";
echo "   Other User Booking ID: {$otherUserBooking->id}\n";

// Run tests
echo "\n" . str_repeat('=', 50) . "\n";
echo "🚀 RUNNING TESTS\n";
echo str_repeat('=', 50) . "\n";

// Test 1: Store muscle groups successfully
runTest('Store muscle groups for attended workout', function() use ($attendedBooking, $token) {
    $response = makeRequest('POST', "/api/v1/workouts/{$attendedBooking->id}/muscle-groups", [
        'muscle_groups' => ['legs', 'core']
    ], $token);
    
    if ($response['code'] !== 200) {
        return "Expected 200, got {$response['code']}";
    }
    
    if (!$response['body']['success']) {
        return "Expected success=true";
    }
    
    $stored = WorkoutMuscleGroup::where('booking_id', $attendedBooking->id)->first();
    if (!$stored || $stored->muscle_groups !== ['legs', 'core']) {
        return "Data not stored correctly in database";
    }
    
    return true;
});

// Test 2: Update existing muscle groups
runTest('Update existing muscle groups', function() use ($attendedBooking, $token) {
    $response = makeRequest('POST', "/api/v1/workouts/{$attendedBooking->id}/muscle-groups", [
        'muscle_groups' => ['chest', 'back', 'shoulders']
    ], $token);
    
    if ($response['code'] !== 200) {
        return "Expected 200, got {$response['code']}";
    }
    
    $count = WorkoutMuscleGroup::where('booking_id', $attendedBooking->id)->count();
    if ($count !== 1) {
        return "Expected 1 record (update), found $count";
    }
    
    $stored = WorkoutMuscleGroup::where('booking_id', $attendedBooking->id)->first();
    if ($stored->muscle_groups !== ['chest', 'back', 'shoulders']) {
        return "Update failed";
    }
    
    return true;
});

// Test 3: Retrieve muscle groups
runTest('Retrieve muscle groups for workout', function() use ($attendedBooking, $token) {
    $response = makeRequest('GET', "/api/v1/workouts/{$attendedBooking->id}/muscle-groups", null, $token);
    
    if ($response['code'] !== 200) {
        return "Expected 200, got {$response['code']}";
    }
    
    if (!$response['body']['success']) {
        return "Expected success=true";
    }
    
    if ($response['body']['data']['muscle_groups'] !== ['chest', 'back', 'shoulders']) {
        return "Retrieved wrong data";
    }
    
    return true;
});

// Test 4: Cannot store for non-attended workout
runTest('Cannot store muscle groups for non-attended workout', function() use ($notAttendedBooking, $token) {
    $response = makeRequest('POST', "/api/v1/workouts/{$notAttendedBooking->id}/muscle-groups", [
        'muscle_groups' => ['legs']
    ], $token);
    
    if ($response['code'] !== 422) {
        return "Expected 422, got {$response['code']}";
    }
    
    if ($response['body']['message'] !== 'Cannot record muscle groups for a workout you did not attend') {
        return "Wrong error message";
    }
    
    return true;
});

// Test 5: Cannot access other user's booking
runTest('Cannot access other user\'s booking', function() use ($otherUserBooking, $token) {
    $response = makeRequest('POST', "/api/v1/workouts/{$otherUserBooking->id}/muscle-groups", [
        'muscle_groups' => ['legs']
    ], $token);
    
    if ($response['code'] !== 403) {
        return "Expected 403, got {$response['code']}";
    }
    
    if ($response['body']['message'] !== 'Unauthorized access to this booking') {
        return "Wrong error message";
    }
    
    return true;
});

// Test 6: Validation - empty array
runTest('Validation: empty muscle groups array', function() use ($attendedBooking, $token) {
    $response = makeRequest('POST', "/api/v1/workouts/{$attendedBooking->id}/muscle-groups", [
        'muscle_groups' => []
    ], $token);
    
    if ($response['code'] !== 422) {
        return "Expected 422, got {$response['code']}";
    }
    
    return true;
});

// Test 7: Validation - invalid muscle group
runTest('Validation: invalid muscle group value', function() use ($attendedBooking, $token) {
    $response = makeRequest('POST', "/api/v1/workouts/{$attendedBooking->id}/muscle-groups", [
        'muscle_groups' => ['invalid_group']
    ], $token);
    
    if ($response['code'] !== 422) {
        return "Expected 422, got {$response['code']}";
    }
    
    return true;
});

// Test 8: Validation - missing muscle_groups field
runTest('Validation: missing muscle_groups field', function() use ($attendedBooking, $token) {
    $response = makeRequest('POST', "/api/v1/workouts/{$attendedBooking->id}/muscle-groups", [], $token);
    
    if ($response['code'] !== 422) {
        return "Expected 422, got {$response['code']}";
    }
    
    return true;
});

// Test 9: Validation - not an array
runTest('Validation: muscle_groups must be array', function() use ($attendedBooking, $token) {
    $response = makeRequest('POST', "/api/v1/workouts/{$attendedBooking->id}/muscle-groups", [
        'muscle_groups' => 'legs'
    ], $token);
    
    if ($response['code'] !== 422) {
        return "Expected 422, got {$response['code']}";
    }
    
    return true;
});

// Test 10: Test all valid muscle groups
runTest('All valid muscle group values', function() use ($attendedBooking, $token) {
    $validGroups = [
        'total_body', 'legs', 'chest', 'back',
        'shoulders', 'arms', 'core', 'cardio'
    ];
    
    foreach ($validGroups as $group) {
        $response = makeRequest('POST', "/api/v1/workouts/{$attendedBooking->id}/muscle-groups", [
            'muscle_groups' => [$group]
        ], $token);
        
        if ($response['code'] !== 200) {
            return "Failed for '$group': Expected 200, got {$response['code']}";
        }
    }
    
    return true;
});

// Test 11: Test history endpoint includes muscle groups
runTest('Test history endpoint includes muscle groups', function() use ($testUser, $attendedBooking) {
    // Store muscle groups for the attended booking
    WorkoutMuscleGroup::updateOrCreate(
        ['booking_id' => $attendedBooking->id],
        ['user_id' => $testUser->id, 'muscle_groups' => ['legs', 'core']]
    );
    
    $response = makeRequest('GET', "/api/test-history?user_id={$testUser->id}");
    
    if ($response['code'] !== 200) {
        return "Expected 200, got {$response['code']}";
    }
    
    $bookings = $response['body'];
    $foundBooking = null;
    
    foreach ($bookings as $booking) {
        if ($booking['id'] == $attendedBooking->id) {
            $foundBooking = $booking;
            break;
        }
    }
    
    if (!$foundBooking) {
        return "Booking not found in history";
    }
    
    if (!isset($foundBooking['muscle_groups']) || $foundBooking['muscle_groups'] !== ['legs', 'core']) {
        return "Muscle groups not included correctly";
    }
    
    if (!isset($foundBooking['muscle_groups_recorded']) || $foundBooking['muscle_groups_recorded'] !== true) {
        return "muscle_groups_recorded flag not set correctly";
    }
    
    return true;
});

// Test 12: 404 when no muscle groups recorded
runTest('404 when retrieving non-existent muscle groups', function() use ($notAttendedBooking, $token) {
    // Create a new attended booking without muscle groups
    $newBooking = Booking::create([
        'user_id' => $notAttendedBooking->user_id,
        'customer_name' => 'Test User',
        'customer_email' => 'test@example.com',
        'class_id' => 99,
        'class_name' => 'New Class',
        'instructor' => 'Instructor',
        'date' => now()->subDay(),
        'time' => '15:00',
        'status' => 'confirmed',
        'type' => 'HIIT',
        'attended' => 1,
        'booking_time' => now()->subDay(),
        'location' => 'Main Gym',
        'booking_type' => 'class',
    ]);
    
    $response = makeRequest('GET', "/api/v1/workouts/{$newBooking->id}/muscle-groups", null, $token);
    
    if ($response['code'] !== 404) {
        return "Expected 404, got {$response['code']}";
    }
    
    if ($response['body']['message'] !== 'No muscle groups recorded for this workout') {
        return "Wrong error message";
    }
    
    // Clean up
    $newBooking->delete();
    
    return true;
});

// Clean up test data
echo "\n🧹 Cleaning up test data...\n";

WorkoutMuscleGroup::where('user_id', $testUser->id)->delete();
Booking::where('user_id', $testUser->id)->delete();
Booking::where('user_id', $otherUser->id)->delete();
$testUser->delete();
$otherUser->delete();

echo "✅ Test data cleaned\n";

// Summary
echo "\n" . str_repeat('=', 50) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat('=', 50) . "\n";
echo "✅ Passed: $passedTests\n";
echo "❌ Failed: $failedTests\n";
echo "📈 Total: " . ($passedTests + $failedTests) . "\n";
echo "🎯 Success Rate: " . round(($passedTests / ($passedTests + $failedTests)) * 100, 2) . "%\n";

if ($failedTests > 0) {
    echo "\n⚠️  Failed Tests:\n";
    foreach ($testResults as $result) {
        if ($result['status'] !== 'PASSED') {
            echo "   - {$result['test']}: {$result['error']}\n";
        }
    }
}

echo "\n" . str_repeat('=', 50) . "\n";

exit($failedTests > 0 ? 1 : 0);