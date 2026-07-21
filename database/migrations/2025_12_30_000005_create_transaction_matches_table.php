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
        Schema::create('transaction_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('cashbook_transaction_id')->constrained()->onDelete('cascade');
            $table->decimal('match_amount', 15, 2);
            $table->enum('match_type', ['exact', 'partial', 'manual', 'auto'])->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['bank_transaction_id', 'cashbook_transaction_id']);
            $table->index(['bank_transaction_id', 'match_amount']);
            $table->index(['cashbook_transaction_id', 'match_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_matches');
    }
};
