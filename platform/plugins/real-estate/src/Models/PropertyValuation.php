<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyValuation extends BaseModel
{
    protected $table = 're_property_valuations';

    protected $fillable = [
        'property_id',
        'estimated_value',
        'price_per_square_foot',
        'rent_estimate',
        'valuation_data',
        'last_calculated_at',
    ];

    protected $casts = [
        'estimated_value' => 'float',
        'price_per_square_foot' => 'float',
        'rent_estimate' => 'float',
        'valuation_data' => 'array',
        'last_calculated_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

