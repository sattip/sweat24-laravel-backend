<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Package;
use App\Models\UserPackage;

// Find the user we just created
$user = User::where('email', 'user@sweat24.gr')->first();

if (!$user) {
    echo "User not found!\n";
    exit(1);
}

// Find the Basic Membership package
$package = Package::where('name', 'Basic Membership 1 μήνας')->first();

if (!$package) {
    echo "Package not found!\n";
    exit(1);
}

// Create the user package subscription
$userPackage = UserPackage::create([
    'user_id' => $user->id,
    'package_id' => $package->id,
    'name' => $package->name,
    'assigned_date' => now(),
    'expiry_date' => now()->addDays($package->duration),
    'remaining_sessions' => $package->sessions ?: 999, // For unlimited packages, use a high number
    'total_sessions' => $package->sessions ?: 999,
    'status' => 'active',
    'auto_renew' => false,
]);

echo "Package assigned successfully!\n";
echo "User: " . $user->name . " (" . $user->email . ")\n";
echo "Package: " . $package->name . "\n";
echo "Price: €" . $package->price . "\n";
echo "Duration: " . $package->duration . " days\n";
echo "Sessions: " . ($package->sessions ?: 'Unlimited') . "\n";
echo "Expiry Date: " . $userPackage->expiry_date->format('Y-m-d') . "\n";
echo "Status: " . $userPackage->status . "\n";
echo "User Package ID: " . $userPackage->id . "\n"; 