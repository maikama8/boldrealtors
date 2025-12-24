<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\RentalApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RentalApplicationController extends BaseController
{
    public function submit(Request $request): BaseHttpResponse
    {
        $request->validate([
            'property_id' => 'required|exists:re_properties,id',
            'applicant_name' => 'required|string|max:255',
            'applicant_email' => 'required|email|max:255',
            'applicant_phone' => 'required|string|max:20',
            'desired_move_in_date' => 'nullable|date|after:today',
            'application_data' => 'required|array',
            'documents' => 'nullable|array',
        ]);

        $account = Auth::guard('account')->user();

        // Check if property is available for rent
        $property = Property::findOrFail($request->input('property_id'));
        if ($property->type->getValue() !== 'renting') {
            return $this->httpResponse()
                ->setError()
                ->setMessage('This property is not available for rent.');
        }

        $application = RentalApplication::create([
            'property_id' => $request->input('property_id'),
            'account_id' => $account?->id,
            'applicant_name' => $request->input('applicant_name'),
            'applicant_email' => $request->input('applicant_email'),
            'applicant_phone' => $request->input('applicant_phone'),
            'desired_move_in_date' => $request->input('desired_move_in_date') ? 
                \Carbon\Carbon::parse($request->input('desired_move_in_date')) : null,
            'application_data' => $request->input('application_data'),
            'documents' => $request->input('documents', []),
            'status' => 'pending',
        ]);

        // Send notification to landlord
        // TODO: Implement notification system

        return $this->httpResponse()
            ->setMessage('Rental application submitted successfully! The landlord will review your application.')
            ->setData(['application' => $application]);
    }

    public function index(Request $request): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        
        $applications = RentalApplication::query()
            ->with(['property', 'account'])
            ->when($account, function ($query) use ($account) {
                // If user is property owner, show applications for their properties
                $query->whereHas('property', function ($q) use ($account) {
                    $q->where('author_id', $account->id)
                      ->where('author_type', get_class($account));
                });
            }, function ($query) use ($account) {
                // Otherwise show applications submitted by user
                $query->where('account_id', $account?->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->httpResponse()
            ->setData(['applications' => $applications]);
    }

    public function updateStatus($id, Request $request): BaseHttpResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'under_review', 'approved', 'rejected'])],
            'landlord_notes' => 'nullable|string|max:1000',
        ]);

        $account = Auth::guard('account')->user();
        
        $application = RentalApplication::where('id', $id)
            ->whereHas('property', function ($query) use ($account) {
                $query->where('author_id', $account->id)
                      ->where('author_type', get_class($account));
            })
            ->firstOrFail();

        $application->update([
            'status' => $request->input('status'),
            'landlord_notes' => $request->input('landlord_notes'),
        ]);

        // Send notification to applicant
        // TODO: Implement notification system

        return $this->httpResponse()
            ->setMessage('Application status updated successfully!')
            ->setData(['application' => $application]);
    }
}

