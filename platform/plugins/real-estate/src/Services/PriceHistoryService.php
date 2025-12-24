<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PriceHistoryService
{
    /**
     * Get price history chart data for a property
     */
    public function getPriceHistoryChart(Property $property, int $months = 12): array
    {
        $history = PropertyHistory::where('property_id', $property->id)
            ->where('event_type', 'price_change')
            ->where('created_at', '>=', Carbon::now()->subMonths($months))
            ->orderBy('created_at', 'asc')
            ->get();

        $chartData = [
            'labels' => [],
            'prices' => [],
            'dates' => [],
        ];

        // Add initial price if property has history
        if ($history->count() > 0) {
            $firstRecord = $history->first();
            if ($firstRecord->old_price) {
                $chartData['labels'][] = Carbon::parse($firstRecord->created_at)->format('M Y');
                $chartData['prices'][] = (float) $firstRecord->old_price;
                $chartData['dates'][] = $firstRecord->created_at->toDateString();
            }
        }

        // Add all price changes
        foreach ($history as $record) {
            if ($record->new_price) {
                $chartData['labels'][] = Carbon::parse($record->created_at)->format('M Y');
                $chartData['prices'][] = (float) $record->new_price;
                $chartData['dates'][] = $record->created_at->toDateString();
            }
        }

        // Add current price if not in history
        if (empty($chartData['prices']) || end($chartData['prices']) != $property->price) {
            $chartData['labels'][] = 'Current';
            $chartData['prices'][] = (float) $property->price;
            $chartData['dates'][] = Carbon::now()->toDateString();
        }

        return [
            'chart_data' => $chartData,
            'price_change' => $this->calculatePriceChange($property, $history),
            'price_per_sqft_trend' => $this->calculatePricePerSqftTrend($property, $history),
        ];
    }

    /**
     * Calculate price change percentage
     */
    protected function calculatePriceChange(Property $property, $history): array
    {
        if ($history->isEmpty()) {
            return [
                'percentage' => 0,
                'amount' => 0,
                'direction' => 'stable',
            ];
        }

        $firstPrice = $history->first()->old_price ?? $property->price;
        $currentPrice = $property->price;
        $change = $currentPrice - $firstPrice;
        $percentage = $firstPrice > 0 ? ($change / $firstPrice) * 100 : 0;

        return [
            'percentage' => round($percentage, 2),
            'amount' => $change,
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Calculate price per square foot trend
     */
    protected function calculatePricePerSqftTrend(Property $property, $history): array
    {
        if (!$property->square || $property->square <= 0) {
            return [];
        }

        $trend = [];
        foreach ($history as $record) {
            if ($record->new_price) {
                $pricePerSqft = $record->new_price / $property->square;
                $trend[] = [
                    'date' => $record->created_at->toDateString(),
                    'price_per_sqft' => round($pricePerSqft, 2),
                ];
            }
        }

        // Add current
        $trend[] = [
            'date' => Carbon::now()->toDateString(),
            'price_per_sqft' => round($property->price / $property->square, 2),
        ];

        return $trend;
    }

    /**
     * Get neighborhood price trends
     */
    public function getNeighborhoodPriceTrends(string $city, string $neighborhood = null, int $months = 12): array
    {
        $query = Property::where('city_id', function ($q) use ($city) {
            $q->select('id')
                ->from('cities')
                ->where('name', 'like', "%{$city}%");
        })
            ->where('type', 'sale')
            ->where('created_at', '>=', Carbon::now()->subMonths($months));

        if ($neighborhood) {
            $query->where('name', 'like', "%{$neighborhood}%");
        }

        $properties = $query->get();

        $monthlyAverages = [];
        for ($i = $months; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthProperties = $properties->filter(function ($p) use ($month) {
                return Carbon::parse($p->created_at)->format('Y-m') === $month->format('Y-m');
            });

            if ($monthProperties->count() > 0) {
                $monthlyAverages[] = [
                    'month' => $month->format('M Y'),
                    'average_price' => round($monthProperties->avg('price'), 2),
                    'median_price' => round($monthProperties->median('price'), 2),
                    'count' => $monthProperties->count(),
                ];
            }
        }

        return $monthlyAverages;
    }
}

