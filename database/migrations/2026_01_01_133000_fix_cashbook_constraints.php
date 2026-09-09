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
        $foreignKeys = collect(Schema::getForeignKeys('cashbook'))->pluck('name');

        Schema::table('cashbook', function (Blueprint $table) use ($foreignKeys) {
            // Attempt to drop the existing constraints if they exist
            if ($foreignKeys->contains('cashbook_related_invoice_id_foreign')) {
                $table->dropForeign('cashbook_related_invoice_id_foreign');
            }

            if ($foreignKeys->contains('cashbook_related_payment_id_foreign')) {
                $table->dropForeign('cashbook_related_payment_id_foreign');
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
        $foreignKeys = collect(Schema::getForeignKeys('cashbook'))->pluck('name');

        Schema::table('cashbook', function (Blueprint $table) use ($foreignKeys) {
            if ($foreignKeys->contains('cashbook_related_invoice_id_foreign')) {
                $table->dropForeign('cashbook_related_invoice_id_foreign');
            }

            if ($foreignKeys->contains('cashbook_related_payment_id_foreign')) {
                $table->dropForeign('cashbook_related_payment_id_foreign');
            }
        });
    }
};