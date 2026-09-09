<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
         * PostgreSQL-safe budget workflow migration.
         *
         * We do not use ->enum()->change() here because Laravel's
         * PostgreSQL grammar generates invalid SQL for that operation.
         */

        // First make sure existing budget statuses remain valid.
        DB::statement("
            UPDATE budgets
            SET status = 'draft'
            WHERE status IS NULL
               OR status NOT IN ('draft', 'approved', 'active', 'closed')
        ");

        // Add workflow tracking fields.
        if (!Schema::hasColumn('budgets', 'submitted_by')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->foreignId('submitted_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('submitted_at')->nullable();

                $table->foreignId('bursar_reviewed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('bursar_reviewed_at')->nullable();

                $table->foreignId('committee_reviewed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('committee_reviewed_at')->nullable();

                // Review notes
                $table->text('bursar_notes')->nullable();
                $table->text('committee_notes')->nullable();
                $table->text('rejection_reason')->nullable();

                // Budget totals
                $table->decimal('total_budgeted', 15, 2)->default(0);
                $table->decimal('total_actual', 15, 2)->default(0);
                $table->decimal('total_variance', 15, 2)->default(0);
            });
        }

        /*
         * MySQL-safe budget workflow migration.
         * We alter the column directly to add the new enum values.
         */
        DB::statement("
            ALTER TABLE budgets 
            MODIFY status ENUM(
                'draft', 
                'submitted', 
                'bursar_review', 
                'finance_committee', 
                'committee_review', 
                'approved', 
                'active', 
                'closed', 
                'rejected'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE budgets 
            MODIFY status ENUM('draft', 'approved', 'active', 'closed') NOT NULL DEFAULT 'draft'
        ");

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['bursar_reviewed_by']);
            $table->dropForeign(['committee_reviewed_by']);

            $table->dropColumn([
                'submitted_by',
                'submitted_at',
                'bursar_reviewed_by',
                'bursar_reviewed_at',
                'committee_reviewed_by',
                'committee_reviewed_at',
                'bursar_notes',
                'committee_notes',
                'rejection_reason',
                'total_budgeted',
                'total_actual',
                'total_variance'
            ]);
        });
    }
};