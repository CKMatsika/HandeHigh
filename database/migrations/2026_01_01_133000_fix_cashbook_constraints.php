<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
         * PostgreSQL-safe Cashbook foreign-key correction.
         *
         * The original migration attempted to drop inferred Laravel
         * constraints that may not exist. PostgreSQL's IF EXISTS
         * prevents the migration from failing in that situation.
         */

        // Remove incorrect/inferred constraints if they exist.
        DB::statement('
            ALTER TABLE cashbook
            DROP CONSTRAINT IF EXISTS cashbook_related_invoice_id_foreign
        ');

        DB::statement('
            ALTER TABLE cashbook
            DROP CONSTRAINT IF EXISTS cashbook_related_payment_id_foreign
        ');

        // Re-add the correct foreign keys.
        DB::statement('
            ALTER TABLE cashbook
            ADD CONSTRAINT cashbook_related_invoice_id_foreign
            FOREIGN KEY (related_invoice_id)
            REFERENCES invoices (id)
            ON DELETE SET NULL
        ');

        DB::statement('
            ALTER TABLE cashbook
            ADD CONSTRAINT cashbook_related_payment_id_foreign
            FOREIGN KEY (related_payment_id)
            REFERENCES payments (id)
            ON DELETE SET NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
            ALTER TABLE cashbook
            DROP CONSTRAINT IF EXISTS cashbook_related_invoice_id_foreign
        ');

        DB::statement('
            ALTER TABLE cashbook
            DROP CONSTRAINT IF EXISTS cashbook_related_payment_id_foreign
        ');
    }
};