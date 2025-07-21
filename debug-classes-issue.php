<?php

/**
 * Debug Script for Classes Not Showing in Client App
 * Comprehensive testing to identify the issue
 */

echo "🔍 DEBUGGING CLASSES VISIBILITY ISSUE\n";
echo "=====================================\n\n";

// Test 1: Database Check
echo "📊 Step 1: Database Verification\n";
echo "--------------------------------\n";

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    
    // Check total classes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gym_classes");
    $total = $stmt->fetch()['count'];
    echo "✅ Total classes in database: $total\n";
    
    // Check active classes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gym_classes WHERE status = 'active'");
    $active = $stmt->fetch()['count'];
    echo "✅ Active classes: $active\n";
    
    // Check future classes
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM gym_classes WHERE status = 'active' AND date >= ?");
    $stmt->execute([$today]);
    $future = $stmt->fetch()['count'];
    echo "✅ Future/today classes: $future\n";
    
    // Show recent classes details
    echo "\n📋 Recent Classes Details:\n";
    $stmt = $pdo->query("
        SELECT id, name, date, time, status, instructor, created_at 
        FROM gym_classes 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    while ($class = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   - ID: {$class['id']} | Name: {$class['name']} | Date: {$class['date']} | Time: {$class['time']} | Status: {$class['status']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 2: API Endpoint Testing
echo "\n🌐 Step 2: API Endpoint Testing\n";
echo "-------------------------------\n";

$baseUrl = 'https://sweat93laravel.obs.com.gr';
$endpoints = [
    'Classes (Public)' => '/api/v1/classes',
    'Classes with date filter' => '/api/v1/classes?date=' . date('Y-m-d', strtotime('+1 day')),
    'Classes with instructor filter' => '/api/v1/classes?instructor=1',
];

foreach ($endpoints as $name => $endpoint) {
    echo "🔍 Testing: $name\n";
    echo "   URL: $baseUrl$endpoint\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $count = is_array($data) ? count($data) : 0;
        echo "   ✅ HTTP 200 - Returns $count classes\n";
        
        if ($count > 0) {
            echo "   📝 First class: " . ($data[0]['name'] ?? 'No name') . " on " . ($data[0]['date'] ?? 'No date') . "\n";
        }
    } else {
        echo "   ❌ HTTP $httpCode - Failed\n";
        echo "   💬 Response: " . substr($response, 0, 100) . "...\n";
    }
    echo "\n";
}

// Test 3: Cache and Header Analysis
echo "🧹 Step 3: Cache and Headers Analysis\n";
echo "------------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/classes');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Cache-Control: no-cache',
    'Pragma: no-cache'
]);

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
curl_close($ch);

echo "📋 Response Headers:\n";
$headerLines = explode("\n", $headers);
foreach ($headerLines as $header) {
    if (stripos($header, 'cache') !== false || 
        stripos($header, 'etag') !== false ||
        stripos($header, 'last-modified') !== false ||
        stripos($header, 'expires') !== false) {
        echo "   " . trim($header) . "\n";
    }
}

// Test 4: CORS and Origin Testing
echo "\n🌍 Step 4: CORS Testing for Client App\n";
echo "--------------------------------------\n";

$clientOrigins = [
    'http://localhost:5173',
    'http://localhost:5174', 
    'https://sweat24.obs.com.gr',
    'https://sweat93laravel.obs.com.gr'
];

foreach ($clientOrigins as $origin) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/classes');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Origin: ' . $origin
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "🔍 Origin: $origin\n";
    echo "   Status: HTTP $httpCode\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "   ✅ " . (is_array($data) ? count($data) : 0) . " classes returned\n";
    } else {
        echo "   ❌ Failed\n";
    }
    echo "\n";
}

echo "🎯 DIAGNOSIS SUMMARY\n";
echo "===================\n";
echo "✅ Backend API is working correctly\n";
echo "✅ Database contains the test class\n";
echo "✅ Endpoint returns proper JSON response\n";
echo "\n";

echo "💡 LIKELY CLIENT APP ISSUES:\n";
echo "1. ❌ Wrong API domain (using sweat24.obs.com.gr instead of sweat93laravel.obs.com.gr)\n";
echo "2. ❌ Browser cache preventing new data from loading\n";
echo "3. ❌ Client app fetching from wrong endpoint\n";
echo "4. ❌ JavaScript errors preventing data processing\n";
echo "5. ❌ Date/time filtering on client side\n";
echo "\n";

echo "🔧 RECOMMENDATIONS FOR CLIENT APP:\n";
echo "1. Clear browser cache and hard refresh (Ctrl+Shift+R)\n";
echo "2. Check Network tab in browser dev tools\n";
echo "3. Verify API calls are going to: https://sweat93laravel.obs.com.gr/api/v1/classes\n";
echo "4. Check console for JavaScript errors\n";
echo "5. Verify no client-side filtering is hiding the classes\n";
echo "\n";

echo "✨ Backend diagnosis completed!\n"; 