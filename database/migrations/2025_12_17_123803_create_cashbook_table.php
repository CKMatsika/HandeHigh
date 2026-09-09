<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cashbook', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('set null');
            $table->string('transaction_type'); // income, expense, transfer
            $table->string('category');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable(); // cash, bank, mobile_money, check
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->unsignedBigInteger('related_payment_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'transaction_date']);
            $table->index(['school_id', 'transaction_type']);
            $table->index(['school_id', 'category']);
            $table->index(['bank_account_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbook');
    }
};
