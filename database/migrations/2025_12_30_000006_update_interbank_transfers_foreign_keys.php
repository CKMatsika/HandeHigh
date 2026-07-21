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
        Schema::table('interbank_transfers', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['from_bank_account_id']);
            $table->dropForeign(['to_bank_account_id']);
            
            // Add new foreign keys pointing to accounts table
            $table->foreign('from_bank_account_id')
                  ->references('id')
                  ->on('accounts')
                  ->onDelete('cascade');
                  
            $table->foreign('to_bank_account_id')
                  ->references('id')
                  ->on('accounts')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interbank_transfers', function (Blueprint $table) {
            // Drop the new foreign keys
            $table->dropForeign(['from_bank_account_id']);
            $table->dropForeign(['to_bank_account_id']);
            
            // Restore the original foreign keys pointing to bank_accounts table
            $table->foreign('from_bank_account_id')
                  ->references('id')
                  ->on('bank_accounts')
                  ->onDelete('cascade');
                  
            $table->foreign('to_bank_account_id')
                  ->references('id')
                  ->on('bank_accounts')
                  ->onDelete('cascade');
        });
    }
};
