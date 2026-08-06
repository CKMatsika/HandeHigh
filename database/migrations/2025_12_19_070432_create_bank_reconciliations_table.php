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
        Schema::createIfNotExists('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('cascade');
            $table->date('reconciliation_date');
            $table->decimal('book_balance', 15, 2);
            $table->decimal('bank_balance', 15, 2);
            $table->decimal('reconciled_balance', 15, 2);
            $table->json('matched_items')->nullable(); // IDs of matched transactions
            $table->json('unmatched_items')->nullable(); // IDs of unmatched items
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['bank_account_id', 'reconciliation_date']);
            $table->index(['school_id', 'reconciliation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
