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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'mopse_receipt_number')) {
                $table->string('mopse_receipt_number', 50)->nullable()->index()->after('reference');
            }
            if (!Schema::hasColumn('payments', 'is_advance')) {
                $table->boolean('is_advance')->default(false)->after('status');
            }
            if (!Schema::hasColumn('payments', 'advance_balance')) {
                $table->decimal('advance_balance', 12, 2)->default(0.00)->after('is_advance');
            }
            if (!Schema::hasColumn('payments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('account_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'mopse_receipt_number')) {
                $table->dropColumn('mopse_receipt_number');
            }
            if (Schema::hasColumn('payments', 'is_advance')) {
                $table->dropColumn('is_advance');
            }
            if (Schema::hasColumn('payments', 'advance_balance')) {
                $table->dropColumn('advance_balance');
            }
            if (Schema::hasColumn('payments', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};
