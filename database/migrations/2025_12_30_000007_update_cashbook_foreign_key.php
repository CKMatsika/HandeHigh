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
        Schema::table('cashbook', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['bank_account_id']);
            
            // Rename the column from bank_account_id to account_id
            $table->renameColumn('bank_account_id', 'account_id');
            
            // Add new foreign key pointing to accounts table
            $table->foreign('account_id')
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
        Schema::table('cashbook', function (Blueprint $table) {
            // Drop the new foreign key
            $table->dropForeign(['account_id']);
            
            // Rename back to bank_account_id
            $table->renameColumn('account_id', 'bank_account_id');
            
            // Restore the original foreign key pointing to bank_accounts table
            $table->foreign('bank_account_id')
                  ->references('id')
                  ->on('bank_accounts')
                  ->onDelete('cascade');
        });
    }
};
