<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_rent_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('re_properties')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('re_accounts')->onDelete('cascade');
            $table->foreignId('rental_application_id')->nullable()->constrained('re_rental_applications')->onDelete('set null');
            $table->date('payment_date');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('payment_method')->nullable(); // bank_transfer, card, cash, etc.
            $table->string('payment_reference')->nullable();
            $table->string('status')->default('pending'); // pending, paid, overdue, partial
            $table->boolean('reported_to_credit_bureau')->default(false);
            $table->timestamp('reported_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('property_id');
            $table->index('account_id');
            $table->index('status');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_rent_payments');
    }
};

