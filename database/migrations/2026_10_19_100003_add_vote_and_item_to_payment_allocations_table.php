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
        Schema::table('payment_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_allocations', 'invoice_item_id')) {
                $table->foreignId('invoice_item_id')->nullable()->after('invoice_id')->constrained('invoice_items')->nullOnDelete();
            }
            if (!Schema::hasColumn('payment_allocations', 'vote_code')) {
                $table->string('vote_code', 50)->nullable()->after('invoice_item_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('payment_allocations', 'invoice_item_id')) {
                $table->dropForeign(['invoice_item_id']);
                $table->dropColumn('invoice_item_id');
            }
            if (Schema::hasColumn('payment_allocations', 'vote_code')) {
                $table->dropColumn('vote_code');
            }
        });
    }
};
