<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalApplication extends BaseModel
{
    protected $table = 're_rental_applications';

    protected $fillable = [
        'property_id',
        'account_id',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'desired_move_in_date',
        'application_data',
        'documents',
        'status',
        'landlord_notes',
    ];

    protected $casts = [
        'application_data' => 'array',
        'documents' => 'array',
        'desired_move_in_date' => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

