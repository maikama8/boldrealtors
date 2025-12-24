<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Resources\ListPropertyResource;
use Botble\RealEstate\Http\Resources\PropertyResource;
use Botble\RealEstate\Models\Property;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Http\Request;

class PropertyController extends BaseController
{
    /**
     * List properties
     *
     * @group Real Estate
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam search               Limit results to those matching a string.
     * @queryParam type                 Filter by property type (sale, rent).
     * @queryParam city_id              Filter by city ID.
     * @queryParam state_id             Filter by state ID.
     * @queryParam country_id           Filter by country ID.
     * @queryParam category_id          Filter by category ID.
     * @queryParam project_id           Filter by project ID.
     * @queryParam min_price            Filter by minimum price.
     * @queryParam max_price            Filter by maximum price.
     * @queryParam min_square           Filter by minimum square footage.
     * @queryParam max_square           Filter by maximum square footage.
     * @queryParam number_bedroom       Filter by number of bedrooms.
     * @queryParam number_bathroom      Filter by number of bathrooms.
     * @queryParam number_floor         Filter by number of floors.
     * @queryParam features             Filter by feature IDs (comma-separated).
     * @queryParam facilities           Filter by facility IDs (comma-separated).
     * @queryParam is_featured          Filter by featured properties (1 or 0).
     * @queryParam order                Order sort attribute ascending or descending. Default: desc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: created_at. One of: created_at, updated_at, name, price
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100); // Limit to 100 items per page

        $properties = RealEstateHelper::getPropertiesFilter($perPage);

        return $this
            ->httpResponse()
            ->setData(ListPropertyResource::collection($properties))
            ->toApiResponse();
    }

    /**
     * Search properties
     *
     * @bodyParam q string required The search keyword.
     *
     * @group Real Estate
     */
    public function getSearch(Request $request)
    {
        $query = BaseHelper::stringify($request->input('q'));

        $request->merge(['keyword' => $query]);

        $properties = RealEstateHelper::getPropertiesFilter();

        $data = [
            'items' => ListPropertyResource::collection($properties),
            'query' => $query,
            'count' => $properties->count(),
        ];

        if ($data['count'] > 0) {
            return $this
                ->httpResponse()
                ->setData($data);
        }

        return $this
            ->httpResponse()
            ->setError()
            ->setMessage(trans('core/base::layouts.no_search_result'));
    }

    /**
     * Get property filters
     *
     * @group Real Estate
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam search               Limit results to those matching a string.
     * @queryParam type                 Filter by property type (sale, rent).
     * @queryParam city_id              Filter by city ID.
     * @queryParam state_id             Filter by state ID.
     * @queryParam country_id           Filter by country ID.
     * @queryParam category_id          Filter by category ID.
     * @queryParam project_id           Filter by project ID.
     * @queryParam min_price            Filter by minimum price.
     * @queryParam max_price            Filter by maximum price.
     * @queryParam min_square           Filter by minimum square footage.
     * @queryParam max_square           Filter by maximum square footage.
     * @queryParam number_bedroom       Filter by number of bedrooms.
     * @queryParam number_bathroom      Filter by number of bathrooms.
     * @queryParam number_floor         Filter by number of floors.
     * @queryParam features             Filter by feature IDs (comma-separated).
     * @queryParam facilities           Filter by facility IDs (comma-separated).
     * @queryParam is_featured          Filter by featured properties (1 or 0).
     * @queryParam order                Order sort attribute ascending or descending. Default: desc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: created_at. One of: created_at, updated_at, name, price
     */
    public function getFilters(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100); // Limit to 100 items per page

        $properties = RealEstateHelper::getPropertiesFilter($perPage, []);

        return $this
            ->httpResponse()
            ->setData(ListPropertyResource::collection($properties))
            ->toApiResponse();
    }

    /**
     * Get property by slug
     *
     * @group Real Estate
     * @urlParam slug string required The slug of the property.
     */
    public function findBySlug(string $slug)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Property::class));

        if (! $slug) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Property not found');
        }

        $property = Property::query()
            ->where('id', $slug->reference_id)
            ->where(RealEstateHelper::getPropertyDisplayQueryConditions())
            ->with(RealEstateHelper::getPropertyRelationsQuery())
            ->first();

        if (! $property) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Property not found');
        }

        return $this
            ->httpResponse()
            ->setData(new PropertyResource($property))
            ->toApiResponse();
    }

    /**
     * Get property by ID
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the property.
     */
    public function show(int $id)
    {
        $property = Property::query()
            ->where('id', $id)
            ->where(RealEstateHelper::getPropertyDisplayQueryConditions())
            ->with(RealEstateHelper::getPropertyRelationsQuery())
            ->first();

        if (! $property) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Property not found');
        }

        return $this
            ->httpResponse()
            ->setData(new PropertyResource($property))
            ->toApiResponse();
    }
}
