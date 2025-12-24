<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Services\MapSearchService;
use Illuminate\Http\Request;

class MapSearchController extends BaseController
{
    public function searchByBounds(Request $request): BaseHttpResponse
    {
        $request->validate([
            'north' => 'required|numeric|between:-90,90',
            'south' => 'required|numeric|between:-90,90',
            'east' => 'required|numeric|between:-180,180',
            'west' => 'required|numeric|between:-180,180',
            'filters' => 'nullable|array',
        ]);

        $service = app(MapSearchService::class);
        
        $properties = $service->searchByBounds(
            $request->input('north'),
            $request->input('south'),
            $request->input('east'),
            $request->input('west'),
            $request->input('filters', [])
        );

        return $this->httpResponse()
            ->setData([
                'properties' => $properties,
                'count' => $properties->count(),
            ]);
    }

    public function searchByRadius(Request $request): BaseHttpResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:0.1|max:100',
            'filters' => 'nullable|array',
        ]);

        $service = app(MapSearchService::class);
        
        $properties = $service->searchByRadius(
            $request->input('latitude'),
            $request->input('longitude'),
            $request->input('radius_km'),
            $request->input('filters', [])
        );

        return $this->httpResponse()
            ->setData([
                'properties' => $properties,
                'count' => $properties->count(),
            ]);
    }

    public function searchByPolygon(Request $request): BaseHttpResponse
    {
        $request->validate([
            'coordinates' => 'required|array|min:3',
            'coordinates.*' => 'required|array|size:2',
            'coordinates.*.*' => 'required|numeric',
            'filters' => 'nullable|array',
        ]);

        $service = app(MapSearchService::class);
        
        $properties = $service->searchByPolygon(
            $request->input('coordinates'),
            $request->input('filters', [])
        );

        return $this->httpResponse()
            ->setData([
                'properties' => $properties,
                'count' => $properties->count(),
            ]);
    }

    public function getClusters(Request $request): BaseHttpResponse
    {
        $request->validate([
            'north' => 'required|numeric|between:-90,90',
            'south' => 'required|numeric|between:-90,90',
            'east' => 'required|numeric|between:-180,180',
            'west' => 'required|numeric|between:-180,180',
            'zoom_level' => 'nullable|integer|min:1|max:20',
        ]);

        $service = app(MapSearchService::class);
        
        $clusters = $service->getClusters(
            $request->input('north'),
            $request->input('south'),
            $request->input('east'),
            $request->input('west'),
            $request->integer('zoom_level', 10)
        );

        return $this->httpResponse()
            ->setData([
                'clusters' => $clusters,
                'count' => count($clusters),
            ]);
    }
}

