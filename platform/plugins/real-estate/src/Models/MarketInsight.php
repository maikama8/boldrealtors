<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;

class MarketInsight extends BaseModel
{
    protected $table = 're_market_insights';

    protected $fillable = [
        'location_type',
        'location_id',
        'location_name',
        'insight_date',
        'average_price',
        'median_price',
        'price_per_square_foot',
        'total_listings',
        'new_listings',
        'sold_listings',
        'average_days_on_market',
        'price_change_percentage',
        'trend_data',
        'forecast_data',
    ];

    protected $casts = [
        'insight_date' => 'date',
        'average_price' => 'float',
        'median_price' => 'float',
        'price_per_square_foot' => 'float',
        'average_days_on_market' => 'float',
        'price_change_percentage' => 'float',
        'trend_data' => 'array',
        'forecast_data' => 'array',
    ];
}

