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
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, recreate or update tables if needed or re-create schema
            // In SQLite Laravel Schema::table handles foreign key modifications by table recreation
            Schema::table('student_positions', function (Blueprint $table) {
                try {
                    $table->dropForeign(['student_id']);
                } catch (\Exception $e) {}
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });

            Schema::table('student_clubs', function (Blueprint $table) {
                try {
                    $table->dropForeign(['student_id']);
                } catch (\Exception $e) {}
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });

            Schema::table('student_sports', function (Blueprint $table) {
                try {
                    $table->dropForeign(['student_id']);
                } catch (\Exception $e) {}
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        } else {
            Schema::table('student_positions', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });

            Schema::table('student_clubs', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });

            Schema::table('student_sports', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback for extracurricular foreign keys
    }
};
