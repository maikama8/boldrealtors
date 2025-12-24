<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PropertyRecommendationService
{
    /**
     * Get personalized property recommendations
     */
    public function getRecommendations(?int $limit = 12): Collection
    {
        $account = Auth::guard('account')->user();
        
        if (!$account) {
            // For guests, return featured properties
            return Property::query()
                ->where('is_featured', true)
                ->where('moderation_status', 'approved')
                ->latest()
                ->limit($limit)
                ->get();
        }

        $recommendations = collect();

        // 1. Based on saved/favorited properties
        $savedProperties = $this->getSavedProperties($account);
        if ($savedProperties->isNotEmpty()) {
            $recommendations = $recommendations->merge(
                $this->getSimilarProperties($savedProperties, $limit)
            );
        }

        // 2. Based on viewed properties (if tracking available)
        $viewedProperties = $this->getViewedProperties($account);
        if ($viewedProperties->isNotEmpty() && $recommendations->count() < $limit) {
            $recommendations = $recommendations->merge(
                $this->getSimilarProperties($viewedProperties, $limit - $recommendations->count())
            );
        }

        // 3. Based on search history (if available)
        $searchHistory = $this->getSearchHistory($account);
        if ($searchHistory->isNotEmpty() && $recommendations->count() < $limit) {
            $recommendations = $recommendations->merge(
                $this->getPropertiesFromSearchCriteria($searchHistory, $limit - $recommendations->count())
            );
        }

        // 4. Based on location (if user has location preference)
        if ($recommendations->count() < $limit) {
            $locationBased = $this->getLocationBasedRecommendations($account, $limit - $recommendations->count());
            $recommendations = $recommendations->merge($locationBased);
        }

        // Remove duplicates and return
        return $recommendations->unique('id')->take($limit);
    }

    /**
     * Get similar properties based on given properties
     */
    protected function getSimilarProperties(Collection $properties, int $limit): Collection
    {
        if ($properties->isEmpty()) {
            return collect();
        }

        // Get average characteristics
        $avgPrice = $properties->avg('price');
        $avgBedrooms = $properties->avg('number_bedroom');
        $avgBathrooms = $properties->avg('number_bathroom');
        $commonCityIds = $properties->pluck('city_id')->unique()->toArray();
        $commonType = $properties->mode('type');

        $query = Property::query()
            ->where('moderation_status', 'approved')
            ->whereNotIn('id', $properties->pluck('id'))
            ->whereIn('city_id', $commonCityIds);

        if ($commonType) {
            $query->where('type', $commonType);
        }

        // Price range: ±30% of average
        if ($avgPrice) {
            $query->whereBetween('price', [
                $avgPrice * 0.7,
                $avgPrice * 1.3
            ]);
        }

        // Bedrooms: ±1
        if ($avgBedrooms) {
            $query->whereBetween('number_bedroom', [
                max(1, floor($avgBedrooms - 1)),
                ceil($avgBedrooms + 1)
            ]);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get saved properties for account
     */
    protected function getSavedProperties($account): Collection
    {
        // This would integrate with your wishlist/favorites system
        // For now, return empty collection
        return collect();
    }

    /**
     * Get viewed properties for account
     */
    protected function getViewedProperties($account): Collection
    {
        // This would integrate with view tracking
        // For now, return empty collection
        return collect();
    }

    /**
     * Get search history for account
     */
    protected function getSearchHistory($account): Collection
    {
        return \Botble\RealEstate\Models\SavedSearch::where('account_id', $account->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get properties from search criteria
     */
    protected function getPropertiesFromSearchCriteria(Collection $searches, int $limit): Collection
    {
        if ($searches->isEmpty()) {
            return collect();
        }

        $latestSearch = $searches->first();
        $criteria = $latestSearch->search_criteria ?? [];

        $query = Property::query()->where('moderation_status', 'approved');

        // Apply search criteria
        if (isset($criteria['city_id'])) {
            $query->where('city_id', $criteria['city_id']);
        }

        if (isset($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        if (isset($criteria['min_price'])) {
            $query->where('price', '>=', $criteria['min_price']);
        }

        if (isset($criteria['max_price'])) {
            $query->where('price', '<=', $criteria['max_price']);
        }

        if (isset($criteria['bedrooms'])) {
            $query->where('number_bedroom', '>=', $criteria['bedrooms']);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get location-based recommendations
     */
    protected function getLocationBasedRecommendations($account, int $limit): Collection
    {
        // If account has a preferred location, use it
        // Otherwise, return featured properties
        return Property::query()
            ->where('moderation_status', 'approved')
            ->where('is_featured', true)
            ->latest()
            ->limit($limit)
            ->get();
    }
}

