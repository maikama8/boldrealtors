<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Collection;

class MapSearchService
{
    /**
     * Get properties within a bounding box
     */
    public function searchByBounds(
        float $north,
        float $south,
        float $east,
        float $west,
        array $filters = []
    ): Collection {
        $query = Property::query()
            ->where('moderation_status', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east]);

        // Apply additional filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['bedrooms'])) {
            $query->where('number_bedroom', '>=', $filters['bedrooms']);
        }

        if (isset($filters['bathrooms'])) {
            $query->where('number_bathroom', '>=', $filters['bathrooms']);
        }

        return $query->with(['city', 'state', 'currency'])
            ->get()
            ->map(function ($property) {
                return [
                    'id' => $property->id,
                    'name' => $property->name,
                    'price' => $property->price,
                    'price_formatted' => $property->price_format,
                    'latitude' => $property->latitude,
                    'longitude' => $property->longitude,
                    'image' => $property->image,
                    'type' => $property->type->getValue(),
                    'status' => $property->status->getValue(),
                    'bedrooms' => $property->number_bedroom,
                    'bathrooms' => $property->number_bathroom,
                    'square' => $property->square,
                    'url' => $property->url,
                    'location' => $property->location,
                ];
            });
    }

    /**
     * Get properties within a radius of a point
     */
    public function searchByRadius(
        float $latitude,
        float $longitude,
        float $radiusKm,
        array $filters = []
    ): Collection {
        // Using Haversine formula for distance calculation
        $query = Property::query()
            ->where('moderation_status', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('*, (
                6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance', [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');

        // Apply additional filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->with(['city', 'state', 'currency'])
            ->get()
            ->map(function ($property) {
                return [
                    'id' => $property->id,
                    'name' => $property->name,
                    'price' => $property->price,
                    'price_formatted' => $property->price_format,
                    'latitude' => $property->latitude,
                    'longitude' => $property->longitude,
                    'distance' => round($property->distance, 2),
                    'image' => $property->image,
                    'type' => $property->type->getValue(),
                    'status' => $property->status->getValue(),
                    'bedrooms' => $property->number_bedroom,
                    'bathrooms' => $property->number_bathroom,
                    'square' => $property->square,
                    'url' => $property->url,
                    'location' => $property->location,
                ];
            });
    }

    /**
     * Get properties within a custom drawn polygon
     */
    public function searchByPolygon(array $coordinates, array $filters = []): Collection
    {
        // Coordinates should be array of [lat, lng] pairs
        if (empty($coordinates) || count($coordinates) < 3) {
            return collect();
        }

        // For polygon search, we'll use a bounding box first, then filter by point-in-polygon
        $lats = array_column($coordinates, 0);
        $lngs = array_column($coordinates, 1);

        $north = max($lats);
        $south = min($lats);
        $east = max($lngs);
        $west = min($lngs);

        $properties = $this->searchByBounds($north, $south, $east, $west, $filters);

        // Filter by point-in-polygon algorithm
        return $properties->filter(function ($property) use ($coordinates) {
            return $this->pointInPolygon(
                $property['latitude'],
                $property['longitude'],
                $coordinates
            );
        });
    }

    /**
     * Point-in-polygon algorithm (Ray casting algorithm)
     */
    protected function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $lng) != ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }

            $j = $i;
        }

        return $inside;
    }

    /**
     * Get cluster data for map markers (for performance with many properties)
     */
    public function getClusters(
        float $north,
        float $south,
        float $east,
        float $west,
        int $zoomLevel = 10
    ): array {
        $properties = $this->searchByBounds($north, $south, $east, $west);

        // Determine cluster size based on zoom level
        $clusterSize = $zoomLevel < 10 ? 0.1 : ($zoomLevel < 13 ? 0.05 : 0.01);

        $clusters = [];
        $used = [];

        foreach ($properties as $index => $property) {
            if (isset($used[$index])) {
                continue;
            }

            $cluster = [
                'properties' => [$property],
                'center' => [
                    'lat' => $property['latitude'],
                    'lng' => $property['longitude'],
                ],
                'count' => 1,
            ];

            // Find nearby properties to cluster
            foreach ($properties as $otherIndex => $otherProperty) {
                if ($index === $otherIndex || isset($used[$otherIndex])) {
                    continue;
                }

                $distance = $this->calculateDistance(
                    $property['latitude'],
                    $property['longitude'],
                    $otherProperty['latitude'],
                    $otherProperty['longitude']
                );

                if ($distance <= $clusterSize) {
                    $cluster['properties'][] = $otherProperty;
                    $used[$otherIndex] = true;
                    $cluster['count']++;
                }
            }

            $used[$index] = true;

            // Calculate cluster center
            if ($cluster['count'] > 1) {
                $avgLat = array_sum(array_column($cluster['properties'], 'latitude')) / $cluster['count'];
                $avgLng = array_sum(array_column($cluster['properties'], 'longitude')) / $cluster['count'];
                $cluster['center'] = ['lat' => $avgLat, 'lng' => $avgLng];
            }

            $clusters[] = $cluster;
        }

        return $clusters;
    }

    /**
     * Calculate distance between two points (Haversine)
     */
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

