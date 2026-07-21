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
            // SQLite doesn't support dropping foreign keys easily in Schema builder sometimes, 
            // but Laravel abstract this. However, we might need to be careful.
            // Since the original constraints point to non-existent tables, we just need to drop them if they exist?
            // Actually, we can just assume we need to fix them.
            
            // To be safe and since we know the table names were inferred wrongly:
            // "cashbook_related_invoice_id_foreign" -> inferred
            
            try {
                $table->dropForeign(['related_invoice_id']);
            } catch (\Exception $e) {
                // Ignore if it doesn't exist or fails
            }
            
            try {
                $table->dropForeign(['related_payment_id']);
            } catch (\Exception $e) {
                // Ignore if it doesn't exist or fails
            }

            // Now re-add them correctly pointing to 'invoices' and 'payments'
            
            // Note: constraint name needs to be potentially different or explicitly re-added.
            // Using constrained('invoices')
            
            $table->foreign('related_invoice_id')
                  ->references('id')
                  ->on('invoices')
                  ->onDelete('set null');

            $table->foreign('related_payment_id')
                  ->references('id')
                  ->on('payments')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashbook', function (Blueprint $table) {
            $table->dropForeign(['related_invoice_id']);
            $table->dropForeign(['related_payment_id']);
            // We can't really restore the "broken" state easily or meaningfully
        });
    }
};
