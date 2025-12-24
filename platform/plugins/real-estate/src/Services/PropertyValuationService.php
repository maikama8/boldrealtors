<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyValuation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PropertyValuationService
{
    /**
     * Calculate property valuation based on similar properties
     */
    public function calculateValuation(Property $property): PropertyValuation
    {
        // Get similar properties in the same area
        $similarProperties = Property::query()
            ->where('id', '!=', $property->id)
            ->where('city_id', $property->city_id)
            ->where('status', $property->status)
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->get();

        $estimatedValue = $property->price;
        $pricePerSquareFoot = 0;
        $rentEstimate = 0;

        if ($similarProperties->isNotEmpty() && $property->square > 0) {
            // Calculate average price per square foot from similar properties
            $totalPricePerSqft = $similarProperties->sum(function ($prop) {
                return $prop->square > 0 ? ($prop->price / $prop->square) : 0;
            });

            $count = $similarProperties->filter(fn($p) => $p->square > 0)->count();
            $avgPricePerSqft = $count > 0 ? ($totalPricePerSqft / $count) : 0;

            if ($avgPricePerSqft > 0) {
                $pricePerSquareFoot = $avgPricePerSqft;
                // Estimate value based on square footage
                $estimatedValue = $property->square * $avgPricePerSqft;
                
                // Adjust based on property features
                $estimatedValue = $this->adjustForFeatures($estimatedValue, $property, $similarProperties);
            }

            // Estimate rent (typically 0.8-1.2% of property value per month)
            $monthlyRentRate = 0.01; // 1% per month
            $rentEstimate = $estimatedValue * $monthlyRentRate;
        }

        $valuationData = [
            'similar_properties_count' => $similarProperties->count(),
            'calculation_method' => 'comparable_sales',
            'confidence_score' => $this->calculateConfidenceScore($property, $similarProperties),
        ];

        return PropertyValuation::updateOrCreate(
            ['property_id' => $property->id],
            [
                'estimated_value' => round($estimatedValue, 2),
                'price_per_square_foot' => round($pricePerSquareFoot, 2),
                'rent_estimate' => round($rentEstimate, 2),
                'valuation_data' => $valuationData,
                'last_calculated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Adjust estimated value based on property features
     */
    protected function adjustForFeatures(float $baseValue, Property $property, $similarProperties): float
    {
        $adjustedValue = $baseValue;

        // Adjust for bedrooms
        $avgBedrooms = $similarProperties->avg('number_bedroom') ?: 0;
        if ($property->number_bedroom > $avgBedrooms) {
            $bedroomDiff = $property->number_bedroom - $avgBedrooms;
            $adjustedValue += ($adjustedValue * 0.05 * $bedroomDiff); // 5% per extra bedroom
        }

        // Adjust for bathrooms
        $avgBathrooms = $similarProperties->avg('number_bathroom') ?: 0;
        if ($property->number_bathroom > $avgBathrooms) {
            $bathroomDiff = $property->number_bathroom - $avgBathrooms;
            $adjustedValue += ($adjustedValue * 0.03 * $bathroomDiff); // 3% per extra bathroom
        }

        // Adjust for featured status
        if ($property->is_featured) {
            $adjustedValue += ($adjustedValue * 0.1); // 10% premium for featured
        }

        return $adjustedValue;
    }

    /**
     * Calculate confidence score for the valuation
     */
    protected function calculateConfidenceScore(Property $property, $similarProperties): float
    {
        if ($similarProperties->isEmpty()) {
            return 0.3; // Low confidence with no similar properties
        }

        $score = 0.5; // Base score

        // More similar properties = higher confidence
        if ($similarProperties->count() >= 10) {
            $score += 0.3;
        } elseif ($similarProperties->count() >= 5) {
            $score += 0.2;
        } elseif ($similarProperties->count() >= 3) {
            $score += 0.1;
        }

        // If property has square footage data
        if ($property->square > 0) {
            $score += 0.1;
        }

        // If property has location data
        if ($property->latitude && $property->longitude) {
            $score += 0.1;
        }

        return min(1.0, $score);
    }

    /**
     * Get or calculate valuation for a property
     */
    public function getValuation(Property $property, bool $recalculate = false): ?PropertyValuation
    {
        $valuation = PropertyValuation::where('property_id', $property->id)->first();

        // Recalculate if requested or if valuation is older than 30 days
        if ($recalculate || !$valuation || 
            ($valuation->last_calculated_at && $valuation->last_calculated_at->diffInDays() > 30)) {
            return $this->calculateValuation($property);
        }

        return $valuation;
    }
}

