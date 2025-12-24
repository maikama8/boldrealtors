<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_property_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('re_properties')->onDelete('cascade');
            $table->string('event_type'); // price_change, status_change, listing_added, listing_removed
            $table->decimal('old_value', 15, 2)->nullable();
            $table->decimal('new_value', 15, 2)->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('event_date');
            $table->timestamps();

            $table->index('property_id');
            $table->index('event_type');
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_history');
    }
};

