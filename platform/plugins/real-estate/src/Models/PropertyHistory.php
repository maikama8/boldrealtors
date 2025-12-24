<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyHistory extends BaseModel
{
    protected $table = 're_property_history';

    protected $fillable = [
        'property_id',
        'event_type',
        'old_value',
        'new_value',
        'old_status',
        'new_status',
        'notes',
        'event_date',
    ];

    protected $casts = [
        'old_value' => 'float',
        'new_value' => 'float',
        'event_date' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

