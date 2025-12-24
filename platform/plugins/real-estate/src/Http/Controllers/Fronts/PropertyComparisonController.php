<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyComparison;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PropertyComparisonController extends BaseController
{
    public function add(Request $request): BaseHttpResponse
    {
        $propertyId = $request->input('property_id');
        $account = Auth::guard('account')->user();
        $sessionId = Session::getId();

        // Get or create comparison
        $comparison = PropertyComparison::firstOrCreate(
            [
                'account_id' => $account?->id,
                'session_id' => $account ? null : $sessionId,
            ],
            [
                'property_ids' => [],
            ]
        );

        $propertyIds = $comparison->property_ids ?? [];
        
        // Limit to 4 properties max
        if (count($propertyIds) >= 4) {
            return $this->httpResponse()
                ->setError()
                ->setMessage('You can compare up to 4 properties at a time.');
        }

        if (!in_array($propertyId, $propertyIds)) {
            $propertyIds[] = $propertyId;
            $comparison->update(['property_ids' => $propertyIds]);
        }

        return $this->httpResponse()
            ->setMessage('Property added to comparison!')
            ->setData([
                'comparison' => $comparison,
                'count' => count($propertyIds),
            ]);
    }

    public function remove(Request $request): BaseHttpResponse
    {
        $propertyId = $request->input('property_id');
        $account = Auth::guard('account')->user();
        $sessionId = Session::getId();

        $comparison = PropertyComparison::where(function ($query) use ($account, $sessionId) {
            if ($account) {
                $query->where('account_id', $account->id);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        if ($comparison) {
            $propertyIds = array_values(array_filter(
                $comparison->property_ids ?? [],
                fn($id) => $id != $propertyId
            ));

            if (empty($propertyIds)) {
                $comparison->delete();
            } else {
                $comparison->update(['property_ids' => $propertyIds]);
            }
        }

        return $this->httpResponse()
            ->setMessage('Property removed from comparison!');
    }

    public function index(): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        $sessionId = Session::getId();

        $comparison = PropertyComparison::where(function ($query) use ($account, $sessionId) {
            if ($account) {
                $query->where('account_id', $account->id);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->first();

        $properties = collect();
        if ($comparison && !empty($comparison->property_ids)) {
            $properties = Property::whereIn('id', $comparison->property_ids)
                ->with(['city', 'state', 'country', 'currency', 'features', 'facilities'])
                ->get()
                ->sortBy(function ($property) use ($comparison) {
                    return array_search($property->id, $comparison->property_ids);
                });
        }

        return $this->httpResponse()
            ->setData([
                'properties' => $properties,
                'count' => $properties->count(),
            ]);
    }

    public function clear(): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        $sessionId = Session::getId();

        PropertyComparison::where(function ($query) use ($account, $sessionId) {
            if ($account) {
                $query->where('account_id', $account->id);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->delete();

        return $this->httpResponse()
            ->setMessage('Comparison cleared!');
    }
}

