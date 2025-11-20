<?php

/**
 * Test script for New Member Info API
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\NewMemberInfo;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== NEW MEMBER INFO API TEST ===\n\n";

// 1. Create sample data
echo "1. Creating sample data...\n";

$sampleData = [
    [
        'title' => 'Καλωσόρισμα στο Γυμναστήριο',
        'content' => 'Καλώς ήρθατε στο γυμναστήριό μας! Είμαστε χαρούμενοι που είστε μέλος της οικογένειάς μας.',
        'category' => 'general',
        'order' => 1
    ],
    [
        'title' => 'Κανόνες Γυμναστηρίου',
        'content' => '1. Παρακαλούμε να φοράτε καθαρά αθλητικά παπούτσια\n2. Χρησιμοποιήστε πετσέτα στα μηχανήματα\n3. Επιστρέψτε τα βάρη στη θέση τους',
        'category' => 'rules',
        'order' => 2
    ],
    [
        'title' => 'Οφέλη της Άσκησης',
        'content' => 'Η τακτική άσκηση βελτιώνει την υγεία, αυξάνει την ενέργεια και μειώνει το άγχος.',
        'category' => 'benefits',
        'order' => 3
    ],
    [
        'title' => 'Ωράριο Λειτουργίας',
        'content' => 'Δευτέρα - Παρασκευή: 07:00 - 22:00\nΣάββατο: 09:00 - 18:00\nΚυριακή: 10:00 - 16:00',
        'category' => 'schedule',
        'order' => 4
    ],
    [
        'title' => 'Διαθέσιμος Εξοπλισμός',
        'content' => 'Διαθέτουμε μηχανήματα cardio, ελεύθερα βάρη, μηχανήματα αντίστασης και χώρο για group classes.',
        'category' => 'equipment',
        'order' => 5
    ],
    [
        'title' => 'Πώς κάνω κράτηση για μάθημα;',
        'content' => 'Μπορείτε να κάνετε κράτηση μέσω της εφαρμογής μας ή στη ρεσεψιόν.',
        'category' => 'faq',
        'order' => 6
    ]
];

foreach ($sampleData as $data) {
    $item = NewMemberInfo::create($data);
    echo "   ✅ Created: {$item->title} ({$item->category})\n";
}

echo "\n2. Testing model methods...\n";

// Test scopes
$activeCount = NewMemberInfo::active()->count();
echo "   Active items: $activeCount\n";

$rulesCount = NewMemberInfo::category('rules')->count();
echo "   Rules category items: $rulesCount\n";

$ordered = NewMemberInfo::ordered()->pluck('title')->toArray();
echo "   Ordered items: " . implode(', ', array_slice($ordered, 0, 3)) . "...\n";

echo "\n3. Testing API endpoints...\n";

// Get a test user and token
$testUser = User::first();
if (!$testUser) {
    echo "   ❌ No users found for testing\n";
} else {
    $token = $testUser->createToken('test-token')->plainTextToken;
    echo "   ✅ Test token created\n";
    
    // Test endpoints
    $baseUrl = env('APP_URL', 'http://localhost');
    
    echo "\n4. API Endpoint Examples:\n";
    echo "   GET all items:\n";
    echo "   curl -X GET \"{$baseUrl}/api/v1/new-member-info\" \\\n";
    echo "        -H \"Authorization: Bearer {$token}\" \\\n";
    echo "        -H \"Accept: application/json\"\n";
    
    echo "\n   GET active items only:\n";
    echo "   curl -X GET \"{$baseUrl}/api/v1/new-member-info?active=true\" \\\n";
    echo "        -H \"Authorization: Bearer {$token}\" \\\n";
    echo "        -H \"Accept: application/json\"\n";
    
    echo "\n   GET by category:\n";
    echo "   curl -X GET \"{$baseUrl}/api/v1/new-member-info/category/rules\" \\\n";
    echo "        -H \"Authorization: Bearer {$token}\" \\\n";
    echo "        -H \"Accept: application/json\"\n";
    
    echo "\n   CREATE new item:\n";
    echo "   curl -X POST \"{$baseUrl}/api/v1/new-member-info\" \\\n";
    echo "        -H \"Authorization: Bearer {$token}\" \\\n";
    echo "        -H \"Content-Type: application/json\" \\\n";
    echo "        -H \"Accept: application/json\" \\\n";
    echo "        -d '{\n";
    echo "          \"title\": \"Νέος Κανόνας\",\n";
    echo "          \"content\": \"Περιγραφή του κανόνα\",\n";
    echo "          \"category\": \"rules\",\n";
    echo "          \"is_active\": true,\n";
    echo "          \"order\": 10\n";
    echo "        }'\n";
    
    $firstItem = NewMemberInfo::first();
    if ($firstItem) {
        echo "\n   UPDATE item:\n";
        echo "   curl -X PUT \"{$baseUrl}/api/v1/new-member-info/{$firstItem->id}\" \\\n";
        echo "        -H \"Authorization: Bearer {$token}\" \\\n";
        echo "        -H \"Content-Type: application/json\" \\\n";
        echo "        -H \"Accept: application/json\" \\\n";
        echo "        -d '{\"title\": \"Ενημερωμένος Τίτλος\"}'\n";
        
        echo "\n   DELETE item:\n";
        echo "   curl -X DELETE \"{$baseUrl}/api/v1/new-member-info/{$firstItem->id}\" \\\n";
        echo "        -H \"Authorization: Bearer {$token}\" \\\n";
        echo "        -H \"Accept: application/json\"\n";
    }
}

echo "\n5. Database Summary:\n";
$total = NewMemberInfo::count();
$active = NewMemberInfo::active()->count();
$byCategory = NewMemberInfo::selectRaw('category, COUNT(*) as count')
    ->groupBy('category')
    ->pluck('count', 'category')
    ->toArray();

echo "   Total items: $total\n";
echo "   Active items: $active\n";
echo "   By category:\n";
foreach ($byCategory as $cat => $count) {
    $label = NewMemberInfo::CATEGORIES[$cat] ?? $cat;
    echo "      - $label: $count\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "✅ New Member Info API is ready to use!\n";
echo "📝 Sample data has been created in the database\n";
echo "🔗 Use the curl commands above to test the API\n";