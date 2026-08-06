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
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_import_id')->constrained()->onDelete('cascade');
            $table->date('transaction_date');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('balance', 15, 2)->nullable();
            $table->string('reference')->nullable();
            $table->string('fit_id')->nullable(); // Financial Institution Transaction ID
            $table->string('check_number')->nullable();
            $table->enum('reconciliation_status', ['unmatched', 'matched', 'reconciled'])->default('unmatched');
            $table->foreignId('matched_transaction_id')->nullable()->constrained('cashbook')->onDelete('set null');
            $table->foreignId('matched_journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['bank_statement_import_id']);
            $table->index(['reconciliation_status']);
            $table->index(['transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
