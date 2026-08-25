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
        // 1. Add is_postable to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'is_postable')) {
                $table->boolean('is_postable')->default(true)->after('is_active');
            }
        });

        // 2. Add revenue_account_id to fee_structures table
        Schema::table('fee_structures', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_structures', 'revenue_account_id')) {
                $table->foreignId('revenue_account_id')
                    ->nullable()
                    ->after('service_type')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }
        });

        // 3. Add revenue_account_id to projects table
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'revenue_account_id')) {
                $table->foreignId('revenue_account_id')
                    ->nullable()
                    ->after('project_type')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }
        });

        // 4. Add revenue_account_id to kiosk_products table
        Schema::table('kiosk_products', function (Blueprint $table) {
            if (! Schema::hasColumn('kiosk_products', 'revenue_account_id')) {
                $table->foreignId('revenue_account_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kiosk_products', function (Blueprint $table) {
            if (Schema::hasColumn('kiosk_products', 'revenue_account_id')) {
                $table->dropForeign(['revenue_account_id']);
                $table->dropColumn('revenue_account_id');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'revenue_account_id')) {
                $table->dropForeign(['revenue_account_id']);
                $table->dropColumn('revenue_account_id');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('fee_structures', 'revenue_account_id')) {
                $table->dropForeign(['revenue_account_id']);
                $table->dropColumn('revenue_account_id');
            }
        });

        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'is_postable')) {
                $table->dropColumn('is_postable');
            }
        });
    }
};
