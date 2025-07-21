<?php

/**
 * Test Policy Rules Verification
 * Verifies the correct cancellation and reschedule policies
 */

echo "🔍 POLICY RULES VERIFICATION\n";
echo "============================\n\n";

// Test different time scenarios
$testScenarios = [
    ['hours' => 1, 'scenario' => '1 ώρα πριν'],
    ['hours' => 2, 'scenario' => '2 ώρες πριν'], 
    ['hours' => 3, 'scenario' => '3 ώρες πριν'],
    ['hours' => 5, 'scenario' => '5 ώρες πριν'],
    ['hours' => 6, 'scenario' => '6 ώρες πριν'],
    ['hours' => 12, 'scenario' => '12 ώρες πριν'],
    ['hours' => 24, 'scenario' => '24 ώρες πριν'],
    ['hours' => 48, 'scenario' => '48 ώρες πριν']
];

echo "📋 EXPECTED POLICY RULES:\n";
echo "-------------------------\n";
echo "✅ Ακύρωση (Cancel): έως 3+ ώρες πριν\n";
echo "✅ Αλλαγή ώρας (Reschedule): έως 6+ ώρες πριν\n";
echo "✅ Χωρίς ποινή: 24+ ώρες πριν\n\n";

echo "🧪 TESTING SCENARIOS:\n";
echo "---------------------\n";

foreach ($testScenarios as $test) {
    $hours = $test['hours'];
    $scenario = $test['scenario'];
    
    // Apply same logic as in CancellationPolicyController
    $canCancel = $hours >= 3;
    $canReschedule = $hours >= 6;
    $canCancelWithoutPenalty = true; // No penalty system
    $penaltyPercentage = 0; // No penalties
    
    echo "⏰ $scenario:\n";
    echo "   Cancel: " . ($canCancel ? "✅ ΝΑΙ" : "❌ ΟΧΙ") . "\n";
    echo "   Reschedule: " . ($canReschedule ? "✅ ΝΑΙ" : "❌ ΟΧΙ") . "\n";
    echo "   Χωρίς ποινή: " . ($canCancelWithoutPenalty ? "✅ ΝΑΙ" : "❌ ΟΧΙ") . "\n";
    echo "   Ποινή: {$penaltyPercentage}%\n";
    echo "   ---\n";
}

// Test actual API endpoint
echo "\n🌐 API ENDPOINT TEST:\n";
echo "--------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://sweat93laravel.obs.com.gr/api/v1/test-policy/1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Policy endpoint working (HTTP 200)\n";
    $data = json_decode($response, true);
    
    if ($data) {
        echo "📊 Current booking policy status:\n";
        echo "   Hours until class: {$data['hours_until_class']}\n";
        echo "   Can cancel: " . ($data['can_cancel'] ? "✅ ΝΑΙ" : "❌ ΟΧΙ") . "\n";
        echo "   Can reschedule: " . ($data['can_reschedule'] ? "✅ ΝΑΙ" : "❌ ΟΧΙ") . "\n";
        echo "   Penalty: {$data['penalty_percentage']}%\n";
        
        // Verify logic
        $hours = $data['hours_until_class'];
        $expectedCancel = $hours >= 3;
        $expectedReschedule = $hours >= 6;
        
        if ($data['can_cancel'] === $expectedCancel && $data['can_reschedule'] === $expectedReschedule) {
            echo "✅ Policy logic is CORRECT!\n";
        } else {
            echo "❌ Policy logic is WRONG!\n";
            echo "   Expected cancel: " . ($expectedCancel ? "YES" : "NO") . "\n";
            echo "   Expected reschedule: " . ($expectedReschedule ? "YES" : "NO") . "\n";
        }
    }
} else {
    echo "❌ Policy endpoint failed (HTTP $httpCode)\n";
    echo "Response: $response\n";
}

echo "\n✨ Policy verification completed!\n"; 