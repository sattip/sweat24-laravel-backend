<?php
/**
 * Test script for Chat API functionality
 * Run with: php test_chat_api.php
 */

echo "==============================\n";
echo "Chat API Test Suite\n";
echo "==============================\n\n";

// Configuration - Load from environment variables
$baseUrl = getenv('API_BASE_URL') ?: 'http://localhost/api/v1';
$adminToken = getenv('ADMIN_TOKEN');

if (!$adminToken) {
    echo "❌ Error: ADMIN_TOKEN environment variable not set\n";
    echo "Usage: ADMIN_TOKEN=your_token php test_chat_api.php\n";
    exit(1);
}

// Test 1: Get existing conversations
echo "Test 1: Getting existing conversations\n";
echo "--------------------------------------\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/chat/conversations?status=active");
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
    echo "✅ Success! Found " . count($data) . " conversations\n";
    
    // Check admin_unread_count calculation
    foreach ($data as $conv) {
        echo "  - Conversation #{$conv['id']}: admin_unread_count = {$conv['admin_unread_count']}\n";
    }
} else {
    echo "❌ Failed: $response\n";
}
echo "\n";

// Test 2: Create new conversation
echo "Test 2: Creating new conversation from admin\n";
echo "--------------------------------------------\n";
$testUserId = getenv('TEST_USER_ID') ?: 2; // Can be overridden via environment variable
$testMessage = "Hello! This is a test message from admin at " . date('Y-m-d H:i:s');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/chat/conversations");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'user_id' => $testUserId,
    'initial_message' => $testMessage
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $adminToken",
    "Content-Type: application/json",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($httpCode == 201) {
    $data = json_decode($response, true);
    echo "✅ Success! Created conversation #{$data['conversation']['id']}\n";
    echo "  - User: {$data['conversation']['user']['name']}\n";
    echo "  - Message count: " . count($data['conversation']['messages']) . "\n";
    echo "  - Admin unread: {$data['conversation']['admin_unread_count']}\n";
    echo "  - User unread: {$data['conversation']['user_unread_count']}\n";
    $createdConvId = $data['conversation']['id'];
} else {
    echo "❌ Failed: $response\n";
    $createdConvId = null;
}
echo "\n";

// Test 3: Mark messages as read
if ($createdConvId) {
    echo "Test 3: Marking messages as read\n";
    echo "---------------------------------\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/chat/conversations/$createdConvId/read");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
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
        echo "✅ Success! {$data['message']}\n";
        echo "  - Updated count: {$data['updated_count']}\n";
    } else {
        echo "❌ Failed: $response\n";
    }
    echo "\n";
}

// Test 4: Send message to existing conversation
if ($createdConvId) {
    echo "Test 4: Sending message to conversation\n";
    echo "---------------------------------------\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/admin/chat/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'conversation_id' => $createdConvId,
        'content' => 'This is a follow-up message'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $adminToken",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode\n";
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        echo "✅ Success! Sent message #{$data['message']['id']}\n";
    } else {
        echo "❌ Failed: $response\n";
    }
    echo "\n";
}

echo "==============================\n";
echo "Test Suite Complete!\n";
echo "==============================\n";