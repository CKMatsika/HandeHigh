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
        Schema::table('budgets', function (Blueprint $table) {
            // Enhanced workflow fields
            $table->enum('status', [
                'draft',           // Accounts Clerk creates
                'submitted',        // Submitted to Bursar
                'bursar_review',   // Bursar reviewing
                'finance_committee', // Sent to Finance Committee
                'committee_review', // Finance Committee reviewing
                'approved',         // Fully approved
                'active',          // Budget is active
                'closed',          // Budget period closed
                'rejected'         // Budget rejected
            ])->default('draft')->change();
            
            // Workflow tracking
            $table->foreignId('submitted_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignId('bursar_reviewed_by')->nullable()->after('submitted_at')->constrained('users')->onDelete('set null');
            $table->timestamp('bursar_reviewed_at')->nullable()->after('bursar_reviewed_by');
            $table->foreignId('committee_reviewed_by')->nullable()->after('bursar_reviewed_at')->constrained('users')->onDelete('set null');
            $table->timestamp('committee_reviewed_at')->nullable()->after('committee_reviewed_by');
            
            // Review notes
            $table->text('bursar_notes')->nullable()->after('committee_reviewed_at');
            $table->text('committee_notes')->nullable()->after('bursar_notes');
            $table->text('rejection_reason')->nullable()->after('committee_notes');
            
            // Budget totals
            $table->decimal('total_budgeted', 15, 2)->default(0)->after('rejection_reason');
            $table->decimal('total_actual', 15, 2)->default(0)->after('total_budgeted');
            $table->decimal('total_variance', 15, 2)->default(0)->after('total_actual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Drop new columns
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
            
            // Revert status enum
            $table->enum('status', ['draft', 'approved', 'active', 'closed'])->default('draft')->change();
        });
    }
};
