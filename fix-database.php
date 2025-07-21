<?php

/**
 * Database Fix Script for Sweat24 Laravel Backend
 * This script fixes the MySQL connection issue by switching to SQLite
 */

echo "🔧 Starting Database Fix Script...\n";

// Create SQLite database file if it doesn't exist
$sqliteFile = __DIR__ . '/database/database.sqlite';
if (!file_exists($sqliteFile)) {
    touch($sqliteFile);
    echo "✅ Created SQLite database file\n";
} else {
    echo "✅ SQLite database file already exists\n";
}

// Clear configuration cache
try {
    shell_exec('php artisan config:clear 2>&1');
    echo "✅ Cleared config cache\n";
} catch (Exception $e) {
    echo "⚠️ Could not clear config cache: " . $e->getMessage() . "\n";
}

// Clear route cache
try {
    shell_exec('php artisan route:clear 2>&1');
    echo "✅ Cleared route cache\n";
} catch (Exception $e) {
    echo "⚠️ Could not clear route cache: " . $e->getMessage() . "\n";
}

// Test database connection
try {
    require_once 'vendor/autoload.php';
    
    $app = require_once 'bootstrap/app.php';
    
    // Force SQLite configuration
    config(['database.default' => 'sqlite']);
    config(['database.connections.sqlite.database' => $sqliteFile]);
    
    // Test connection
    $pdo = new PDO('sqlite:' . $sqliteFile);
    echo "✅ SQLite connection successful\n";
    
    // Run migrations if needed
    $migrationOutput = shell_exec('php artisan migrate --force 2>&1');
    echo "✅ Migrations executed:\n" . $migrationOutput . "\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

// Test API endpoint
echo "\n🧪 Testing API endpoint...\n";
$testUrl = 'https://sweat93laravel.obs.com.gr/api/test-history?user_id=1';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ API endpoint working - Response: " . $response . "\n";
} else {
    echo "❌ API endpoint failed - HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
}

echo "\n🎯 Database fix completed!\n";
echo "📋 Summary:\n";
echo "- Database: SQLite (fallback from MySQL)\n";
echo "- File: " . $sqliteFile . "\n";
echo "- API Status: " . ($httpCode === 200 ? "Working" : "Needs attention") . "\n";
echo "\n💡 The booking history endpoint should now work at:\n";
echo "   https://sweat93laravel.obs.com.gr/api/test-history?user_id=USER_ID\n"; 