<?php

/**
 * Feature Verification Script
 * 
 * Run this script to verify all Zillow-like features are properly implemented
 * Usage: php verify-features.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Zillow Feature Verification Script                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$allPassed = true;
$totalChecks = 0;
$passedChecks = 0;

// Helper function
function check($name, $condition, &$allPassed, &$totalChecks, &$passedChecks) {
    $totalChecks++;
    $status = $condition ? "✓" : "✗";
    $color = $condition ? "\033[32m" : "\033[31m";
    $reset = "\033[0m";
    
    echo "  {$color}{$status}{$reset} {$name}\n";
    
    if ($condition) {
        $passedChecks++;
    } else {
        $allPassed = false;
    }
}

// 1. Database Tables
echo "1. Database Tables:\n";
$tables = [
    're_property_valuations',
    're_saved_searches',
    're_property_history',
    're_property_comparisons',
    're_property_tours',
    're_rental_applications',
    're_property_media',
    're_property_sync_sources',
    're_market_insights',
    're_rent_payments',
    're_property_shares',
];

foreach ($tables as $table) {
    check($table, Schema::hasTable($table), $allPassed, $totalChecks, $passedChecks);
}

// 2. Service Classes
echo "\n2. Service Classes:\n";
$services = [
    'Botble\RealEstate\Services\PropertyValuationService',
    'Botble\RealEstate\Services\BuyAbilityService',
    'Botble\RealEstate\Services\PropertyRecommendationService',
    'Botble\RealEstate\Services\PropertyHistoryService',
    'Botble\RealEstate\Services\NaturalLanguageSearchService',
    'Botble\RealEstate\Services\MapSearchService',
    'Botble\RealEstate\Services\NigerianPaymentService',
    'Botble\RealEstate\Services\NigerianCreditBureauService',
    'Botble\RealEstate\Services\NigerianPropertySyncService',
    'Botble\RealEstate\Services\MarketInsightsService',
    'Botble\RealEstate\Services\NeighborhoodInsightsService',
    'Botble\RealEstate\Services\Property3DTourService',
    'Botble\RealEstate\Services\PriceHistoryService',
];

foreach ($services as $service) {
    check($service, class_exists($service), $allPassed, $totalChecks, $passedChecks);
}

// 3. Models
echo "\n3. Models:\n";
$models = [
    'Botble\RealEstate\Models\PropertyValuation',
    'Botble\RealEstate\Models\SavedSearch',
    'Botble\RealEstate\Models\PropertyHistory',
    'Botble\RealEstate\Models\PropertyComparison',
    'Botble\RealEstate\Models\PropertyTour',
    'Botble\RealEstate\Models\RentalApplication',
    'Botble\RealEstate\Models\PropertyMedia',
    'Botble\RealEstate\Models\PropertySyncSource',
    'Botble\RealEstate\Models\MarketInsight',
    'Botble\RealEstate\Models\RentPayment',
    'Botble\RealEstate\Models\PropertyShare',
];

foreach ($models as $model) {
    check($model, class_exists($model), $allPassed, $totalChecks, $passedChecks);
}

// 4. Controllers
echo "\n4. Controllers:\n";
$controllers = [
    'Botble\RealEstate\Http\Controllers\Fronts\SavedSearchController',
    'Botble\RealEstate\Http\Controllers\Fronts\PropertyComparisonController',
    'Botble\RealEstate\Http\Controllers\Fronts\MortgageCalculatorController',
    'Botble\RealEstate\Http\Controllers\Fronts\BuyAbilityController',
    'Botble\RealEstate\Http\Controllers\Fronts\TourController',
    'Botble\RealEstate\Http\Controllers\Fronts\RentalApplicationController',
    'Botble\RealEstate\Http\Controllers\Fronts\NaturalLanguageSearchController',
    'Botble\RealEstate\Http\Controllers\Fronts\MapSearchController',
    'Botble\RealEstate\Http\Controllers\Fronts\MarketInsightsController',
    'Botble\RealEstate\Http\Controllers\Fronts\PropertyShareController',
    'Botble\RealEstate\Http\Controllers\Fronts\RentPaymentController',
    'Botble\RealEstate\Http\Controllers\Fronts\NeighborhoodInsightsController',
    'Botble\RealEstate\Http\Controllers\Fronts\PriceHistoryController',
];

foreach ($controllers as $controller) {
    check($controller, class_exists($controller), $allPassed, $totalChecks, $passedChecks);
}

// 5. Vue Components
echo "\n5. Vue Components:\n";
$components = [
    'platform/plugins/real-estate/resources/js/components/NaturalLanguageSearch.vue',
    'platform/plugins/real-estate/resources/js/components/MapSearch.vue',
    'platform/plugins/real-estate/resources/js/components/PropertyValuation.vue',
    'platform/plugins/real-estate/resources/js/components/PropertyComparison.vue',
    'platform/plugins/real-estate/resources/js/components/BuyAbilityCalculator.vue',
    'platform/plugins/real-estate/resources/js/components/NeighborhoodInsights.vue',
    'platform/plugins/real-estate/resources/js/components/Property3DTour.vue',
    'platform/plugins/real-estate/resources/js/components/RentPaymentForm.vue',
    'platform/plugins/real-estate/resources/js/components/PriceHistoryChart.vue',
    'platform/plugins/real-estate/resources/js/components/index.js',
];

foreach ($components as $component) {
    check($component, File::exists(base_path($component)), $allPassed, $totalChecks, $passedChecks);
}

// 6. Console Commands
echo "\n6. Console Commands:\n";
$commands = [
    'real-estate:process-saved-search-alerts',
    'real-estate:sync-nigerian-properties',
];

foreach ($commands as $command) {
    $exists = false;
    try {
        $output = shell_exec("php artisan list | grep '{$command}'");
        $exists = !empty($output);
    } catch (\Exception $e) {
        // Command might not be registered yet
    }
    check($command, $exists, $allPassed, $totalChecks, $passedChecks);
}

// 7. Notifications
echo "\n7. Notifications:\n";
$notifications = [
    'Botble\RealEstate\Notifications\SavedSearchAlertNotification',
];

foreach ($notifications as $notification) {
    check($notification, class_exists($notification), $allPassed, $totalChecks, $passedChecks);
}

// Summary
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    Verification Summary                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "Total Checks: {$totalChecks}\n";
echo "Passed: {$passedChecks}\n";
echo "Failed: " . ($totalChecks - $passedChecks) . "\n";
echo "Success Rate: " . round(($passedChecks / $totalChecks) * 100, 2) . "%\n\n";

if ($allPassed) {
    echo "\033[32m✓ All features verified successfully!\033[0m\n";
    echo "\033[32m🎉 Your Zillow-like platform is ready to use!\033[0m\n";
    exit(0);
} else {
    echo "\033[31m✗ Some features are missing. Please check above.\033[0m\n";
    exit(1);
}

