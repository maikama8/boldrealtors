<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_property_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('re_properties')->onDelete('cascade');
            $table->foreignId('shared_by_account_id')->nullable()->constrained('re_accounts')->onDelete('set null');
            $table->foreignId('shared_with_account_id')->nullable()->constrained('re_accounts')->onDelete('set null');
            $table->string('share_token')->unique(); // For public sharing
            $table->string('share_type')->default('public'); // public, private, email
            $table->string('shared_with_email')->nullable();
            $table->text('message')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('property_id');
            $table->index('shared_by_account_id');
            $table->index('share_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_shares');
    }
};

