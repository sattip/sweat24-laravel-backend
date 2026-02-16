<?php
// Test script for muscle groups feature

// Test data
$baseUrl = 'http://localhost';
$authToken = 'YOUR_AUTH_TOKEN'; // Replace with actual token
$bookingId = 1; // Replace with actual booking ID
$userId = 1; // Replace with actual user ID

echo "=== Testing Muscle Groups Feature ===\n\n";

// 1. Test storing muscle groups
echo "1. Testing POST /api/v1/workouts/{bookingId}/muscle-groups\n";
echo "   Endpoint: POST {$baseUrl}/api/v1/workouts/{$bookingId}/muscle-groups\n";
echo "   Headers: Authorization: Bearer {$authToken}\n";
echo "   Body: {\"muscle_groups\": [\"legs\", \"core\"]}\n\n";

// 2. Test retrieving muscle groups
echo "2. Testing GET /api/v1/workouts/{bookingId}/muscle-groups\n";
echo "   Endpoint: GET {$baseUrl}/api/v1/workouts/{$bookingId}/muscle-groups\n";
echo "   Headers: Authorization: Bearer {$authToken}\n\n";

// 3. Test workout history with muscle groups
echo "3. Testing GET /api/test-history?user_id={userId}\n";
echo "   Endpoint: GET {$baseUrl}/api/test-history?user_id={$userId}\n";
echo "   Expected: Each workout should include 'muscle_groups' and 'muscle_groups_recorded' fields\n\n";

// Example curl commands
echo "=== Example CURL Commands ===\n\n";

echo "# Store muscle groups:\n";
echo "curl -X POST {$baseUrl}/api/v1/workouts/{$bookingId}/muscle-groups \\\n";
echo "  -H \"Authorization: Bearer {$authToken}\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"muscle_groups\": [\"legs\", \"core\"]}'\n\n";

echo "# Get muscle groups:\n";
echo "curl -X GET {$baseUrl}/api/v1/workouts/{$bookingId}/muscle-groups \\\n";
echo "  -H \"Authorization: Bearer {$authToken}\"\n\n";

echo "# Get workout history:\n";
echo "curl -X GET \"{$baseUrl}/api/test-history?user_id={$userId}\"\n\n";

echo "=== Validation Rules ===\n";
echo "• muscle_groups is required and must be an array\n";
echo "• Valid values: total_body, legs, chest, back, shoulders, arms, core, cardio\n";
echo "• Booking must belong to authenticated user\n";
echo "• User must have attended the workout (attended = 1)\n";
echo "• Each booking can have only one muscle groups record (will update if exists)\n\n";

echo "=== Database Check ===\n";
echo "To verify in database:\n";
echo "SELECT * FROM workout_muscle_groups WHERE booking_id = {$bookingId};\n";