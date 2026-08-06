<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('house_id')->nullable()->constrained('school_houses')->nullOnDelete()->after('class_name');
            $table->string('exit_type')->nullable()->after('status')->comment('graduated, transferred, withdrawn, expelled');
            $table->date('exit_date')->nullable()->after('exit_type');
            $table->text('exit_remarks')->nullable()->after('exit_date');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['house_id']);
            $table->dropColumn(['house_id', 'exit_type', 'exit_date', 'exit_remarks']);
        });
    }
};
