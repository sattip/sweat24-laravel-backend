<?php

/**
 * User Package Creation Utility
 * Creates active packages for users to enable bookings
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

echo "🎯 USER PACKAGE CREATION UTILITY\n";
echo "================================\n\n";

// Get user email from command line or prompt
$userEmail = $argv[1] ?? null;
if (!$userEmail) {
    echo "Usage: php create-user-package.php user@example.com [sessions] [days]\n";
    echo "Or run without args for interactive mode\n\n";
    
    echo "Enter user email: ";
    $userEmail = trim(fgets(STDIN));
}

$sessions = $argv[2] ?? 10;
$days = $argv[3] ?? 30;

echo "Creating package for: $userEmail\n";
echo "Sessions: $sessions\n";
echo "Duration: $days days\n\n";

try {
    // Find user
    $user = App\Models\User::where('email', $userEmail)->first();
    if (!$user) {
        echo "❌ User not found: $userEmail\n";
        exit(1);
    }
    
    echo "✅ User found: {$user->name} (ID: {$user->id})\n";
    
    // Check for existing active packages
    $existingActive = App\Models\UserPackage::where('user_id', $user->id)
        ->where('status', 'active')
        ->count();
    
    if ($existingActive > 0) {
        echo "⚠️  User already has $existingActive active package(s)\n";
        echo "Continue anyway? (y/n): ";
        $confirm = trim(fgets(STDIN));
        if (strtolower($confirm) !== 'y') {
            echo "Cancelled.\n";
            exit(0);
        }
    }
    
    // Get or create base package
    $basePackage = App\Models\Package::first();
    if (!$basePackage) {
        $basePackage = App\Models\Package::create([
            'name' => 'Standard Membership',
            'description' => 'Standard monthly membership package',
            'price' => 50.00,
            'sessions' => $sessions,
            'duration' => $days,
            'type' => 'monthly',
            'status' => 'active'
        ]);
        echo "✅ Created base package: {$basePackage->name}\n";
    } else {
        echo "✅ Using existing base package: {$basePackage->name}\n";
    }
    
    // Create user package
    $userPackage = App\Models\UserPackage::create([
        'user_id' => $user->id,
        'package_id' => $basePackage->id,
        'name' => 'Active Membership Package',
        'total_sessions' => $sessions,
        'remaining_sessions' => $sessions,
        'assigned_date' => now(),
        'expiry_date' => now()->addDays($days),
        'status' => 'active'
    ]);
    
    echo "✅ Created user package successfully!\n";
    echo "   Package ID: {$userPackage->id}\n";
    echo "   Sessions: {$userPackage->remaining_sessions}/{$userPackage->total_sessions}\n";
    echo "   Expires: {$userPackage->expiry_date}\n";
    echo "   Status: {$userPackage->status}\n";
    
    // Verify booking capability
    $hasAvailableSessions = App\Models\UserPackage::where('user_id', $user->id)
        ->where('status', 'active')
        ->where('remaining_sessions', '>', 0)
        ->exists();
    
    echo "\n🎯 BOOKING STATUS:\n";
    echo "User can make bookings: " . ($hasAvailableSessions ? "YES ✅" : "NO ❌") . "\n";
    
    if ($hasAvailableSessions) {
        echo "✨ User is ready to make bookings!\n";
    } else {
        echo "❌ Something went wrong - user still cannot make bookings\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Package creation completed!\n"; 