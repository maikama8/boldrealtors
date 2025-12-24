<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentPayment extends BaseModel
{
    protected $table = 're_rent_payments';

    protected $fillable = [
        'property_id',
        'account_id',
        'rental_application_id',
        'payment_date',
        'due_date',
        'amount',
        'currency',
        'payment_method',
        'payment_reference',
        'status',
        'reported_to_credit_bureau',
        'reported_at',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
        'amount' => 'float',
        'reported_to_credit_bureau' => 'boolean',
        'reported_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function rentalApplication(): BelongsTo
    {
        return $this->belongsTo(RentalApplication::class);
    }
}

