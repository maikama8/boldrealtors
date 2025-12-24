<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Services\MarketInsightsService;
use Illuminate\Http\Request;

class MarketInsightsController extends BaseController
{
    public function getInsights(Request $request): BaseHttpResponse
    {
        $request->validate([
            'location_type' => ['required', 'in:city,state,country,neighborhood'],
            'location_id' => 'required|string',
            'location_name' => 'required|string',
            'generate' => 'nullable|boolean',
        ]);

        $service = app(MarketInsightsService::class);

        try {
            if ($request->boolean('generate')) {
                $insight = $service->generateInsights(
                    $request->input('location_type'),
                    $request->input('location_id'),
                    $request->input('location_name')
                );
            } else {
                $insight = $service->getInsights(
                    $request->input('location_type'),
                    $request->input('location_id')
                );
            }

            if (!$insight) {
                return $this->httpResponse()
                    ->setError()
                    ->setMessage('No market insights available for this location.');
            }

            return $this->httpResponse()
                ->setData(['insight' => $insight]);
        } catch (\Exception $e) {
            return $this->httpResponse()
                ->setError()
                ->setMessage($e->getMessage());
        }
    }
}

