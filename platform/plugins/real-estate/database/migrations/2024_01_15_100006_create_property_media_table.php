<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_property_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('re_properties')->onDelete('cascade');
            $table->string('media_type'); // photo, video, virtual_tour, floor_plan, sky_tour, drone
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable(); // Store additional data (e.g., tour URL, floor plan data)
            $table->timestamps();

            $table->index('property_id');
            $table->index('media_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_media');
    }
};

