<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'invoices_student_term_lookup_idx';

    public function up(): void
    {
        if (! Schema::hasTable('invoices') || Schema::hasIndex('invoices', self::INDEX_NAME)) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['school_id', 'student_id', 'academic_year', 'term'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasIndex('invoices', self::INDEX_NAME)) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex(self::INDEX_NAME);
            });
        }
    }
};
