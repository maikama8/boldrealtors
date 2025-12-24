<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_property_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('re_properties')->onDelete('cascade');
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->decimal('price_per_square_foot', 10, 2)->nullable();
            $table->decimal('rent_estimate', 15, 2)->nullable();
            $table->json('valuation_data')->nullable(); // Store additional valuation metrics
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_valuations');
    }
};

