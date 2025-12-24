<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertySyncSource extends BaseModel
{
    protected $table = 're_property_sync_sources';

    protected $fillable = [
        'property_id',
        'source_name',
        'source_url',
        'external_id',
        'source_data',
        'sync_status',
        'last_synced_at',
        'sync_notes',
    ];

    protected $casts = [
        'source_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

