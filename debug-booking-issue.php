<?php

/**
 * Debug Script for Booking Issue
 * Comprehensive testing to verify user packages and booking logic
 */

echo "🔍 DEBUGGING BOOKING ISSUE FOR user@sweat24.gr\n";
echo "==============================================\n\n";

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    
    // Test 1: User Verification
    echo "👤 Step 1: User Verification\n";
    echo "----------------------------\n";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['user@sweat24.gr']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User found: {$user['name']} (ID: {$user['id']})\n";
        echo "   Email: {$user['email']}\n";
        echo "   Status: {$user['status']}\n\n";
        
        $userId = $user['id'];
    } else {
        echo "❌ User not found!\n\n";
        exit;
    }
    
    // Test 2: User Packages Check
    echo "📦 Step 2: User Packages Check\n";
    echo "------------------------------\n";
    
    $stmt = $pdo->prepare("
        SELECT up.*, p.name as package_name 
        FROM user_packages up 
        LEFT JOIN packages p ON up.package_id = p.id 
        WHERE up.user_id = ?
    ");
    $stmt->execute([$userId]);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total packages: " . count($packages) . "\n";
    
    foreach ($packages as $pkg) {
        echo "   Package: {$pkg['name']} ({$pkg['package_name']})\n";
        echo "   Status: {$pkg['status']}\n";
        echo "   Sessions: {$pkg['remaining_sessions']}/{$pkg['total_sessions']}\n";
        echo "   Expires: {$pkg['expiry_date']}\n";
        echo "   ---\n";
    }
    
    // Test 3: Active Package Check (as in BookingController)
    echo "\n🔍 Step 3: Active Package Check (Booking Logic)\n";
    echo "-----------------------------------------------\n";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM user_packages 
        WHERE user_id = ? 
        AND status = 'active' 
        AND remaining_sessions > 0
    ");
    $stmt->execute([$userId]);
    $hasAvailableSessions = $stmt->fetch()['count'] > 0;
    
    echo "Has available sessions (Booking Logic): " . ($hasAvailableSessions ? "YES ✅" : "NO ❌") . "\n";
    
    if ($hasAvailableSessions) {
        echo "✅ User should be able to make bookings!\n";
    } else {
        echo "❌ User cannot make bookings - no active sessions!\n";
        
        // Debug why
        $stmt = $pdo->prepare("SELECT * FROM user_packages WHERE user_id = ?");
        $stmt->execute([$userId]);
        $allPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nDEBUG: All user packages:\n";
        foreach ($allPackages as $pkg) {
            echo "   - ID: {$pkg['id']}, Status: {$pkg['status']}, Sessions: {$pkg['remaining_sessions']}\n";
        }
    }
    
    // Test 4: Manual Booking Test
    echo "\n📝 Step 4: API Booking Test\n";
    echo "---------------------------\n";
    
    $bookingData = [
        'user_id' => $userId,
        'class_id' => 1,
        'class_name' => 'τεστ',
        'instructor' => 'Γιαννης Παπαδοπουλος',
        'date' => '2025-07-22',
        'time' => '15:00',
        'type' => 'group',
        'location' => 'main'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sweat93laravel.obs.com.gr/api/v1/bookings');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bookingData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "API Response (HTTP $httpCode):\n";
    echo $response . "\n";
    
    if ($httpCode === 201) {
        echo "✅ Booking created successfully!\n";
        
        // Check if session was deducted
        $stmt = $pdo->prepare("
            SELECT remaining_sessions 
            FROM user_packages 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        $newSessions = $stmt->fetch()['remaining_sessions'];
        echo "   Remaining sessions after booking: $newSessions\n";
        
    } elseif ($httpCode === 403) {
        echo "❌ Booking blocked - no available sessions\n";
    } else {
        echo "❌ Booking failed with HTTP $httpCode\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 SUMMARY\n";
echo "==========\n";
echo "If booking failed due to 'no available sessions', the issue is:\n";
echo "1. User doesn't have an active package, OR\n";
echo "2. User's package has 0 remaining sessions, OR\n";
echo "3. User's package status is not 'active'\n";
echo "\nSolution: Create/update user package with active status and remaining_sessions > 0\n";
echo "\n✨ Debug completed!\n"; 