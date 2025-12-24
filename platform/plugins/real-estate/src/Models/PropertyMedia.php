<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyMedia extends BaseModel
{
    protected $table = 're_property_media';

    protected $fillable = [
        'property_id',
        'media_type',
        'file_path',
        'thumbnail_path',
        'description',
        'order',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_primary' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

