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
        Schema::create('interbank_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('transfer_number')->unique();
            $table->date('transfer_date');
            $table->foreignId('from_bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->foreignId('to_bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['school_id', 'transfer_date']);
            $table->index(['from_bank_account_id']);
            $table->index(['to_bank_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interbank_transfers');
    }
};
