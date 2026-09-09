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
        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('expense_account_id')->nullable()->after('vendor_id')->constrained('accounts')->nullOnDelete();
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->foreignId('expense_account_id')->nullable()->after('category')->constrained('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropForeign(['expense_account_id']);
            $table->dropColumn('expense_account_id');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['expense_account_id']);
            $table->dropColumn('expense_account_id');
        });
    }
};
