<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyTour extends BaseModel
{
    protected $table = 're_property_tours';

    protected $fillable = [
        'property_id',
        'account_id',
        'tour_type',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'message',
        'preferred_date_time',
        'status',
        'agent_notes',
    ];

    protected $casts = [
        'preferred_date_time' => 'datetime',
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

