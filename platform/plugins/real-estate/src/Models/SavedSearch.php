<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends BaseModel
{
    protected $table = 're_saved_searches';

    protected $fillable = [
        'account_id',
        'name',
        'search_type',
        'search_criteria',
        'email_alerts',
        'result_count',
    ];

    protected $casts = [
        'search_criteria' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

