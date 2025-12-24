<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\PropertyShare;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class PropertyShareController extends BaseController
{
    public function share(Request $request): BaseHttpResponse
    {
        $request->validate([
            'property_id' => 'required|exists:re_properties,id',
            'share_type' => ['required', 'in:public,private,email'],
            'shared_with_email' => 'required_if:share_type,email|email',
            'message' => 'nullable|string|max:500',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $account = Auth::guard('account')->user();
        $property = Property::findOrFail($request->input('property_id'));

        $expiresAt = null;
        if ($request->filled('expires_in_days')) {
            $expiresAt = Carbon::now()->addDays($request->input('expires_in_days'));
        }

        $share = PropertyShare::create([
            'property_id' => $property->id,
            'shared_by_account_id' => $account?->id,
            'share_type' => $request->input('share_type'),
            'shared_with_email' => $request->input('shared_with_email'),
            'message' => $request->input('message'),
            'expires_at' => $expiresAt,
        ]);

        // Send email if share type is email
        if ($request->input('share_type') === 'email' && $request->filled('shared_with_email')) {
            $this->sendShareEmail($share, $property);
        }

        $shareUrl = route('public.property.shared', $share->share_token);

        return $this->httpResponse()
            ->setMessage('Property shared successfully!')
            ->setData([
                'share' => $share,
                'share_url' => $shareUrl,
            ]);
    }

    public function viewShared(string $token)
    {
        $share = PropertyShare::where('share_token', $token)->firstOrFail();

        // Check if expired
        if ($share->isExpired()) {
            abort(410, 'This shared link has expired.');
        }

        // Increment view count
        $share->incrementViewCount();

        $property = $share->property;

        return Theme::scope('real-estate.property', [
            'property' => $property,
            'shared' => true,
            'share_message' => $share->message,
        ], 'plugins/real-estate::themes.property')->render();
    }

    public function getShareStats($id): BaseHttpResponse
    {
        $account = Auth::guard('account')->user();
        
        $share = PropertyShare::where('id', $id)
            ->where('shared_by_account_id', $account?->id)
            ->firstOrFail();

        return $this->httpResponse()
            ->setData([
                'share' => $share,
                'total_views' => $share->view_count,
                'is_expired' => $share->isExpired(),
            ]);
    }

    protected function sendShareEmail(PropertyShare $share, Property $property): void
    {
        // TODO: Implement email sending
        // Mail::to($share->shared_with_email)->send(new PropertyShareMail($share, $property));
    }
}

