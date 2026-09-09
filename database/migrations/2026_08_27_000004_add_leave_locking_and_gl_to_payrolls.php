<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('leave_days_accrued', 8, 2)->default(0)->after('salary')->comment('Total leave days accrued');
            $table->decimal('leave_days_taken', 8, 2)->default(0)->after('leave_days_accrued')->comment('Total leave days taken');
            $table->decimal('leave_balance', 8, 2)->default(0)->after('leave_days_taken')->comment('Current net available leave days');
        });

        Schema::table('journal_batches', function (Blueprint $table) {
            $table->string('source_type', 50)->default('manual')->change();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('status')->comment('Strict immutability lock for ZIMRA compliance');
            $table->timestamp('locked_at')->nullable()->after('is_locked');
            $table->foreignId('journal_batch_id')->nullable()->after('locked_at')->constrained('journal_batches')->nullOnDelete();
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('leave_days_accrued', 8, 2)->default(0)->after('notes');
            $table->decimal('leave_days_taken', 8, 2)->default(0)->after('leave_days_accrued');
            $table->decimal('leave_balance', 8, 2)->default(0)->after('leave_days_taken');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['leave_days_accrued', 'leave_days_taken', 'leave_balance']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['journal_batch_id']);
            $table->dropColumn(['is_locked', 'locked_at', 'journal_batch_id']);
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn(['leave_days_accrued', 'leave_days_taken', 'leave_balance']);
        });
    }
};
