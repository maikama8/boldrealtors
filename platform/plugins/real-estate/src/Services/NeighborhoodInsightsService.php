<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Http;

class NeighborhoodInsightsService
{
    /**
     * Get neighborhood insights for a property
     */
    public function getInsights(Property $property): array
    {
        if (!$property->latitude || !$property->longitude) {
            return [];
        }

        $insights = [
            'schools' => $this->getNearbySchools($property),
            'demographics' => $this->getDemographics($property),
            'crime_stats' => $this->getCrimeStats($property),
            'walkability' => $this->calculateWalkability($property),
            'transit_score' => $this->calculateTransitScore($property),
            'amenities' => $this->getNearbyAmenities($property),
        ];

        return $insights;
    }

    /**
     * Get nearby schools (would integrate with school data API)
     */
    protected function getNearbySchools(Property $property, float $radiusKm = 5): array
    {
        // This would integrate with a schools database or API
        // For now, return placeholder structure
        return [
            'primary_schools' => [
                'count' => 0,
                'average_rating' => null,
                'nearest' => null,
            ],
            'secondary_schools' => [
                'count' => 0,
                'average_rating' => null,
                'nearest' => null,
            ],
            'universities' => [
                'count' => 0,
                'nearest' => null,
            ],
        ];
    }

    /**
     * Get demographics data (would integrate with census/demographic API)
     */
    protected function getDemographics(Property $property): array
    {
        // This would integrate with demographic data sources
        return [
            'population' => null,
            'median_age' => null,
            'median_income' => null,
            'household_size' => null,
            'education_level' => null,
        ];
    }

    /**
     * Get crime statistics (would integrate with crime data API)
     */
    protected function getCrimeStats(Property $property): array
    {
        // This would integrate with crime statistics API
        return [
            'crime_rate' => null, // per 1000 residents
            'safety_score' => null, // 0-100
            'crime_trend' => null, // increasing, decreasing, stable
            'last_updated' => null,
        ];
    }

    /**
     * Calculate walkability score
     */
    protected function calculateWalkability(Property $property): array
    {
        // Calculate based on nearby amenities, sidewalks, etc.
        $score = 50; // Base score
        
        // Check for nearby amenities (would query database)
        $nearbyAmenities = $this->getNearbyAmenities($property);
        
        if (count($nearbyAmenities['restaurants']) > 5) {
            $score += 10;
        }
        if (count($nearbyAmenities['shopping']) > 3) {
            $score += 10;
        }
        if (count($nearbyAmenities['parks']) > 2) {
            $score += 10;
        }

        return [
            'score' => min(100, $score),
            'description' => $this->getWalkabilityDescription($score),
        ];
    }

    /**
     * Calculate transit score
     */
    protected function calculateTransitScore(Property $property): array
    {
        // Calculate based on nearby public transportation
        $score = 30; // Base score
        
        // This would check for nearby bus stops, train stations, etc.
        // For now, return placeholder
        
        return [
            'score' => $score,
            'description' => $this->getTransitDescription($score),
            'nearest_stop' => null,
            'distance_to_stop' => null,
        ];
    }

    /**
     * Get nearby amenities
     */
    protected function getNearbyAmenities(Property $property, float $radiusKm = 2): array
    {
        // Query properties/facilities within radius
        $amenities = Property::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('id', '!=', $property->id)
            ->get()
            ->filter(function ($p) use ($property, $radiusKm) {
                $distance = $this->calculateDistance(
                    $property->latitude,
                    $property->longitude,
                    $p->latitude,
                    $p->longitude
                );
                return $distance <= $radiusKm;
            });

        // Categorize amenities (this would be more sophisticated)
        return [
            'restaurants' => [],
            'shopping' => [],
            'parks' => [],
            'hospitals' => [],
            'banks' => [],
            'total' => $amenities->count(),
        ];
    }

    protected function getWalkabilityDescription(int $score): string
    {
        if ($score >= 90) {
            return 'Walker\'s Paradise - Daily errands do not require a car';
        } elseif ($score >= 70) {
            return 'Very Walkable - Most errands can be accomplished on foot';
        } elseif ($score >= 50) {
            return 'Somewhat Walkable - Some errands can be accomplished on foot';
        } elseif ($score >= 25) {
            return 'Car-Dependent - Most errands require a car';
        } else {
            return 'Car-Dependent - Almost all errands require a car';
        }
    }

    protected function getTransitDescription(int $score): string
    {
        if ($score >= 90) {
            return 'Rider\'s Paradise - World-class public transportation';
        } elseif ($score >= 70) {
            return 'Excellent Transit - Transit is convenient for most trips';
        } elseif ($score >= 50) {
            return 'Good Transit - Many nearby public transportation options';
        } elseif ($score >= 25) {
            return 'Some Transit - A few nearby public transportation options';
        } else {
            return 'Minimal Transit - Very few nearby public transportation options';
        }
    }

    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

