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
        Schema::table('fee_structures', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_structures', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('school_id')->constrained('fee_structures')->nullOnDelete();
            }
            if (!Schema::hasColumn('fee_structures', 'residency_type')) {
                $table->string('residency_type', 20)->default('all')->after('grade');
            }
            if (!Schema::hasColumn('fee_structures', 'vote_code')) {
                $table->string('vote_code', 50)->nullable()->after('code');
            }
            if (!Schema::hasColumn('fee_structures', 'allocation_priority')) {
                $table->integer('allocation_priority')->default(10)->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('fee_structures', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('fee_structures', 'residency_type')) {
                $table->dropColumn('residency_type');
            }
            if (Schema::hasColumn('fee_structures', 'vote_code')) {
                $table->dropColumn('vote_code');
            }
            if (Schema::hasColumn('fee_structures', 'allocation_priority')) {
                $table->dropColumn('allocation_priority');
            }
        });
    }
};
