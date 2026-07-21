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
        Schema::table('accounts', function (Blueprint $table) {
            // Best-effort drop of existing unique index on code (sqlite safe with try/catch)
            try {
                $table->dropUnique('accounts_code_unique');
            } catch (\Throwable $e) {
                // ignore if not present
            }

            // Add composite unique index on school_id + code
            $table->unique(['school_id', 'code'], 'accounts_school_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            try {
                $table->dropUnique('accounts_school_code_unique');
            } catch (\Throwable $e) {
                // ignore
            }
            // Revert to unique code (not recommended for multi-school, but for rollback)
            $table->unique('code');
        });
    }
};
