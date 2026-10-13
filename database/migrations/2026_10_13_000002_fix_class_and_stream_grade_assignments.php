<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('classes') || ! Schema::hasTable('enrollments')) {
            return;
        }

        // 1. Ensure distinct SchoolClass records exist for each Grade and Stream
        $grades = ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6'];
        $streams = ['North', 'South', 'East', 'West'];

        $schools = DB::table('schools')->get();

        foreach ($schools as $school) {
            foreach ($grades as $grade) {
                foreach ($streams as $stream) {
                    $exists = DB::table('classes')
                        ->where('school_id', $school->id)
                        ->where('grade', $grade)
                        ->where('name', $stream)
                        ->exists();

                    if (! $exists) {
                        DB::table('classes')->insert([
                            'school_id' => $school->id,
                            'name' => $stream,
                            'grade' => $grade,
                            'academic_year' => '2026',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // 2. Correct enrollments where class_id is mismatched or loosely linked
            $enrollments = DB::table('enrollments')
                ->where('school_id', $school->id)
                ->whereNotNull('class_name')
                ->get();

            foreach ($enrollments as $enr) {
                $enrGrade = trim((string) ($enr->grade ?? ''));
                if (empty($enrGrade)) {
                    // Try to get grade from student
                    $student = DB::table('students')->where('id', $enr->student_id)->first();
                    $enrGrade = trim((string) ($student->grade ?? ''));
                }

                if (empty($enrGrade)) {
                    continue;
                }

                // Standardize grade representation
                $canonicalGrade = match(strtolower($enrGrade)) {
                    '1', 'form 1', 'form1', 'grade 1' => 'Form 1',
                    '2', 'form 2', 'form2', 'grade 2' => 'Form 2',
                    '3', 'form 3', 'form3', 'grade 3' => 'Form 3',
                    '4', 'form 4', 'form4', 'grade 4' => 'Form 4',
                    '5', 'form 5', 'form5', 'grade 5' => 'Form 5',
                    '6', 'form 6', 'form6', 'grade 6' => 'Form 6',
                    default => $enrGrade,
                };

                // Find the exact matching class for this grade and stream
                $correctClass = DB::table('classes')
                    ->where('school_id', $school->id)
                    ->where('name', $enr->class_name)
                    ->where('grade', $canonicalGrade)
                    ->first();

                if ($correctClass) {
                    DB::table('enrollments')
                        ->where('id', $enr->id)
                        ->update([
                            'class_id' => $correctClass->id,
                            'grade' => $canonicalGrade,
                        ]);

                    // Also sync to student table
                    DB::table('students')
                        ->where('id', $enr->student_id)
                        ->update([
                            'grade' => $canonicalGrade,
                            'class_name' => $enr->class_name,
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive data migration
    }
};
