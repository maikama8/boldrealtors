<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_property_tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('re_properties')->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('re_accounts')->onDelete('set null');
            $table->string('tour_type')->default('in_person'); // in_person, self_tour, virtual
            $table->string('visitor_name');
            $table->string('visitor_email');
            $table->string('visitor_phone')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('preferred_date_time')->nullable();
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $table->text('agent_notes')->nullable();
            $table->timestamps();

            $table->index('property_id');
            $table->index('account_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_tours');
    }
};

