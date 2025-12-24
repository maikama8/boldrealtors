<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Str;

class NaturalLanguageSearchService
{
    /**
     * Parse natural language query and convert to search criteria
     */
    public function parseQuery(string $query): array
    {
        $criteria = [
            'keywords' => [],
            'bedrooms' => null,
            'bathrooms' => null,
            'price_min' => null,
            'price_max' => null,
            'property_type' => null,
            'location' => null,
            'amenities' => [],
            'features' => [],
        ];

        $query = strtolower($query);
        $words = explode(' ', $query);

        // Extract bedrooms
        foreach ($words as $word) {
            if (preg_match('/(\d+)\s*(bed|bedroom|bedrooms|br|brs)/i', $query, $matches)) {
                $criteria['bedrooms'] = (int) $matches[1];
            }
        }

        // Extract bathrooms
        if (preg_match('/(\d+)\s*(bath|bathroom|bathrooms|ba)/i', $query, $matches)) {
            $criteria['bathrooms'] = (int) $matches[1];
        }

        // Extract price range
        if (preg_match('/(under|below|less than|max|maximum)\s*(₦|naira|ngn)?\s*(\d+[,\d]*)/i', $query, $matches)) {
            $price = $this->parsePrice($matches[3]);
            $criteria['price_max'] = $price;
        } elseif (preg_match('/(over|above|more than|min|minimum|from)\s*(₦|naira|ngn)?\s*(\d+[,\d]*)/i', $query, $matches)) {
            $price = $this->parsePrice($matches[3]);
            $criteria['price_min'] = $price;
        } elseif (preg_match('/(₦|naira|ngn)?\s*(\d+[,\d]*)\s*(to|-|and)\s*(₦|naira|ngn)?\s*(\d+[,\d]*)/i', $query, $matches)) {
            $criteria['price_min'] = $this->parsePrice($matches[2]);
            $criteria['price_max'] = $this->parsePrice($matches[5]);
        } elseif (preg_match('/(₦|naira|ngn)?\s*(\d+[,\d]*)/i', $query, $matches)) {
            // Single price - treat as max
            $price = $this->parsePrice($matches[2]);
            if ($price > 100000) { // Likely a property price
                $criteria['price_max'] = $price;
            }
        }

        // Extract location
        $nigerianCities = [
            'lagos', 'abuja', 'port harcourt', 'kano', 'ibadan', 'benin', 'kaduna',
            'abia', 'adamawa', 'akwa ibom', 'anambra', 'bauchi', 'bayelsa', 'borno',
            'cross river', 'delta', 'ebonyi', 'edo', 'ekiti', 'enugu', 'gombe',
            'imo', 'jigawa', 'kebbi', 'kwara', 'nasarawa', 'niger', 'ogun', 'ondo',
            'osun', 'oyo', 'plateau', 'rivers', 'sokoto', 'taraba', 'yobe', 'zamfara'
        ];

        foreach ($nigerianCities as $city) {
            if (stripos($query, $city) !== false) {
                $criteria['location'] = $city;
                break;
            }
        }

        // Extract property type
        $propertyTypes = [
            'house' => 'house',
            'apartment' => 'apartment',
            'flat' => 'apartment',
            'bungalow' => 'house',
            'duplex' => 'house',
            'terrace' => 'house',
            'villa' => 'house',
            'condo' => 'apartment',
            'studio' => 'apartment',
            'penthouse' => 'apartment',
        ];

        foreach ($propertyTypes as $keyword => $type) {
            if (stripos($query, $keyword) !== false) {
                $criteria['property_type'] = $type;
                break;
            }
        }

        // Extract amenities
        $amenities = [
            'pool' => 'pool',
            'swimming pool' => 'pool',
            'gym' => 'gym',
            'fitness' => 'gym',
            'parking' => 'parking',
            'garage' => 'parking',
            'security' => 'security',
            'elevator' => 'elevator',
            'lift' => 'elevator',
            'balcony' => 'balcony',
            'garden' => 'garden',
            'pet friendly' => 'pet_friendly',
            'furnished' => 'furnished',
            'unfurnished' => 'unfurnished',
            'air conditioning' => 'air_conditioning',
            'ac' => 'air_conditioning',
            'hardwood' => 'hardwood',
            'hardwood floors' => 'hardwood',
        ];

        foreach ($amenities as $keyword => $amenity) {
            if (stripos($query, $keyword) !== false) {
                $criteria['amenities'][] = $amenity;
            }
        }

        // Extract keywords (remaining words)
        $stopWords = ['in', 'with', 'and', 'or', 'the', 'a', 'an', 'for', 'to', 'of', 'near', 'around'];
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
        $criteria['keywords'] = array_values($keywords);

        return $criteria;
    }

    /**
     * Search properties using natural language criteria
     */
    public function search(string $query, int $perPage = 20)
    {
        $criteria = $this->parseQuery($query);
        
        $queryBuilder = Property::query()
            ->where('moderation_status', 'approved')
            ->where('status', '!=', 'not_available');

        // Apply bedrooms filter
        if ($criteria['bedrooms']) {
            $queryBuilder->where('number_bedroom', '>=', $criteria['bedrooms']);
        }

        // Apply bathrooms filter
        if ($criteria['bathrooms']) {
            $queryBuilder->where('number_bathroom', '>=', $criteria['bathrooms']);
        }

        // Apply price filters
        if ($criteria['price_min']) {
            $queryBuilder->where('price', '>=', $criteria['price_min']);
        }
        if ($criteria['price_max']) {
            $queryBuilder->where('price', '<=', $criteria['price_max']);
        }

        // Apply location filter
        if ($criteria['location']) {
            $queryBuilder->whereHas('city', function($q) use ($criteria) {
                $q->where('name', 'like', '%' . $criteria['location'] . '%');
            })->orWhere('location', 'like', '%' . $criteria['location'] . '%');
        }

        // Apply property type filter
        if ($criteria['property_type']) {
            // Map to actual property type enum values
            $typeMap = [
                'house' => 'selling',
                'apartment' => 'selling',
            ];
            // This would need to be adjusted based on your actual property type system
        }

        // Apply keyword search
        if (!empty($criteria['keywords'])) {
            $queryBuilder->where(function($q) use ($criteria) {
                foreach ($criteria['keywords'] as $keyword) {
                    $q->orWhere('name', 'like', '%' . $keyword . '%')
                      ->orWhere('description', 'like', '%' . $keyword . '%')
                      ->orWhere('location', 'like', '%' . $keyword . '%');
                }
            });
        }

        // Apply amenities filter
        if (!empty($criteria['amenities'])) {
            foreach ($criteria['amenities'] as $amenity) {
                $queryBuilder->whereHas('facilities', function($q) use ($amenity) {
                    $q->where('name', 'like', '%' . $amenity . '%');
                });
            }
        }

        return $queryBuilder->with(['city', 'state', 'currency', 'features', 'facilities'])
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Parse price string to float
     */
    protected function parsePrice(string $priceString): float
    {
        // Remove currency symbols and commas
        $price = preg_replace('/[^0-9.]/', '', $priceString);
        
        // Handle millions (e.g., "5M" = 5,000,000)
        if (preg_match('/(\d+\.?\d*)\s*m/i', $priceString, $matches)) {
            return (float) $matches[1] * 1000000;
        }
        
        // Handle thousands (e.g., "500K" = 500,000)
        if (preg_match('/(\d+\.?\d*)\s*k/i', $priceString, $matches)) {
            return (float) $matches[1] * 1000;
        }
        
        return (float) $price;
    }
}

