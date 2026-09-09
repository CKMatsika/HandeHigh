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
            // Attempt to drop the existing constraints if they exist
            try {
                $table->dropForeign('cashbook_related_invoice_id_foreign');
            } catch (\Exception $e) {
                // Ignore if it doesn't exist
            }

            try {
                $table->dropForeign('cashbook_related_payment_id_foreign');
            } catch (\Exception $e) {
                // Ignore if it doesn't exist
            }

            // Re-add the correct foreign keys
            $table->foreign('related_invoice_id', 'cashbook_related_invoice_id_foreign')
                  ->references('id')->on('invoices')
                  ->onDelete('set null');

            $table->foreign('related_payment_id', 'cashbook_related_payment_id_foreign')
                  ->references('id')->on('payments')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashbook', function (Blueprint $table) {
            try {
                $table->dropForeign('cashbook_related_invoice_id_foreign');
            } catch (\Exception $e) {
                // Ignore
            }

            try {
                $table->dropForeign('cashbook_related_payment_id_foreign');
            } catch (\Exception $e) {
                // Ignore
            }
        });
    }
};