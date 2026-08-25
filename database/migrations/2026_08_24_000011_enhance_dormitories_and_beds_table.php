<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dormitories', function (Blueprint $table) {
            $table->foreignId('hostel_id')->nullable()->after('school_id')->constrained('hostels')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('prefect_id')->constrained('employees')->nullOnDelete();
            $table->index(['school_id', 'hostel_id']);
        });

        Schema::table('bed_assignments', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->cascadeOnDelete();
            $table->string('notes')->nullable()->after('is_current');
            $table->index(['school_id', 'academic_year', 'term', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::table('bed_assignments', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn(['school_id', 'notes']);
        });

        Schema::table('dormitories', function (Blueprint $table) {
            $table->dropForeign(['hostel_id']);
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn(['hostel_id', 'supervisor_id']);
        });
    }
};
