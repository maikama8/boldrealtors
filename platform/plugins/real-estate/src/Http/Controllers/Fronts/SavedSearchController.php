<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedSearchController extends BaseController
{
    public function store(Request $request): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();

        $savedSearch = SavedSearch::create([
            'account_id' => $account?->id,
            'name' => $request->input('name', 'My Search'),
            'search_type' => $request->input('search_type', 'property'),
            'search_criteria' => $request->except(['name', 'search_type', '_token']),
            'email_alerts' => $request->input('email_alerts', 'none'),
        ]);

        return $this->httpResponse()
            ->setMessage('Search saved successfully!')
            ->setData(['saved_search' => $savedSearch]);
    }

    public function index(): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        
        $savedSearches = SavedSearch::where('account_id', $account?->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->httpResponse()
            ->setData(['saved_searches' => $savedSearches]);
    }

    public function destroy($id): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        
        $savedSearch = SavedSearch::where('id', $id)
            ->where('account_id', $account?->id)
            ->firstOrFail();

        $savedSearch->delete();

        return $this->httpResponse()
            ->setMessage('Saved search deleted successfully!');
    }

    public function update($id, Request $request): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        
        $savedSearch = SavedSearch::where('id', $id)
            ->where('account_id', $account?->id)
            ->firstOrFail();

        $savedSearch->update([
            'name' => $request->input('name', $savedSearch->name),
            'email_alerts' => $request->input('email_alerts', $savedSearch->email_alerts),
        ]);

        return $this->httpResponse()
            ->setMessage('Saved search updated successfully!')
            ->setData(['saved_search' => $savedSearch]);
    }
}

