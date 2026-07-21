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
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['bank_account_id']);
            
            // Add the new foreign key constraint pointing to accounts table
            $table->foreign('bank_account_id')
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
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['bank_account_id']);
            
            // Restore the original foreign key constraint pointing to bank_accounts table
            $table->foreign('bank_account_id')
                  ->references('id')
                  ->on('bank_accounts')
                  ->onDelete('cascade');
        });
    }
};
