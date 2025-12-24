<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_property_sync_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained('re_properties')->onDelete('cascade');
            $table->string('source_name'); // e.g., propertypro.ng, nigerianpropertycentre.com
            $table->string('source_url');
            $table->string('external_id')->nullable(); // ID from source site
            $table->json('source_data')->nullable(); // Raw data from source
            $table->string('sync_status')->default('pending'); // pending, synced, failed, updated
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_notes')->nullable();
            $table->timestamps();

            $table->index('property_id');
            $table->index('source_name');
            $table->index('external_id');
            $table->unique(['source_name', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_sync_sources');
    }
};

