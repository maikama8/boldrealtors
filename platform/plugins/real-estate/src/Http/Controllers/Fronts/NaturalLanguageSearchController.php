<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Services\NaturalLanguageSearchService;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class NaturalLanguageSearchController extends BaseController
{
    public function search(Request $request): BaseHttpResponse
    {
        $request->validate([
            'query' => 'required|string|min:3|max:500',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $service = app(NaturalLanguageSearchService::class);
        $perPage = $request->integer('per_page', 20);
        
        $properties = $service->search($request->input('query'), $perPage);
        $criteria = $service->parseQuery($request->input('query'));

        if ($request->expectsJson()) {
            return $this->httpResponse()
                ->setData([
                    'properties' => $properties,
                    'parsed_criteria' => $criteria,
                    'query' => $request->input('query'),
                ]);
        }

        return Theme::scope('real-estate.properties', [
            'properties' => $properties,
            'search_query' => $request->input('query'),
            'parsed_criteria' => $criteria,
        ], 'plugins/real-estate::themes.properties')->render();
    }
}

