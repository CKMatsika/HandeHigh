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
        Schema::createIfNotExists('journal_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('batch_number')->unique();
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->text('description');
            $table->enum('source_type', [
                'manual', 'invoice', 'payment', 'bill', 'receipt', 
                'credit_note', 'cashbook', 'bank_transfer', 'bank_reconciliation',
                'adjustment', 'opening_balance'
            ]);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'transaction_date']);
            $table->index(['school_id', 'status']);
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_batches');
    }
};
