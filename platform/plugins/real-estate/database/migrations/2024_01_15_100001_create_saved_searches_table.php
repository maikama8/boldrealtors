<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('re_accounts')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('search_type')->default('property'); // property, project
            $table->json('search_criteria'); // Store all search filters
            $table->string('email_alerts')->default('none'); // none, daily, weekly
            $table->integer('result_count')->default(0);
            $table->timestamps();

            $table->index('account_id');
            $table->index('search_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_saved_searches');
    }
};

