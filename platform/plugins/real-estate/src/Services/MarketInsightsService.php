<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\MarketInsight;
use Botble\RealEstate\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketInsightsService
{
    /**
     * Generate market insights for a location
     */
    public function generateInsights(
        string $locationType,
        string $locationId,
        string $locationName,
        ?Carbon $date = null
    ): MarketInsight {
        $date = $date ?? Carbon::now();

        // Check if insights already exist for this date
        $insight = MarketInsight::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('insight_date', $date->format('Y-m-d'))
            ->first();

        if ($insight) {
            return $insight;
        }

        // Get properties for this location
        $properties = $this->getPropertiesForLocation($locationType, $locationId);

        if ($properties->isEmpty()) {
            throw new \Exception("No properties found for location: {$locationName}");
        }

        // Calculate metrics
        $prices = $properties->pluck('price')->filter()->values();
        $averagePrice = $prices->avg();
        $medianPrice = $prices->median();
        $totalSquare = $properties->sum('square');
        $totalProperties = $properties->count();
        $pricePerSquareFoot = $totalSquare > 0 ? ($prices->sum() / $totalSquare) : 0;

        // Get new listings (last 30 days)
        $newListings = $properties->filter(function ($property) use ($date) {
            return $property->created_at->greaterThan($date->copy()->subDays(30));
        })->count();

        // Get sold listings (if status tracking available)
        $soldListings = $properties->where('status', 'sold')->count();

        // Calculate average days on market
        $averageDaysOnMarket = $this->calculateAverageDaysOnMarket($properties);

        // Get previous month's data for comparison
        $previousInsight = MarketInsight::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('insight_date', '<', $date->format('Y-m-d'))
            ->orderBy('insight_date', 'desc')
            ->first();

        $priceChangePercentage = null;
        if ($previousInsight && $previousInsight->average_price > 0) {
            $priceChangePercentage = (($averagePrice - $previousInsight->average_price) / $previousInsight->average_price) * 100;
        }

        // Generate trend data (last 12 months)
        $trendData = $this->generateTrendData($locationType, $locationId, $date);

        // Generate forecast data
        $forecastData = $this->generateForecast($trendData);

        $insight = MarketInsight::create([
            'location_type' => $locationType,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'insight_date' => $date,
            'average_price' => round($averagePrice, 2),
            'median_price' => round($medianPrice, 2),
            'price_per_square_foot' => round($pricePerSquareFoot, 2),
            'total_listings' => $totalProperties,
            'new_listings' => $newListings,
            'sold_listings' => $soldListings,
            'average_days_on_market' => round($averageDaysOnMarket, 1),
            'price_change_percentage' => $priceChangePercentage ? round($priceChangePercentage, 2) : null,
            'trend_data' => $trendData,
            'forecast_data' => $forecastData,
        ]);

        return $insight;
    }

    /**
     * Get properties for a location
     */
    protected function getPropertiesForLocation(string $locationType, string $locationId): \Illuminate\Support\Collection
    {
        $query = Property::query()
            ->where('moderation_status', 'approved')
            ->whereNotNull('price')
            ->where('price', '>', 0);

        switch ($locationType) {
            case 'city':
                $query->where('city_id', $locationId);
                break;
            case 'state':
                $query->where('state_id', $locationId);
                break;
            case 'country':
                $query->where('country_id', $locationId);
                break;
            case 'neighborhood':
                // Would need neighborhood field or custom logic
                break;
        }

        return $query->get();
    }

    /**
     * Calculate average days on market
     */
    protected function calculateAverageDaysOnMarket($properties): float
    {
        $totalDays = 0;
        $count = 0;

        foreach ($properties as $property) {
            if ($property->created_at && $property->updated_at) {
                $days = $property->created_at->diffInDays($property->updated_at);
                $totalDays += $days;
                $count++;
            }
        }

        return $count > 0 ? ($totalDays / $count) : 0;
    }

    /**
     * Generate trend data for last 12 months
     */
    protected function generateTrendData(string $locationType, string $locationId, Carbon $date): array
    {
        $trends = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = $date->copy()->subMonths($i)->startOfMonth();
            
            $properties = $this->getPropertiesForLocation($locationType, $locationId)
                ->filter(function ($property) use ($monthDate) {
                    return $property->created_at->lessThanOrEqualTo($monthDate->endOfMonth());
                });

            $avgPrice = $properties->pluck('price')->filter()->avg();
            $count = $properties->count();

            $trends[] = [
                'month' => $monthDate->format('Y-m'),
                'average_price' => round($avgPrice ?? 0, 2),
                'listings_count' => $count,
            ];
        }

        return $trends;
    }

    /**
     * Generate forecast for next 3 months
     */
    protected function generateForecast(array $trendData): array
    {
        if (count($trendData) < 3) {
            return [];
        }

        // Simple linear regression for forecasting
        $prices = array_column($trendData, 'average_price');
        $n = count($prices);
        
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $prices[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        $forecast = [];
        $lastMonth = Carbon::parse(end($trendData)['month']);

        for ($i = 1; $i <= 3; $i++) {
            $forecastMonth = $lastMonth->copy()->addMonths($i);
            $predictedPrice = $intercept + $slope * ($n + $i);
            
            $forecast[] = [
                'month' => $forecastMonth->format('Y-m'),
                'predicted_price' => round(max(0, $predictedPrice), 2),
            ];
        }

        return $forecast;
    }

    /**
     * Get insights for a location
     */
    public function getInsights(string $locationType, string $locationId, ?Carbon $date = null): ?MarketInsight
    {
        $date = $date ?? Carbon::now();

        return MarketInsight::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('insight_date', '<=', $date->format('Y-m-d'))
            ->orderBy('insight_date', 'desc')
            ->first();
    }
}

