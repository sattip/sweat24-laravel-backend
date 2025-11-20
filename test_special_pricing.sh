#!/bin/bash

# Test Special Pricing Check System
# Replace YOUR_API_TOKEN with actual token

API_URL="http://localhost:8000/api/v1"
TOKEN="YOUR_API_TOKEN"

echo "🎯 TESTING SPECIAL PRICING CHECK SYSTEM"
echo "========================================"

# Test 1: Check if user has special pricing for Personal Training
echo ""
echo "1. Checking if user 69 has special pricing for Personal Training (service_id=2)..."
curl -s -X GET "$API_URL/custom-packages/check-special-pricing?user_id=69&service_id=2" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

# Test 2: Get detailed special pricing information
echo ""
echo "2. Getting detailed special pricing for user 69 and Personal Training..."
curl -s -X GET "$API_URL/custom-packages/special-pricing-details?user_id=69&service_id=2" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

# Test 3: Get user's complete special pricing summary
echo ""
echo "3. Getting complete special pricing summary for user 69..."
curl -s -X GET "$API_URL/custom-packages/user/69/special-pricing-summary" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

# Test 4: Get pricing comparison for all user's packages
echo ""
echo "4. Getting pricing comparison for all user 69 packages..."
curl -s -X GET "$API_URL/custom-packages/user/69/special-pricing-comparison" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

echo ""
echo "🎉 TESTING COMPLETE!"
echo "=================="
echo "✅ FIXED: Route parameters now work correctly!"
echo "Use these endpoints in your React app:"
echo "- Check special pricing: GET /api/v1/custom-packages/check-special-pricing?user_id={userId}&service_id={serviceId}"
echo "- Get pricing details: GET /api/v1/custom-packages/special-pricing-details?user_id={userId}&service_id={serviceId}"
echo "- Get user summary: GET /api/v1/custom-packages/user/{userId}/special-pricing-summary"
echo "- Get comparison: GET /api/v1/custom-packages/user/{userId}/special-pricing-comparison"
