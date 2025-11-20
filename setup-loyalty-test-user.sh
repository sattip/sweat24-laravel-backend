#!/bin/bash

# Setup Loyalty Test User Script
# This script adds test points to a user for testing the loyalty system

echo "🎯 Loyalty System - Setup Test User"
echo "===================================="
echo ""

# Get user input
read -p "Enter User ID (or email to search): " USER_INPUT

# Database path
DB_PATH="/home/forge/api.sweat93.gr/database/database.sqlite"

# Check if input is an email
if [[ "$USER_INPUT" == *"@"* ]]; then
    echo "Searching for user with email: $USER_INPUT"
    USER_ID=$(sqlite3 "$DB_PATH" "SELECT id FROM users WHERE email='$USER_INPUT' LIMIT 1;")

    if [ -z "$USER_ID" ]; then
        echo "❌ User not found with email: $USER_INPUT"
        exit 1
    fi

    echo "✅ Found user ID: $USER_ID"
else
    USER_ID="$USER_INPUT"
fi

# Get user details
USER_INFO=$(sqlite3 "$DB_PATH" "SELECT id, name, email FROM users WHERE id=$USER_ID;")

if [ -z "$USER_INFO" ]; then
    echo "❌ User not found with ID: $USER_ID"
    exit 1
fi

echo ""
echo "User Details:"
echo "-------------"
IFS='|' read -r ID NAME EMAIL <<< "$USER_INFO"
echo "ID: $ID"
echo "Name: $NAME"
echo "Email: $EMAIL"
echo ""

# Check current points
CURRENT_POINTS=$(sqlite3 "$DB_PATH" "SELECT COALESCE(SUM(amount), 0) FROM loyalty_points WHERE user_id=$USER_ID AND type='earned';")
echo "Current Points Balance: $CURRENT_POINTS"
echo ""

# Ask how many points to add
read -p "How many points to add? (default: 100): " POINTS_TO_ADD
POINTS_TO_ADD=${POINTS_TO_ADD:-100}

# Calculate new balance
NEW_BALANCE=$((CURRENT_POINTS + POINTS_TO_ADD))

# Add points
echo ""
echo "Adding $POINTS_TO_ADD points to user $USER_ID..."

sqlite3 "$DB_PATH" <<EOF
INSERT INTO loyalty_points
(user_id, amount, type, source, description, balance_after, expires_at, created_at, updated_at)
VALUES
($USER_ID, $POINTS_TO_ADD, 'earned', 'manual', 'Test points for loyalty system testing', $NEW_BALANCE, datetime('now', '+1 year'), datetime('now'), datetime('now'));
EOF

if [ $? -eq 0 ]; then
    echo "✅ Successfully added $POINTS_TO_ADD points!"
    echo ""
    echo "New Balance: $NEW_BALANCE points"
    echo "Expires: $(date -d '+1 year' '+%Y-%m-%d')"
else
    echo "❌ Failed to add points"
    exit 1
fi

# Check if fitness_classes have prices
echo ""
echo "Checking fitness classes prices..."
CLASSES_WITHOUT_PRICE=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM fitness_classes WHERE price IS NULL OR price = 0;")

if [ "$CLASSES_WITHOUT_PRICE" -gt 0 ]; then
    echo "⚠️  Warning: $CLASSES_WITHOUT_PRICE fitness classes don't have a price set"
    read -p "Do you want to set default price (15 EUR) for all classes? (y/n): " SET_PRICES

    if [ "$SET_PRICES" == "y" ] || [ "$SET_PRICES" == "Y" ]; then
        sqlite3 "$DB_PATH" "UPDATE fitness_classes SET price = 15.00 WHERE price IS NULL OR price = 0;"
        echo "✅ Updated fitness classes with default price"
    fi
else
    echo "✅ All fitness classes have prices set"
fi

echo ""
echo "🎉 Setup Complete!"
echo ""
echo "Next Steps:"
echo "1. Login to the admin panel as this user"
echo "2. Go to User Profile to see the Points Balance Widget"
echo "3. Go to Calendar and try 'Book with Points'"
echo ""
echo "To check points balance in DB:"
echo "sqlite3 $DB_PATH \"SELECT SUM(amount) FROM loyalty_points WHERE user_id=$USER_ID AND type='earned';\""
echo ""
echo "To see transaction history:"
echo "sqlite3 $DB_PATH \"SELECT * FROM loyalty_transactions WHERE user_id=$USER_ID ORDER BY created_at DESC LIMIT 5;\""
echo ""
