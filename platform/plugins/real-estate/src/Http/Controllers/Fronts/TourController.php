<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyTour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TourController extends BaseController
{
    public function schedule(Request $request): BaseHttpResponse
    {
        $request->validate([
            'property_id' => 'required|exists:re_properties,id',
            'tour_type' => ['required', Rule::in(['in_person', 'self_tour', 'virtual'])],
            'visitor_name' => 'required|string|max:255',
            'visitor_email' => 'required|email|max:255',
            'visitor_phone' => 'nullable|string|max:20',
            'message' => 'nullable|string|max:1000',
            'preferred_date_time' => 'nullable|date|after:now',
        ]);

        $account = Auth::guard('account')->user();

        $tour = PropertyTour::create([
            'property_id' => $request->input('property_id'),
            'account_id' => $account?->id,
            'tour_type' => $request->input('tour_type'),
            'visitor_name' => $request->input('visitor_name'),
            'visitor_email' => $request->input('visitor_email'),
            'visitor_phone' => $request->input('visitor_phone'),
            'message' => $request->input('message'),
            'preferred_date_time' => $request->input('preferred_date_time') ? 
                \Carbon\Carbon::parse($request->input('preferred_date_time')) : null,
            'status' => 'pending',
        ]);

        // Send notification to property owner/agent
        // TODO: Implement notification system

        return $this->httpResponse()
            ->setMessage('Tour request submitted successfully! The property owner will contact you soon.')
            ->setData(['tour' => $tour]);
    }

    public function index(Request $request): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        
        $tours = PropertyTour::query()
            ->with(['property', 'account'])
            ->when($account, function ($query) use ($account) {
                // If user is property owner, show tours for their properties
                $query->whereHas('property', function ($q) use ($account) {
                    $q->where('author_id', $account->id)
                      ->where('author_type', get_class($account));
                });
            }, function ($query) use ($account) {
                // Otherwise show tours requested by user
                $query->where('account_id', $account?->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->httpResponse()
            ->setData(['tours' => $tours]);
    }

    public function updateStatus($id, Request $request): BaseHttpResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
            'agent_notes' => 'nullable|string|max:1000',
        ]);

        $account = Auth::guard('account')->user();
        
        $tour = PropertyTour::where('id', $id)
            ->whereHas('property', function ($query) use ($account) {
                $query->where('author_id', $account->id)
                      ->where('author_type', get_class($account));
            })
            ->firstOrFail();

        $tour->update([
            'status' => $request->input('status'),
            'agent_notes' => $request->input('agent_notes'),
        ]);

        return $this->httpResponse()
            ->setMessage('Tour status updated successfully!')
            ->setData(['tour' => $tour]);
    }
}

