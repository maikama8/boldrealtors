<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyMedia;

class Property3DTourService
{
    /**
     * Supported 3D tour providers
     */
    protected array $providers = [
        'matterport' => [
            'name' => 'Matterport',
            'embed_url_pattern' => 'https://my.matterport.com/show/?m={tour_id}',
        ],
        'iguide' => [
            'name' => 'iGuide',
            'embed_url_pattern' => 'https://my.iguide.com/tours/{tour_id}',
        ],
        'custom' => [
            'name' => 'Custom/Other',
            'embed_url_pattern' => null,
        ],
    ];

    /**
     * Add 3D tour to property
     */
    public function addTour(Property $property, string $provider, string $tourId, ?string $customUrl = null): PropertyMedia
    {
        if (!isset($this->providers[$provider])) {
            throw new \Exception("Unknown 3D tour provider: {$provider}");
        }

        $embedUrl = $this->getEmbedUrl($provider, $tourId, $customUrl);

        return PropertyMedia::create([
            'property_id' => $property->id,
            'media_type' => 'virtual_tour',
            'file_path' => $embedUrl,
            'description' => "3D Tour - {$this->providers[$provider]['name']}",
            'metadata' => [
                'provider' => $provider,
                'tour_id' => $tourId,
                'embed_url' => $embedUrl,
            ],
        ]);
    }

    /**
     * Get embed URL for tour
     */
    protected function getEmbedUrl(string $provider, string $tourId, ?string $customUrl = null): string
    {
        if ($provider === 'custom' && $customUrl) {
            return $customUrl;
        }

        $pattern = $this->providers[$provider]['embed_url_pattern'] ?? null;
        
        if (!$pattern) {
            throw new \Exception("No embed URL pattern for provider: {$provider}");
        }

        return str_replace('{tour_id}', $tourId, $pattern);
    }

    /**
     * Get 3D tours for a property
     */
    public function getTours(Property $property): \Illuminate\Support\Collection
    {
        return PropertyMedia::where('property_id', $property->id)
            ->where('media_type', 'virtual_tour')
            ->orderBy('order')
            ->get();
    }

    /**
     * Get supported providers
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}

