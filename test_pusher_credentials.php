<?php
/**
 * Test script για να ελέγξουμε αν τα Pusher credentials είναι valid
 * Run: php test_pusher_credentials.php
 */

require_once 'vendor/autoload.php';

echo "=====================================\n";
echo "   PUSHER CREDENTIALS TEST\n";
echo "=====================================\n\n";

// Load credentials από το .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$appId = $_ENV['PUSHER_APP_ID'] ?? '';
$key = $_ENV['PUSHER_APP_KEY'] ?? '';
$secret = $_ENV['PUSHER_APP_SECRET'] ?? '';
$cluster = $_ENV['PUSHER_APP_CLUSTER'] ?? '';

echo "📋 Credentials από .env:\n";
echo "   APP_ID: $appId\n";
echo "   APP_KEY: $key\n";
echo "   APP_SECRET: " . substr($secret, 0, 5) . "***\n";
echo "   CLUSTER: $cluster\n\n";

echo "🔍 Testing connection...\n";

try {
    $pusher = new Pusher\Pusher(
        $key,
        $secret,
        $appId,
        [
            'cluster' => $cluster,
            'useTLS' => true
        ]
    );
    
    // Προσπάθησε να στείλεις ένα test event
    $result = $pusher->trigger('test-channel', 'test-event', [
        'message' => 'Test from Laravel backend',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    if ($result) {
        echo "✅ SUCCESS! Τα credentials είναι έγκυρα!\n";
        echo "   - Το Pusher δέχτηκε το test event\n";
        echo "   - Το broadcasting θα δουλέψει μια χαρά!\n\n";
        
        echo "📊 Pusher Account Info:\n";
        echo "   - Dashboard: https://dashboard.pusher.com/apps/$appId\n";
        echo "   - Debug Console: https://dashboard.pusher.com/apps/$appId/console\n";
    } else {
        echo "❌ FAILED! Κάτι πήγε στραβά\n";
        echo "   - Τσέκαρε τα credentials\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR! " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'auth') !== false) {
        echo "🔐 Φαίνεται ότι τα credentials είναι λάθος!\n";
        echo "   Πρέπει να:\n";
        echo "   1. Πας στο https://dashboard.pusher.com\n";
        echo "   2. Κάνεις login (ή signup αν δεν έχεις account)\n";
        echo "   3. Δημιουργήσεις νέο Channels app\n";
        echo "   4. Αντιγράψεις τα νέα credentials στο .env\n";
    }
}

echo "\n=====================================\n";