<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\PriceHistoryService;
use Illuminate\Http\Request;

class PriceHistoryController extends BaseController
{
    public function getPropertyPriceHistory($id, Request $request): BaseHttpResponse
    {
        $property = Property::findOrFail($id);
        $months = $request->input('months', 12);

        $service = app(PriceHistoryService::class);
        $data = $service->getPriceHistoryChart($property, $months);

        return $this->httpResponse()
            ->setData($data);
    }

    public function getNeighborhoodTrends(Request $request): BaseHttpResponse
    {
        $request->validate([
            'city' => 'required|string',
            'neighborhood' => 'nullable|string',
            'months' => 'nullable|integer|min:1|max:24',
        ]);

        $service = app(PriceHistoryService::class);
        $trends = $service->getNeighborhoodPriceTrends(
            $request->input('city'),
            $request->input('neighborhood'),
            $request->input('months', 12)
        );

        return $this->httpResponse()
            ->setData(['trends' => $trends]);
    }
}

