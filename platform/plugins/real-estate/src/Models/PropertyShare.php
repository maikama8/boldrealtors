<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PropertyShare extends BaseModel
{
    protected $table = 're_property_shares';

    protected $fillable = [
        'property_id',
        'shared_by_account_id',
        'shared_with_account_id',
        'share_token',
        'share_type',
        'shared_with_email',
        'message',
        'view_count',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($share) {
            if (!$share->share_token) {
                $share->share_token = Str::random(32);
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'shared_by_account_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'shared_with_account_id');
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

