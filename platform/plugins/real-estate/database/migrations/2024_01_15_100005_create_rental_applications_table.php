<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_rental_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('re_properties')->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('re_accounts')->onDelete('set null');
            $table->string('applicant_name');
            $table->string('applicant_email');
            $table->string('applicant_phone');
            $table->date('desired_move_in_date')->nullable();
            $table->json('application_data'); // Store all application form data
            $table->json('documents')->nullable(); // IDs of uploaded documents
            $table->string('status')->default('pending'); // pending, under_review, approved, rejected
            $table->text('landlord_notes')->nullable();
            $table->timestamps();

            $table->index('property_id');
            $table->index('account_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_rental_applications');
    }
};

