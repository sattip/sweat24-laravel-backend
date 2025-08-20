<?php
/**
 * Test script για το Calendar View endpoint
 * Run: php test_calendar_endpoint.php
 */

echo "=====================================\n";
echo "   CALENDAR VIEW ENDPOINT TEST\n";
echo "=====================================\n\n";

// Configuration - Load from environment variables
$baseUrl = getenv('API_BASE_URL') ?: 'http://localhost/api/v1';
$adminToken = getenv('ADMIN_TOKEN');

if (!$adminToken) {
    echo "❌ Error: ADMIN_TOKEN environment variable not set\n";
    echo "Usage: ADMIN_TOKEN=your_token php test_calendar_endpoint.php\n";
    exit(1);
}

$testDate = date('Y-m-d'); // Today's date

echo "📅 Testing date: $testDate\n\n";

// Test 1: Get calendar view for today
echo "Test 1: Getting calendar view for $testDate\n";
echo "----------------------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/booking-requests-calendar?date=$testDate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $adminToken",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "✅ Success! Found " . count($data) . " trainers with appointments\n\n";
    
    foreach ($data as $trainer) {
        echo "👤 Trainer: {$trainer['trainer_name']} (ID: {$trainer['trainer_id']})\n";
        echo "   Appointments: " . count($trainer['appointments']) . "\n";
        
        foreach ($trainer['appointments'] as $apt) {
            echo "   - {$apt['start_time']}-{$apt['end_time']}: ";
            echo "{$apt['client_name']} ({$apt['type']})\n";
        }
        echo "\n";
    }
} else if ($httpCode == 422) {
    echo "❌ Validation Error: $response\n";
    echo "   Make sure to provide date in format: YYYY-MM-DD\n";
} else {
    echo "❌ Failed: $response\n";
}

echo "\n=====================================\n";

// Test 2: Test with specific date
$specificDate = '2025-01-20';
echo "\nTest 2: Testing with specific date: $specificDate\n";
echo "------------------------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/booking-requests-calendar?date=$specificDate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $adminToken",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    
    // Verify response structure
    echo "📋 Verifying response structure:\n";
    
    $valid = true;
    foreach ($data as $trainer) {
        if (!isset($trainer['trainer_id']) || !isset($trainer['trainer_name']) || !isset($trainer['appointments'])) {
            echo "❌ Missing required trainer fields\n";
            $valid = false;
            break;
        }
        
        foreach ($trainer['appointments'] as $apt) {
            if (!isset($apt['id']) || !isset($apt['client_name']) || 
                !isset($apt['start_time']) || !isset($apt['end_time']) || !isset($apt['type'])) {
                echo "❌ Missing required appointment fields\n";
                $valid = false;
                break 2;
            }
        }
    }
    
    if ($valid) {
        echo "✅ Response structure is correct!\n";
        echo "   - All required fields present\n";
        echo "   - Ready for frontend consumption\n";
    }
    
} else {
    echo "❌ Failed with status $httpCode\n";
}

echo "\n=====================================\n";
echo "Test Complete!\n";
echo "=====================================\n";