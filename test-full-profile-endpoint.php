<?php

/**
 * Test script for the full user profile endpoint
 * 
 * Usage: php test-full-profile-endpoint.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$baseUrl = 'http://localhost:8000';
$adminEmail = 'admin@sweat24.obs.com.gr';
$adminPassword = 'password123';

echo "==========================================\n";
echo "Testing Full User Profile Endpoint\n";
echo "==========================================\n\n";

// Step 1: Login as admin
echo "Step 1: Logging in as admin...\n";
$loginResponse = Http::post("$baseUrl/api/v1/auth/login", [
    'email' => $adminEmail,
    'password' => $adminPassword,
]);

if (!$loginResponse->successful()) {
    die("Failed to login: " . $loginResponse->body() . "\n");
}

$token = $loginResponse->json()['token'] ?? null;
if (!$token) {
    die("No token received\n");
}

echo "✓ Logged in successfully\n\n";

// Step 2: Get a user with is_minor = true to test guardian details
echo "Step 2: Finding a minor user...\n";
$usersResponse = Http::withToken($token)->get("$baseUrl/api/v1/users?is_minor=1");
$minorUser = null;

if ($usersResponse->successful() && count($usersResponse->json()['data'] ?? []) > 0) {
    $minorUser = $usersResponse->json()['data'][0];
    echo "✓ Found minor user: ID {$minorUser['id']} - {$minorUser['name']}\n\n";
}

// Step 3: Get a user with EMS interest to test medical history
echo "Step 3: Finding a user with EMS interest...\n";
$emsUser = null;
$allUsersResponse = Http::withToken($token)->get("$baseUrl/api/v1/users");

if ($allUsersResponse->successful()) {
    foreach ($allUsersResponse->json()['data'] ?? [] as $user) {
        if ($user['ems_interest'] ?? false) {
            $emsUser = $user;
            echo "✓ Found EMS interested user: ID {$emsUser['id']} - {$emsUser['name']}\n\n";
            break;
        }
    }
}

// Step 4: Test the full profile endpoint with different users
$testUsers = [];

if ($minorUser) {
    $testUsers[] = ['id' => $minorUser['id'], 'type' => 'Minor User'];
}

if ($emsUser) {
    $testUsers[] = ['id' => $emsUser['id'], 'type' => 'EMS User'];
}

// Also test with first available user
if (count($allUsersResponse->json()['data'] ?? []) > 0) {
    $testUsers[] = ['id' => $allUsersResponse->json()['data'][0]['id'], 'type' => 'Regular User'];
}

foreach ($testUsers as $testUser) {
    echo "==========================================\n";
    echo "Testing with {$testUser['type']} (ID: {$testUser['id']})\n";
    echo "==========================================\n";
    
    $profileResponse = Http::withToken($token)->get("$baseUrl/api/admin/users/{$testUser['id']}/full-profile");
    
    if ($profileResponse->successful()) {
        $profile = $profileResponse->json()['data'] ?? $profileResponse->json();
        
        echo "✓ Full profile retrieved successfully\n\n";
        echo "Profile Structure:\n";
        echo "-----------------\n";
        
        // Basic info
        echo "Basic Info:\n";
        echo "  - ID: " . ($profile['id'] ?? 'N/A') . "\n";
        echo "  - Name: " . ($profile['full_name'] ?? 'N/A') . "\n";
        echo "  - Email: " . ($profile['email'] ?? 'N/A') . "\n";
        echo "  - Is Minor: " . (($profile['is_minor'] ?? false) ? 'Yes' : 'No') . "\n";
        echo "  - Registration Date: " . ($profile['registration_date'] ?? 'N/A') . "\n";
        echo "  - Signature URL: " . ($profile['signature_url'] ?? 'None') . "\n\n";
        
        // Guardian details (if minor)
        if (!empty($profile['guardian_details'])) {
            echo "Guardian Details:\n";
            $guardian = $profile['guardian_details'];
            echo "  - Full Name: " . ($guardian['full_name'] ?? 'N/A') . "\n";
            echo "  - Father: " . ($guardian['father_name'] ?? 'N/A') . "\n";
            echo "  - Mother: " . ($guardian['mother_name'] ?? 'N/A') . "\n";
            echo "  - Phone: " . ($guardian['phone'] ?? 'N/A') . "\n";
            echo "  - Address: " . ($guardian['address'] ?? 'N/A') . ", " . ($guardian['city'] ?? '') . " " . ($guardian['zip_code'] ?? '') . "\n";
            echo "  - Signature URL: " . ($guardian['signature_url'] ?? 'None') . "\n\n";
        }
        
        // Medical history (if EMS interested)
        if (!empty($profile['medical_history'])) {
            echo "Medical History (EMS):\n";
            $medical = $profile['medical_history'];
            echo "  - Has EMS Interest: " . (($medical['has_ems_interest'] ?? false) ? 'Yes' : 'No') . "\n";
            echo "  - Liability Accepted: " . (($medical['ems_liability_accepted'] ?? false) ? 'Yes' : 'No') . "\n";
            
            if (!empty($medical['ems_contraindications'])) {
                echo "  - Contraindications:\n";
                foreach ($medical['ems_contraindications'] as $condition => $data) {
                    if (is_array($data) && ($data['has_condition'] ?? false)) {
                        echo "    • $condition" . (isset($data['year_of_onset']) ? " (Since: {$data['year_of_onset']})" : "") . "\n";
                    }
                }
            }
            echo "\n";
        }
        
        // Referral info
        if (!empty($profile['found_us_via'])) {
            echo "How Found Us:\n";
            $foundUs = $profile['found_us_via'];
            echo "  - Source: " . ($foundUs['source'] ?? 'N/A') . "\n";
            
            if (!empty($foundUs['referrer_info'])) {
                $referrer = $foundUs['referrer_info'];
                echo "  - Referrer: " . ($referrer['referrer_name'] ?? 'N/A') . " (ID: " . ($referrer['referrer_id'] ?? 'N/A') . ")\n";
                echo "  - Code/Name Used: " . ($referrer['code_or_name_used'] ?? 'N/A') . "\n";
            }
            
            if (!empty($foundUs['sub_source'])) {
                echo "  - Sub-source: " . $foundUs['sub_source'] . "\n";
            }
            echo "\n";
        }
        
    } else {
        echo "✗ Failed to retrieve profile: " . $profileResponse->body() . "\n\n";
    }
}

echo "==========================================\n";
echo "Test Complete!\n";
echo "==========================================\n\n";

echo "API Endpoint Documentation:\n";
echo "---------------------------\n";
echo "Endpoint: GET /api/admin/users/{userId}/full-profile\n";
echo "Authentication: Required (Bearer token)\n";
echo "Permissions: Admin role required\n\n";

echo "Response Structure:\n";
echo "```json\n";
echo json_encode([
    "id" => 45,
    "full_name" => "User Name",
    "email" => "user@example.com",
    "is_minor" => true,
    "registration_date" => "2025-08-07",
    "signature_url" => "https://storage.url/signatures/user_45.png",
    "guardian_details" => [
        "full_name" => "Guardian Name",
        "father_name" => "Father Name",
        "mother_name" => "Mother Name",
        "birth_date" => "1980-05-15",
        "id_number" => "ID123456",
        "phone" => "+30123456789",
        "address" => "Street 15",
        "city" => "Athens",
        "zip_code" => "11145",
        "email" => "guardian@example.com",
        "consent_date" => "2025-08-05T12:00:00.000Z",
        "signature_url" => "https://storage.url/signatures/user_45_guardian.jpg"
    ],
    "medical_history" => [
        "has_ems_interest" => true,
        "ems_contraindications" => [
            "Condition Name" => [
                "has_condition" => true,
                "year_of_onset" => "2021"
            ]
        ],
        "ems_liability_accepted" => true,
        "other_medical_data" => []
    ],
    "found_us_via" => [
        "source" => "Referral",
        "referrer_info" => [
            "referrer_id" => 456,
            "referrer_name" => "Referrer Name",
            "code_or_name_used" => "REF456"
        ],
        "sub_source" => null
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n```\n";