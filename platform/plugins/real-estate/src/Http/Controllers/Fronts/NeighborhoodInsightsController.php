<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\NeighborhoodInsightsService;
use Illuminate\Http\Request;

class NeighborhoodInsightsController extends BaseController
{
    public function getInsights($id, Request $request): BaseHttpResponse
    {
        $property = Property::findOrFail($id);
        
        $service = app(NeighborhoodInsightsService::class);
        $insights = $service->getInsights($property);

        return $this->httpResponse()
            ->setData([
                'property_id' => $property->id,
                'insights' => $insights,
            ]);
    }
}

