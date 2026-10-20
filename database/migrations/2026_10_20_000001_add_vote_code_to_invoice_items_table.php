<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_items', 'vote_code')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->string('vote_code', 50)->nullable()->after('fee_structure_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoice_items', 'vote_code')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('vote_code');
            });
        }
    }
};
