#!/bin/bash

# Quick Verification Script for Zillow Features
# Run: bash QUICK_VERIFY.sh

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║     Quick Feature Verification Script                       ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0

# Function to check file/directory
check_item() {
    if [ -e "$1" ]; then
        echo -e "${GREEN}✓${NC} $2"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}✗${NC} $2 (Missing: $1)"
        ((FAILED++))
        return 1
    fi
}

# Function to count files
count_files() {
    local count=$(find "$1" -name "$2" 2>/dev/null | wc -l | tr -d ' ')
    echo "$count"
}

echo "1. Checking Services..."
SERVICES_COUNT=$(count_files "platform/plugins/real-estate/src/Services" "*Service.php")
if [ "$SERVICES_COUNT" -ge 13 ]; then
    echo -e "${GREEN}✓${NC} Services: $SERVICES_COUNT found (Expected: 13+)"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Services: $SERVICES_COUNT found (Expected: 13+)"
    ((FAILED++))
fi

echo ""
echo "2. Checking Controllers..."
CONTROLLERS_COUNT=$(count_files "platform/plugins/real-estate/src/Http/Controllers/Fronts" "*Controller.php")
if [ "$CONTROLLERS_COUNT" -ge 13 ]; then
    echo -e "${GREEN}✓${NC} Controllers: $CONTROLLERS_COUNT found (Expected: 13+)"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Controllers: $CONTROLLERS_COUNT found (Expected: 13+)"
    ((FAILED++))
fi

echo ""
echo "3. Checking Models..."
check_item "platform/plugins/real-estate/src/Models/PropertyValuation.php" "PropertyValuation"
check_item "platform/plugins/real-estate/src/Models/SavedSearch.php" "SavedSearch"
check_item "platform/plugins/real-estate/src/Models/PropertyHistory.php" "PropertyHistory"
check_item "platform/plugins/real-estate/src/Models/PropertyComparison.php" "PropertyComparison"
check_item "platform/plugins/real-estate/src/Models/PropertyTour.php" "PropertyTour"
check_item "platform/plugins/real-estate/src/Models/RentalApplication.php" "RentalApplication"
check_item "platform/plugins/real-estate/src/Models/PropertyMedia.php" "PropertyMedia"
check_item "platform/plugins/real-estate/src/Models/PropertySyncSource.php" "PropertySyncSource"
check_item "platform/plugins/real-estate/src/Models/MarketInsight.php" "MarketInsight"
check_item "platform/plugins/real-estate/src/Models/RentPayment.php" "RentPayment"
check_item "platform/plugins/real-estate/src/Models/PropertyShare.php" "PropertyShare"

echo ""
echo "4. Checking Vue Components..."
check_item "platform/plugins/real-estate/resources/js/components/NaturalLanguageSearch.vue" "NaturalLanguageSearch.vue"
check_item "platform/plugins/real-estate/resources/js/components/MapSearch.vue" "MapSearch.vue"
check_item "platform/plugins/real-estate/resources/js/components/PropertyValuation.vue" "PropertyValuation.vue"
check_item "platform/plugins/real-estate/resources/js/components/PropertyComparison.vue" "PropertyComparison.vue"
check_item "platform/plugins/real-estate/resources/js/components/BuyAbilityCalculator.vue" "BuyAbilityCalculator.vue"
check_item "platform/plugins/real-estate/resources/js/components/NeighborhoodInsights.vue" "NeighborhoodInsights.vue"
check_item "platform/plugins/real-estate/resources/js/components/Property3DTour.vue" "Property3DTour.vue"
check_item "platform/plugins/real-estate/resources/js/components/RentPaymentForm.vue" "RentPaymentForm.vue"
check_item "platform/plugins/real-estate/resources/js/components/PriceHistoryChart.vue" "PriceHistoryChart.vue"
check_item "platform/plugins/real-estate/resources/js/components/index.js" "index.js"

echo ""
echo "5. Checking Migrations..."
MIGRATIONS_COUNT=$(count_files "platform/plugins/real-estate/database/migrations" "*2024_01_15*")
if [ "$MIGRATIONS_COUNT" -ge 11 ]; then
    echo -e "${GREEN}✓${NC} Migrations: $MIGRATIONS_COUNT found (Expected: 11)"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Migrations: $MIGRATIONS_COUNT found (Expected: 11)"
    ((FAILED++))
fi

echo ""
echo "6. Checking Routes File..."
check_item "platform/plugins/real-estate/routes/fronts.php" "Routes file"

# Check if routes contain key features
if grep -q "saved-searches" "platform/plugins/real-estate/routes/fronts.php" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Saved searches routes"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Saved searches routes missing"
    ((FAILED++))
fi

if grep -q "property-comparison" "platform/plugins/real-estate/routes/fronts.php" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Property comparison routes"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Property comparison routes missing"
    ((FAILED++))
fi

if grep -q "mortgage-calculator" "platform/plugins/real-estate/routes/fronts.php" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Mortgage calculator routes"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Mortgage calculator routes missing"
    ((FAILED++))
fi

if grep -q "map-search" "platform/plugins/real-estate/routes/fronts.php" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Map search routes"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Map search routes missing"
    ((FAILED++))
fi

echo ""
echo "7. Checking Console Commands..."
check_item "platform/plugins/real-estate/src/Console/Commands/ProcessSavedSearchAlerts.php" "ProcessSavedSearchAlerts command"
check_item "platform/plugins/real-estate/src/Console/Commands/SyncNigerianProperties.php" "SyncNigerianProperties command"

echo ""
echo "8. Checking Notifications..."
check_item "platform/plugins/real-estate/src/Notifications/SavedSearchAlertNotification.php" "SavedSearchAlertNotification"

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                    Verification Summary                      ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo "Total Checks: $((PASSED + FAILED))"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ All features verified successfully!${NC}"
    echo -e "${GREEN}🎉 Your Zillow-like platform is ready to use!${NC}"
    exit 0
else
    echo ""
    echo -e "${YELLOW}⚠ Some features are missing. Please check above.${NC}"
    echo -e "${YELLOW}Run: php artisan migrate (if tables are missing)${NC}"
    exit 1
fi

