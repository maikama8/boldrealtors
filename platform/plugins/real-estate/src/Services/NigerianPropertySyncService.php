<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertySyncSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NigerianPropertySyncService
{
    /**
     * List of Nigerian property websites to sync from
     */
    protected array $sources = [
        'propertypro.ng' => [
            'name' => 'PropertyPro',
            'base_url' => 'https://www.propertypro.ng',
            'api_endpoint' => null, // Would need actual API endpoint
        ],
        'nigerianpropertycentre.com' => [
            'name' => 'Nigerian Property Centre',
            'base_url' => 'https://www.nigerianpropertycentre.com',
            'api_endpoint' => null,
        ],
        'tolet.com.ng' => [
            'name' => 'ToLet',
            'base_url' => 'https://www.tolet.com.ng',
            'api_endpoint' => null,
        ],
    ];

    /**
     * Sync properties from a specific source
     */
    public function syncFromSource(string $sourceKey, int $limit = 50): array
    {
        if (!isset($this->sources[$sourceKey])) {
            throw new \Exception("Unknown source: {$sourceKey}");
        }

        $source = $this->sources[$sourceKey];
        $synced = 0;
        $updated = 0;
        $failed = 0;

        try {
            // This is a placeholder - actual implementation would:
            // 1. Scrape or use API to get listings
            // 2. Parse and normalize data
            // 3. Create or update properties
            // 4. Track sync status

            $listings = $this->fetchListings($source, $limit);

            foreach ($listings as $listing) {
                try {
                    $property = $this->createOrUpdateProperty($listing, $sourceKey);
                    
                    if ($property->wasRecentlyCreated) {
                        $synced++;
                    } else {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to sync property from {$sourceKey}: " . $e->getMessage());
                    $failed++;
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync from {$sourceKey}: " . $e->getMessage());
            throw $e;
        }

        return [
            'synced' => $synced,
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($listings ?? []),
        ];
    }

    /**
     * Fetch listings from source (placeholder - needs actual implementation)
     */
    protected function fetchListings(array $source, int $limit): array
    {
        // This would use web scraping or API calls
        // For now, return empty array
        return [];
    }

    /**
     * Create or update property from external listing
     */
    protected function createOrUpdateProperty(array $listingData, string $sourceKey): Property
    {
        // Find existing property by external ID
        $syncSource = PropertySyncSource::where('source_name', $sourceKey)
            ->where('external_id', $listingData['external_id'] ?? null)
            ->first();

        $property = $syncSource?->property;

        // Map external data to our property model
        $propertyData = $this->mapExternalDataToProperty($listingData);

        if ($property) {
            $property->update($propertyData);
        } else {
            $property = Property::create($propertyData);
        }

        // Update sync source record
        PropertySyncSource::updateOrCreate(
            [
                'source_name' => $sourceKey,
                'external_id' => $listingData['external_id'] ?? null,
            ],
            [
                'property_id' => $property->id,
                'source_url' => $listingData['url'] ?? '',
                'source_data' => $listingData,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]
        );

        return $property;
    }

    /**
     * Map external listing data to our property format
     */
    protected function mapExternalDataToProperty(array $listingData): array
    {
        return [
            'name' => $listingData['title'] ?? 'Imported Property',
            'description' => $listingData['description'] ?? '',
            'price' => $this->parsePrice($listingData['price'] ?? 0),
            'type' => $this->mapPropertyType($listingData['type'] ?? 'sale'),
            'number_bedroom' => (int) ($listingData['bedrooms'] ?? 0),
            'number_bathroom' => (int) ($listingData['bathrooms'] ?? 0),
            'square' => (float) ($listingData['square_feet'] ?? 0),
            'location' => $listingData['address'] ?? '',
            'latitude' => $listingData['latitude'] ?? null,
            'longitude' => $listingData['longitude'] ?? null,
            'moderation_status' => 'pending', // Review imported properties
            'status' => 'selling', // Default status
        ];
    }

    /**
     * Parse price from various formats
     */
    protected function parsePrice($price): float
    {
        if (is_numeric($price)) {
            return (float) $price;
        }

        // Remove currency symbols and commas
        $price = preg_replace('/[^0-9.]/', '', $price);
        return (float) $price;
    }

    /**
     * Map external property type to our enum
     */
    protected function mapPropertyType(string $externalType): string
    {
        $typeMap = [
            'sale' => 'selling',
            'rent' => 'renting',
            'lease' => 'renting',
        ];

        return $typeMap[strtolower($externalType)] ?? 'selling';
    }
}

