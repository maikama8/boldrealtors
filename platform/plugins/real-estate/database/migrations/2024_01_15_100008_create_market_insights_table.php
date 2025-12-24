<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_market_insights', function (Blueprint $table) {
            $table->id();
            $table->string('location_type'); // city, state, neighborhood, country
            $table->string('location_id'); // ID of the location
            $table->string('location_name');
            $table->date('insight_date');
            $table->decimal('average_price', 15, 2)->nullable();
            $table->decimal('median_price', 15, 2)->nullable();
            $table->decimal('price_per_square_foot', 10, 2)->nullable();
            $table->integer('total_listings')->default(0);
            $table->integer('new_listings')->default(0);
            $table->integer('sold_listings')->default(0);
            $table->decimal('average_days_on_market', 5, 1)->nullable();
            $table->decimal('price_change_percentage', 5, 2)->nullable(); // Month over month
            $table->json('trend_data')->nullable(); // Historical trend data
            $table->json('forecast_data')->nullable(); // Future predictions
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('insight_date');
            $table->unique(['location_type', 'location_id', 'insight_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_market_insights');
    }
};

