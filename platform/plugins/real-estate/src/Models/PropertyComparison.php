<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyComparison extends BaseModel
{
    protected $table = 're_property_comparisons';

    protected $fillable = [
        'account_id',
        'session_id',
        'property_ids',
    ];

    protected $casts = [
        'property_ids' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

