<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('status_before_archive')->nullable()->after('status');
            $table->timestamp('archived_at')->nullable()->after('status_before_archive');
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            $table->text('archive_reason')->nullable()->after('archived_by');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['archived_by']);
            $table->dropColumn(['status_before_archive', 'archived_at', 'archived_by', 'archive_reason']);
        });
    }
};
