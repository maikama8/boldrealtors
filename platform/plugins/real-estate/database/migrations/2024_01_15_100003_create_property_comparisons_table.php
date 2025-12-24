<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_property_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('re_accounts')->onDelete('cascade');
            $table->string('session_id')->nullable(); // For guest users
            $table->json('property_ids'); // Array of property IDs being compared
            $table->timestamps();

            $table->index('account_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_comparisons');
    }
};

