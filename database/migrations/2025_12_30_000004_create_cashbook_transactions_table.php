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
        Schema::createIfNotExists('cashbook_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->enum('category', ['income', 'expense', 'transfer', 'bank_charge', 'interest', 'other']);
            $table->foreignId('journal_entry_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['unmatched', 'matched', 'partially_matched', 'disputed'])->default('unmatched');
            $table->json('matched_with')->nullable(); // Array of matched bank transaction IDs
            $table->decimal('matched_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['account_id', 'transaction_date']);
            $table->index(['school_id', 'status']);
            $table->index(['transaction_date', 'amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbook_transactions');
    }
};
