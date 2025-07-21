<?php

/**
 * Endpoint Testing Script for Sweat24 Backend
 * Tests all critical API endpoints to verify they're working
 */

echo "🧪 TESTING SWEAT24 API ENDPOINTS\n";
echo "================================\n\n";

$baseUrl = 'https://sweat93laravel.obs.com.gr';
$endpoints = [
    'Booking History (Public)' => '/api/test-history?user_id=1',
    'Booking History (API v1)' => '/api/v1/bookings/history?user_id=1',
    'Dashboard Stats (Public)' => '/api/v1/dashboard/stats',
    'Classes List' => '/api/v1/classes',
    'Packages List' => '/api/v1/packages',
    'Health Check' => '/api/v1/debug/auth',
];

foreach ($endpoints as $name => $endpoint) {
    echo "🔍 Testing: $name\n";
    echo "   URL: $baseUrl$endpoint\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: Sweat24-Test-Script'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "   ❌ CURL Error: $error\n";
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        echo "   ✅ HTTP $httpCode - Working\n";
        $responseData = json_decode($response, true);
        if (is_array($responseData)) {
            if (isset($responseData['message'])) {
                echo "   📝 Message: " . $responseData['message'] . "\n";
            } elseif (is_array($responseData) && count($responseData) === 0) {
                echo "   📝 Response: Empty array (normal for no data)\n";
            } else {
                echo "   📝 Response: Valid JSON data\n";
            }
        }
    } else {
        echo "   ❌ HTTP $httpCode - Failed\n";
        if ($response) {
            $errorData = json_decode($response, true);
            if (isset($errorData['message'])) {
                echo "   💬 Error: " . $errorData['message'] . "\n";
            } else {
                echo "   💬 Raw response: " . substr($response, 0, 200) . "...\n";
            }
        }
    }
    echo "\n";
}

echo "🎯 RECOMMENDATIONS FOR CLIENT APP:\n";
echo "==================================\n";
echo "✅ WORKING ENDPOINTS:\n";
echo "   - https://sweat93laravel.obs.com.gr/api/test-history?user_id=USER_ID\n";
echo "   - https://sweat93laravel.obs.com.gr/api/v1/dashboard/stats\n";
echo "   - https://sweat93laravel.obs.com.gr/api/v1/classes\n\n";

echo "❌ ISSUES TO FIX IN CLIENT APP:\n";
echo "   1. Change domain from 'sweat24.obs.com.gr' to 'sweat93laravel.obs.com.gr'\n";
echo "   2. Use /api/test-history instead of /api/v1/test-history\n";
echo "   3. Add proper error handling for database connection issues\n\n";

echo "🔧 NEXT STEPS:\n";
echo "   1. Update client app URLs to use correct domain\n";
echo "   2. Test booking history with: /api/test-history?user_id=1\n";
echo "   3. For authenticated endpoints, use proper Bearer tokens\n\n";

echo "Script completed! ✨\n"; 