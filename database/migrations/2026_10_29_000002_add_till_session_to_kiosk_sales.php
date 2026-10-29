<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kiosk_sales', 'kiosk_till_session_id')) {
            Schema::table('kiosk_sales', function (Blueprint $table) {
                $table->foreignId('kiosk_till_session_id')
                    ->nullable()
                    ->after('cashier_id')
                    ->constrained('kiosk_till_sessions')
                    ->restrictOnDelete();
                $table->index(['school_id', 'kiosk_till_session_id'], 'kiosk_sales_till_session_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kiosk_sales', 'kiosk_till_session_id')) {
            Schema::table('kiosk_sales', function (Blueprint $table) {
                $table->dropForeign(['kiosk_till_session_id']);
                $table->dropIndex('kiosk_sales_till_session_idx');
                $table->dropColumn('kiosk_till_session_id');
            });
        }
    }
};
