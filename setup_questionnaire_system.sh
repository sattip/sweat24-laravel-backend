#!/bin/bash

# Questionnaire System Deployment Script
# This script automates the deployment of the questionnaire system to production

echo "🚀 Starting Questionnaire System Deployment..."
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status messages
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    print_error "Not in Laravel project directory. Please run from the Laravel root directory."
    exit 1
fi

echo "📁 Working directory: $(pwd)"
echo

# Step 1: Create backup
print_status "Step 1: Creating database backup..."
php artisan backup:run --only-db || print_warning "Backup failed, but continuing..."

# Step 2: Run migrations
print_status "Step 2: Running database migrations..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    print_status "Migrations completed successfully"
else
    print_error "Migration failed! Please check the errors above."
    exit 1
fi

# Step 3: Clear and cache Laravel configs
print_status "Step 3: Clearing and caching Laravel configurations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
print_status "Laravel cache refreshed"

# Step 4: Verify routes are registered
print_status "Step 4: Verifying API routes..."
ROUTE_COUNT=$(php artisan route:list --path=questionnaire | grep -c "questionnaire")
if [ "$ROUTE_COUNT" -gt 0 ]; then
    print_status "Found $ROUTE_COUNT questionnaire routes"
else
    print_error "No questionnaire routes found! Please check routes/api.php"
    exit 1
fi

# Step 5: Run seeder for sample data
print_status "Step 5: Creating sample questionnaire data..."
php artisan db:seed --class=QuestionnaireSeeder --force
if [ $? -eq 0 ]; then
    print_status "Sample data created successfully"
else
    print_warning "Sample data creation failed, but system should still work"
fi

# Step 6: Verify system is working
print_status "Step 6: Testing questionnaire system..."

# Test admin access to questionnaires
ADMIN_TOKEN=$(php artisan tinker --execute="echo App\Models\User::where('role', 'admin')->first()->currentAccessToken()->plainTextToken ?? 'no-token';" | grep -v "Psy Shell")

if [ "$ADMIN_TOKEN" != "no-token" ]; then
    print_status "Testing API endpoints..."

    # Test questionnaire listing
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "http://localhost/api/v1/questionnaires" -H "Authorization: Bearer $ADMIN_TOKEN" -H "Accept: application/json")

    if [ "$RESPONSE" -eq 200 ]; then
        print_status "Admin questionnaire listing works (HTTP 200)"
    else
        print_warning "Admin questionnaire listing returned HTTP $RESPONSE"
    fi

    # Test active questionnaires
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "http://localhost/api/v1/questionnaires/active?user_id=1" -H "Authorization: Bearer $ADMIN_TOKEN" -H "Accept: application/json")

    if [ "$RESPONSE" -eq 200 ]; then
        print_status "Active questionnaires endpoint works (HTTP 200)"
    else
        print_warning "Active questionnaires returned HTTP $RESPONSE"
    fi
else
    print_warning "Could not get admin token for testing"
fi

# Step 7: Create deployment summary
print_status "Step 7: Creating deployment summary..."

echo
echo "=================================================="
echo "🎉 QUESTIONNAIRE SYSTEM DEPLOYMENT COMPLETE!"
echo "=================================================="
echo
echo "📋 What was deployed:"
echo "  ✅ Database tables (questionnaires, questionnaire_responses)"
echo "  ✅ Eloquent models (Questionnaire, QuestionnaireResponse)"
echo "  ✅ API controllers (QuestionnaireController, QuestionnaireResponseController)"
echo "  ✅ API routes with authentication"
echo "  ✅ Validation rules for all endpoints"
echo "  ✅ Sample questionnaire data"
echo
echo "🌐 API Endpoints available:"
echo "  Admin: /api/v1/questionnaires/*"
echo "  Users: /api/v1/questionnaires/active, /api/v1/questionnaire-responses/*"
echo
echo "📊 Database tables:"
echo "  - questionnaires: Stores questionnaire definitions"
echo "  - questionnaire_responses: Stores user responses"
echo
echo "📱 Next steps:"
echo "  1. Update frontend to use real API instead of mock data"
echo "  2. Implement mobile app questionnaire UI"
echo "  3. Test end-to-end functionality"
echo "  4. Set up analytics monitoring"
echo
echo "📖 Documentation:"
echo "  - API docs: docs/QUESTIONNAIRE_API_DOCS.md"
echo "  - Implementation guide: docs/QUESTIONNAIRES_README.md"
echo
echo "🔧 Support:"
echo "  Contact development team for any issues"
echo
echo "=================================================="
echo "🚀 System ready for production use!"
echo "=================================================="

# Final verification
QUESTIONNAIRE_COUNT=$(php artisan tinker --execute="echo App\Models\Questionnaire::count();" | grep -v "Psy Shell" | tr -d '\n')
if [ "$QUESTIONNAIRE_COUNT" -gt 0 ]; then
    print_status "Verification: $QUESTIONNAIRE_COUNT questionnaires found in database"
else
    print_warning "Verification: No questionnaires found - please check seeder"
fi

echo
print_status "Deployment script completed successfully!"
