<?php

use Botble\Base\Http\Middleware\RequiresJsonRequestMiddleware;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Controllers\CustomFieldController;
use Botble\RealEstate\Http\Controllers\Fronts\AccountPropertyController;
use Botble\RealEstate\Http\Controllers\Fronts\AccountReviewController;
use Botble\RealEstate\Http\Controllers\Fronts\ConsultController;
use Botble\RealEstate\Http\Controllers\Fronts\CouponController;
use Botble\RealEstate\Http\Controllers\Fronts\ForgotPasswordController;
use Botble\RealEstate\Http\Controllers\Fronts\InvoiceController;
use Botble\RealEstate\Http\Controllers\Fronts\LoginController;
use Botble\RealEstate\Http\Controllers\Fronts\PublicAccountController;
use Botble\RealEstate\Http\Controllers\Fronts\RegisterController;
use Botble\RealEstate\Http\Controllers\Fronts\ResetPasswordController;
use Botble\RealEstate\Http\Controllers\Fronts\ReviewController;
use Botble\RealEstate\Http\Middleware\EnsureAccountIsApproved;
use Botble\RealEstate\Http\Middleware\LocaleMiddleware;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

if (defined('THEME_MODULE_SCREEN_NAME')) {
    Route::group(['namespace' => 'Botble\RealEstate\Http\Controllers\Fronts'], function (): void {
        Theme::registerRoutes(function (): void {
            $projectsPrefix = SlugHelper::getPrefix(Project::class, 'projects') ?: 'projects';

            $propertiesPrefix = SlugHelper::getPrefix(Property::class, 'properties') ?: 'properties';

            Route::match(theme_option('projects_list_page_id') ? ['POST'] : ['POST', 'GET'], $projectsPrefix, 'PublicController@getProjects')
                ->name('public.projects');

            Route::match(theme_option('properties_list_page_id') ? ['POST'] : ['POST', 'GET'], $propertiesPrefix, 'PublicController@getProperties')
                ->name('public.properties');

            if (is_plugin_active('location')) {
                Route::match(['POST', 'GET'], RealEstateHelper::getPageSlug('projects_city') . '/{slug}', 'PublicController@getProjectsByCity')
                    ->name('public.projects-by-city');

                Route::match(['POST', 'GET'], RealEstateHelper::getPageSlug('properties_city') . '/{slug}', 'PublicController@getPropertiesByCity')
                    ->name('public.properties-by-city');

                Route::match(['POST', 'GET'], RealEstateHelper::getPageSlug('projects_state') . '/{slug}', 'PublicController@getProjectsByState')
                    ->name('public.projects-by-state');

                Route::match(['POST', 'GET'], RealEstateHelper::getPageSlug('properties_state') . '/{slug}', 'PublicController@getPropertiesByState')
                    ->name('public.properties-by-state');
            }

            if (! RealEstateHelper::isDisabledPublicProfile()) {
                Route::get(SlugHelper::getPrefix(Account::class, 'agents') ?: 'agents', 'PublicController@getAgents')
                    ->name('public.agents');
            }

            Route::post('send-consult', 'PublicController@postSendConsult')
                ->name('public.send.consult');

            Route::get('currency/switch/{code?}', [
                'as' => 'public.change-currency',
                'uses' => 'PublicController@changeCurrency',
            ]);

            Route::name('public.account.')->group(function (): void {
                Route::middleware('account.guest')->group(function (): void {
                    Route::get(RealEstateHelper::getPageSlug('login'), [LoginController::class, 'showLoginForm'])->name('login');
                    Route::post('login', [LoginController::class, 'login'])->name('login.post');
                    Route::get(RealEstateHelper::getPageSlug('register'), [RegisterController::class, 'showRegistrationForm'])->name('register');
                    Route::post('register', [RegisterController::class, 'register'])->name('register.post');
                    Route::get('verify', [RegisterController::class, 'getVerify'])->name('verify');
                    Route::get(RealEstateHelper::getPageSlug('reset_password'), [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
                    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
                    Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
                    Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
                });

                Route::prefix('register/confirm')
                    ->middleware(setting('verify_account_email', false) ? 'account.guest' : 'account')
                    ->group(function (): void {
                        Route::get('resend', [RegisterController::class, 'resendConfirmation'])->name('resend_confirmation');
                        Route::get('{user}', [RegisterController::class, 'confirm'])->name('confirm');
                    });
            });

            Route::post('ajax/review/{slug}', [ReviewController::class, 'store'])
                ->middleware(['account', RequiresJsonRequestMiddleware::class])
                ->name('public.ajax.review.store');

            Route::get('ajax/review/{slug}', [ReviewController::class, 'index'])
                ->middleware(RequiresJsonRequestMiddleware::class)
                ->name('public.ajax.review.index');
        });

        Route::group(['middleware' => ['web', 'core', 'account', EnsureAccountIsApproved::class, 'account.not_blocked', LocaleMiddleware::class]], function (): void {
            Route::prefix('account')->name('public.account.')->group(function (): void {
                Route::get('pending-approval', [PublicAccountController::class, 'getPendingApproval'])->name('pending-approval');
                Route::get('dashboard', [PublicAccountController::class, 'getDashboard'])->name('dashboard');
                Route::get('settings', [PublicAccountController::class, 'getSettings'])->name('settings');
                Route::post('settings', [PublicAccountController::class, 'postSettings'])->name('post.settings');
                Route::put('security', [PublicAccountController::class, 'postSecurity'])->name('post.security');
                Route::post('logout', [LoginController::class, 'logout'])->name('logout');

                Route::post('avatar', [PublicAccountController::class, 'postAvatar'])->name('avatar');
                Route::get('packages', [PublicAccountController::class, 'getPackages'])->name('packages');
                Route::get('transactions', [PublicAccountController::class, 'getTransactions'])->name('transactions');
                Route::prefix('coupon')->name('coupon.')->group(function (): void {
                    Route::post('apply', [CouponController::class, 'apply'])->name('apply');
                    Route::post('remove', [CouponController::class, 'remove'])->name('remove');
                    Route::get('refresh/{id}', [CouponController::class, 'refresh'])->name('refresh');
                });
                Route::match(['GET', 'POST'], 'consults', [ConsultController::class, 'index'])->name('consults.index');
                Route::get('consults/{id}', [ConsultController::class, 'show'])->name('consults.show')->wherePrimaryKey();

                Route::match(['GET', 'POST'], 'reviews', [AccountReviewController::class, 'index'])->name('reviews.index');

                Route::prefix('ajax')->group(function (): void {
                    Route::get('activity-logs', [PublicAccountController::class, 'getActivityLogs'])->name('activity-logs');
                    Route::get('transactions', [PublicAccountController::class, 'ajaxGetTransactions'])->name('ajax.transactions');
                    Route::post('upload', [PublicAccountController::class, 'postUpload'])->name('upload');
                    Route::post('upload-from-editor', [PublicAccountController::class, 'postUploadFromEditor'])->name('upload-from-editor');
                    Route::get('packages', [PublicAccountController::class, 'ajaxGetPackages'])->name('ajax.packages');
                    Route::put('packages', [PublicAccountController::class, 'ajaxSubscribePackage'])->name('ajax.package.subscribe');
                });

                Route::get('packages/{id}/subscribe', [PublicAccountController::class, 'getSubscribePackage'])
                    ->name('package.subscribe')
                    ->wherePrimaryKey();
                Route::get('packages/{id}/subscribe/callback', [PublicAccountController::class, 'getPackageSubscribeCallback'])
                    ->name('package.subscribe.callback')
                    ->wherePrimaryKey();

                Route::prefix('properties')->name('properties.')->group(function (): void {
                    Route::resource('', AccountPropertyController::class)->parameters(['' => 'property']);
                    Route::post('renew/{id}', [AccountPropertyController::class, 'renew'])->name('renew')->wherePrimaryKey();
                });

                Route::prefix('invoices')
                    ->name('invoices.')
                    ->controller(InvoiceController::class)
                    ->group(function (): void {
                        Route::match(['GET', 'POST'], '/', 'index')->name('index');
                        Route::get('{id}', 'show')->name('show')
                            ->wherePrimaryKey();
                        Route::get('{id}/generate', [InvoiceController::class, 'generate'])
                            ->name('generate')
                            ->wherePrimaryKey();
                    });

                Route::get('custom-fields/info', [CustomFieldController::class, 'getInfo'])
                    ->name('custom-fields.get-info');
            });

            Route::post('payments/checkout', 'CheckoutController@postCheckout')->name('payments.checkout');
        });

        // Zillow-like features routes
        Route::group(['middleware' => ['web', 'core', 'account', LocaleMiddleware::class]], function (): void {
            Route::prefix('saved-searches')->name('saved-searches.')->group(function (): void {
                Route::post('/', [\Botble\RealEstate\Http\Controllers\Fronts\SavedSearchController::class, 'store'])->name('store');
                Route::get('/', [\Botble\RealEstate\Http\Controllers\Fronts\SavedSearchController::class, 'index'])->name('index');
                Route::put('{id}', [\Botble\RealEstate\Http\Controllers\Fronts\SavedSearchController::class, 'update'])->name('update');
                Route::delete('{id}', [\Botble\RealEstate\Http\Controllers\Fronts\SavedSearchController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('property-comparison')->name('property-comparison.')->group(function (): void {
                Route::post('add', [\Botble\RealEstate\Http\Controllers\Fronts\PropertyComparisonController::class, 'add'])->name('add');
                Route::post('remove', [\Botble\RealEstate\Http\Controllers\Fronts\PropertyComparisonController::class, 'remove'])->name('remove');
                Route::get('/', [\Botble\RealEstate\Http\Controllers\Fronts\PropertyComparisonController::class, 'index'])->name('index');
                Route::post('clear', [\Botble\RealEstate\Http\Controllers\Fronts\PropertyComparisonController::class, 'clear'])->name('clear');
            });

            Route::post('mortgage-calculator/calculate', [\Botble\RealEstate\Http\Controllers\Fronts\MortgageCalculatorController::class, 'calculate'])
                ->name('mortgage-calculator.calculate');

            Route::get('property/{id}/valuation', [\Botble\RealEstate\Http\Controllers\Fronts\PublicController::class, 'getPropertyValuation'])
                ->name('property.valuation')
                ->wherePrimaryKey();

            // Tour scheduling
            Route::prefix('tours')->name('tours.')->group(function (): void {
                Route::post('schedule', [\Botble\RealEstate\Http\Controllers\Fronts\TourController::class, 'schedule'])->name('schedule');
                Route::get('/', [\Botble\RealEstate\Http\Controllers\Fronts\TourController::class, 'index'])->name('index');
                Route::put('{id}/status', [\Botble\RealEstate\Http\Controllers\Fronts\TourController::class, 'updateStatus'])->name('update-status');
            });

            // Rental applications
            Route::prefix('rental-applications')->name('rental-applications.')->group(function (): void {
                Route::post('submit', [\Botble\RealEstate\Http\Controllers\Fronts\RentalApplicationController::class, 'submit'])->name('submit');
                Route::get('/', [\Botble\RealEstate\Http\Controllers\Fronts\RentalApplicationController::class, 'index'])->name('index');
                Route::put('{id}/status', [\Botble\RealEstate\Http\Controllers\Fronts\RentalApplicationController::class, 'updateStatus'])->name('update-status');
            });

            // BuyAbility calculator
            Route::post('buyability/calculate', [\Botble\RealEstate\Http\Controllers\Fronts\BuyAbilityController::class, 'calculate'])
                ->name('buyability.calculate');

            // Property recommendations
            Route::get('recommendations', [\Botble\RealEstate\Http\Controllers\Fronts\PublicController::class, 'getRecommendations'])
                ->name('recommendations');

            // Natural language search
            Route::get('search/natural', [\Botble\RealEstate\Http\Controllers\Fronts\NaturalLanguageSearchController::class, 'search'])
                ->name('search.natural');

            // Map search
            Route::prefix('map-search')->name('map-search.')->group(function (): void {
                Route::post('bounds', [\Botble\RealEstate\Http\Controllers\Fronts\MapSearchController::class, 'searchByBounds'])->name('bounds');
                Route::post('radius', [\Botble\RealEstate\Http\Controllers\Fronts\MapSearchController::class, 'searchByRadius'])->name('radius');
                Route::post('polygon', [\Botble\RealEstate\Http\Controllers\Fronts\MapSearchController::class, 'searchByPolygon'])->name('polygon');
                Route::get('clusters', [\Botble\RealEstate\Http\Controllers\Fronts\MapSearchController::class, 'getClusters'])->name('clusters');
            });

            // Market insights
            Route::get('market-insights', [\Botble\RealEstate\Http\Controllers\Fronts\MarketInsightsController::class, 'getInsights'])
                ->name('market-insights');

            // Property sharing
            Route::prefix('property-share')->name('property-share.')->group(function (): void {
                Route::post('/', [\Botble\RealEstate\Http\Controllers\Fronts\PropertyShareController::class, 'share'])->name('share');
                Route::get('{id}/stats', [\Botble\RealEstate\Http\Controllers\Fronts\PropertyShareController::class, 'getShareStats'])->name('stats');
            });
        });

        // Public shared property route (no auth required)
        Route::get('property/shared/{token}', [\Botble\RealEstate\Http\Controllers\Fronts\PropertyShareController::class, 'viewShared'])
            ->name('property.shared');

        // Rent payments
        Route::prefix('rent-payments')->name('rent-payments.')->group(function (): void {
            Route::get('/', [\Botble\RealEstate\Http\Controllers\Fronts\RentPaymentController::class, 'index'])->name('index');
            Route::post('initiate', [\Botble\RealEstate\Http\Controllers\Fronts\RentPaymentController::class, 'initiatePayment'])->name('initiate');
            Route::get('callback', [\Botble\RealEstate\Http\Controllers\Fronts\RentPaymentController::class, 'callback'])->name('callback');
            Route::get('banks', [\Botble\RealEstate\Http\Controllers\Fronts\RentPaymentController::class, 'getBanks'])->name('banks');
        });

        // Neighborhood insights
        Route::get('property/{id}/neighborhood-insights', [\Botble\RealEstate\Http\Controllers\Fronts\NeighborhoodInsightsController::class, 'getInsights'])
            ->name('property.neighborhood-insights')
            ->wherePrimaryKey();

        // Price history
        Route::get('property/{id}/price-history', [\Botble\RealEstate\Http\Controllers\Fronts\PriceHistoryController::class, 'getPropertyPriceHistory'])
            ->name('property.price-history')
            ->wherePrimaryKey();
        Route::get('neighborhood-trends', [\Botble\RealEstate\Http\Controllers\Fronts\PriceHistoryController::class, 'getNeighborhoodTrends'])
            ->name('neighborhood-trends');
    });
}
