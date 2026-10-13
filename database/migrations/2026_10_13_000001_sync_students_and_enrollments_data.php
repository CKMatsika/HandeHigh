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
        if (! Schema::hasTable('students') || ! Schema::hasTable('enrollments')) {
            return;
        }

        // 1. Backfill students grade, class_name, is_boarding, has_transport from active/latest enrollment
        $students = DB::table('students')
            ->where(function ($q) {
                $q->whereNull('grade')
                  ->orWhere('grade', '')
                  ->orWhereNull('class_name')
                  ->orWhere('class_name', '');
            })
            ->get();

        foreach ($students as $student) {
            $enrollment = DB::table('enrollments')
                ->where('student_id', $student->id)
                ->orderByDesc('id')
                ->first();

            if ($enrollment) {
                $updates = [];
                if (empty($student->grade) && ! empty($enrollment->grade)) {
                    $updates['grade'] = $enrollment->grade;
                }
                if (empty($student->class_name) && ! empty($enrollment->class_name)) {
                    $updates['class_name'] = $enrollment->class_name;
                }
                if (empty($student->is_boarding) && ! empty($enrollment->is_boarding)) {
                    $updates['is_boarding'] = $enrollment->is_boarding;
                }
                if (empty($student->has_transport) && ! empty($enrollment->has_transport)) {
                    $updates['has_transport'] = $enrollment->has_transport;
                }

                if (! empty($updates)) {
                    DB::table('students')->where('id', $student->id)->update($updates);
                }
            }
        }

        // 2. Link enrollments.class_id where class_id IS NULL by matching class name and grade
        if (Schema::hasTable('classes')) {
            $unlinkedEnrollments = DB::table('enrollments')
                ->whereNull('class_id')
                ->get();

            foreach ($unlinkedEnrollments as $enr) {
                if (empty($enr->class_name)) {
                    continue;
                }

                $class = null;
                if (! empty($enr->grade)) {
                    $class = DB::table('classes')
                        ->where('school_id', $enr->school_id)
                        ->where('name', $enr->class_name)
                        ->where(function ($q) use ($enr) {
                            $q->where('grade', $enr->grade)
                              ->orWhere('grade', 'like', "%{$enr->grade}%");
                        })
                        ->first();
                }

                if (! $class) {
                    $class = DB::table('classes')
                        ->where('school_id', $enr->school_id)
                        ->where('name', $enr->class_name)
                        ->first();
                }

                if (! $class && ! empty($enr->grade)) {
                    $combinedNames = [
                        "Form {$enr->grade} {$enr->class_name}",
                        "Form{$enr->grade} {$enr->class_name}",
                        "{$enr->grade} {$enr->class_name}",
                        "Form {$enr->grade}{$enr->class_name}",
                    ];
                    $class = DB::table('classes')
                        ->where('school_id', $enr->school_id)
                        ->whereIn('name', $combinedNames)
                        ->first();
                }

                if ($class) {
                    DB::table('enrollments')->where('id', $enr->id)->update(['class_id' => $class->id]);
                }
            }
        }

        // 3. Backfill enrollments grade and class_name from students if missing
        $enrollments = DB::table('enrollments')
            ->where(function ($q) {
                $q->whereNull('grade')
                  ->orWhere('grade', '')
                  ->orWhereNull('class_name')
                  ->orWhere('class_name', '');
            })
            ->get();

        foreach ($enrollments as $enr) {
            $student = DB::table('students')->where('id', $enr->student_id)->first();
            if ($student) {
                $updates = [];
                if (empty($enr->grade) && ! empty($student->grade)) {
                    $updates['grade'] = $student->grade;
                }
                if (empty($enr->class_name) && ! empty($student->class_name)) {
                    $updates['class_name'] = $student->class_name;
                }

                if (! empty($updates)) {
                    DB::table('enrollments')->where('id', $enr->id)->update($updates);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive data synchronization migration
    }
};
