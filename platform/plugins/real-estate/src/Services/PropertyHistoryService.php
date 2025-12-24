<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyHistory;

class PropertyHistoryService
{
    /**
     * Track price change
     */
    public function trackPriceChange(Property $property, ?float $oldPrice, float $newPrice): void
    {
        if ($oldPrice !== null && $oldPrice != $newPrice) {
            PropertyHistory::create([
                'property_id' => $property->id,
                'event_type' => 'price_change',
                'old_value' => $oldPrice,
                'new_value' => $newPrice,
                'event_date' => now(),
            ]);
        }
    }

    /**
     * Track status change
     */
    public function trackStatusChange(Property $property, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus != $newStatus) {
            PropertyHistory::create([
                'property_id' => $property->id,
                'event_type' => 'status_change',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'event_date' => now(),
            ]);
        }
    }

    /**
     * Track listing added
     */
    public function trackListingAdded(Property $property): void
    {
        PropertyHistory::create([
            'property_id' => $property->id,
            'event_type' => 'listing_added',
            'new_value' => $property->price,
            'new_status' => $property->status->getValue(),
            'event_date' => $property->created_at ?? now(),
        ]);
    }

    /**
     * Track listing removed
     */
    public function trackListingRemoved(Property $property, string $reason = null): void
    {
        PropertyHistory::create([
            'property_id' => $property->id,
            'event_type' => 'listing_removed',
            'old_status' => $property->status->getValue(),
            'notes' => $reason,
            'event_date' => now(),
        ]);
    }

    /**
     * Get property history
     */
    public function getHistory(Property $property, int $limit = 50)
    {
        return PropertyHistory::where('property_id', $property->id)
            ->orderBy('event_date', 'desc')
            ->limit($limit)
            ->get();
    }
}

