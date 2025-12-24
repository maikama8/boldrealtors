<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'api/v1',
    'namespace' => 'Botble\RealEstate\Http\Controllers\API',
], function (): void {
    
    // Public endpoints (no authentication required)
    
    // Properties
    Route::get('properties', 'PropertyController@index');
    Route::get('properties/search', 'PropertyController@getSearch');
    Route::get('properties/filters', 'PropertyController@getFilters');
    Route::get('properties/{slug}', 'PropertyController@findBySlug');
    Route::get('properties/id/{id}', 'PropertyController@show')->where('id', '[0-9]+');
    
    // Projects
    Route::get('projects', 'ProjectController@index');
    Route::get('projects/search', 'ProjectController@getSearch');
    Route::get('projects/filters', 'ProjectController@getFilters');
    Route::get('projects/{slug}', 'ProjectController@findBySlug');
    Route::get('projects/id/{id}', 'ProjectController@show')->where('id', '[0-9]+');
    Route::get('projects/id/{id}/properties', 'ProjectController@getProperties')->where('id', '[0-9]+');
    
    // Categories
    Route::get('categories', 'CategoryController@index');
    Route::get('categories/filters', 'CategoryController@getFilters');
    Route::get('categories/{slug}', 'CategoryController@findBySlug');
    Route::get('categories/id/{id}', 'CategoryController@show')->where('id', '[0-9]+');
    Route::get('categories/id/{id}/properties', 'CategoryController@getProperties')->where('id', '[0-9]+');
    
    // Features
    Route::get('features', 'FeatureController@index');
    Route::get('features/all', 'FeatureController@all');
    Route::get('features/{id}', 'FeatureController@show')->where('id', '[0-9]+');
    
    // Facilities
    Route::get('facilities', 'FacilityController@index');
    Route::get('facilities/all', 'FacilityController@all');
    Route::get('facilities/{id}', 'FacilityController@show')->where('id', '[0-9]+');
    
    // Agents/Accounts
    Route::get('agents', 'AccountController@index');
    Route::get('agents/{id}', 'AccountController@show')->where('id', '[0-9]+');
    Route::get('agents/{id}/properties', 'AccountController@getProperties')->where('id', '[0-9]+');
    Route::get('agents/{id}/projects', 'AccountController@getProjects')->where('id', '[0-9]+');
    
    // Reviews (public read)
    Route::get('properties/{property_id}/reviews', 'ReviewController@index')->where('property_id', '[0-9]+');
    Route::get('reviews/{id}', 'ReviewController@show')->where('id', '[0-9]+');
    
    // Consultation
    Route::post('consults', 'ConsultController@store');
    Route::get('consults/custom-fields', 'ConsultController@getCustomFields');
    
    // Authenticated endpoints (require auth:sanctum middleware)
    Route::group(['middleware' => ['auth:sanctum']], function (): void {
        
        // Account profile
        Route::get('account/profile', 'AccountController@profile');
        
        // Reviews (authenticated actions)
        Route::post('properties/{property_id}/reviews', 'ReviewController@store')->where('property_id', '[0-9]+');
        Route::put('reviews/{id}', 'ReviewController@update')->where('id', '[0-9]+');
        Route::delete('reviews/{id}', 'ReviewController@destroy')->where('id', '[0-9]+');
        
    });
});
