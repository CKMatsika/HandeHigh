<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentStatementPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BulkDocumentPrintController extends Controller
{
    private const MAX_STUDENTS_PER_PRINT = 60;

    public function index()
    {
        $school = $this->resolveSchool(Auth::user());
        abort_unless($school, 403, 'No school context available.');

        $academicYears = Enrollment::where('school_id', $school->id)
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        if ($academicYears->isEmpty()) {
            $academicYears = Invoice::where('school_id', $school->id)
                ->whereNotNull('academic_year')
                ->distinct()
                ->orderByDesc('academic_year')
                ->pluck('academic_year');
        }

        $academicYears = $academicYears->prepend((string) now()->year)->unique()->values();
        $grades = Enrollment::where('school_id', $school->id)
            ->whereNotNull('grade')->where('grade', '!=', '')
            ->distinct()->orderBy('grade')->pluck('grade')
            ->merge(SchoolClass::where('school_id', $school->id)->whereNotNull('grade')->pluck('grade'))
            ->filter()->unique()->sort()->values();
        $classes = SchoolClass::where('school_id', $school->id)
            ->orderBy('grade')->orderBy('name')->get(['id', 'grade', 'name']);

        return view('admin.invoices.bulk-print-create', compact('school', 'academicYears', 'grades', 'classes'));
    }

    public function print(Request $request, StudentStatementPrintService $statementPrinter)
    {
        $school = $this->resolveSchool(Auth::user());
        abort_unless($school, 403, 'No school context available.');

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', Rule::in(['Term 1', 'Term 2', 'Term 3'])],
            'grade' => ['nullable', 'string', 'max:40'],
            'class_id' => [
                'nullable', 'integer',
                Rule::exists('classes', 'id')->where(fn ($query) => $query->where('school_id', $school->id)),
            ],
            'document_type' => ['required', Rule::in(['invoices', 'statements', 'both'])],
        ]);

        $enrollmentQuery = Enrollment::query()
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->where(function ($query) use ($validated) {
                $query->where('academic_year', $validated['academic_year'])
                    ->orWhereNull('academic_year');
            });

        if (! empty($validated['grade'])) {
            $gradeVariants = $this->gradeVariants($validated['grade']);
            $enrollmentQuery->where(function ($query) use ($gradeVariants) {
                $query->whereIn('grade', $gradeVariants)
                    ->orWhereHas('student', fn ($studentQuery) => $studentQuery->whereIn('grade', $gradeVariants))
                    ->orWhereHas('class', fn ($classQuery) => $classQuery->whereIn('grade', $gradeVariants));
            });
        }

        if (! empty($validated['class_id'])) {
            $selectedClass = SchoolClass::where('school_id', $school->id)->findOrFail($validated['class_id']);
            $enrollmentQuery->where(function ($query) use ($selectedClass) {
                $query->where('class_id', $selectedClass->id)
                    ->orWhere(function ($legacyQuery) use ($selectedClass) {
                        $legacyQuery->whereNull('class_id')->where('class_name', $selectedClass->name);
                        if ($selectedClass->grade) {
                            $gradeVariants = $this->gradeVariants($selectedClass->grade);
                            $legacyQuery->where(function ($gradeQuery) use ($gradeVariants) {
                                $gradeQuery->whereIn('grade', $gradeVariants)
                                    ->orWhereHas('student', fn ($studentQuery) => $studentQuery->whereIn('grade', $gradeVariants));
                            });
                        }
                    });
            });
        }

        $matchingStudentCount = (clone $enrollmentQuery)->distinct('student_id')->count('student_id');
        if ($matchingStudentCount > self::MAX_STUDENTS_PER_PRINT) {
            return back()->withInput()->withErrors([
                'cohort' => 'This print batch matches ' . $matchingStudentCount . ' students. Narrow it by class; the maximum per print run is ' . self::MAX_STUDENTS_PER_PRINT . ' students.',
            ]);
        }

        $cohortEnrollments = $enrollmentQuery
            ->with('class:id,school_id,grade,name')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');
        $studentIds = $cohortEnrollments->keys();
        $students = Student::where('school_id', $school->id)
            ->whereIn('id', $studentIds)
            ->with('currentEnrollment.class')
            ->orderBy('last_name')->orderBy('first_name')->get();

        $invoices = collect();
        $statements = collect();

        if (in_array($validated['document_type'], ['invoices', 'both'], true)) {
            $invoiceQuery = Invoice::with(['student.currentEnrollment.class', 'items.feeStructure', 'enrollment.class'])
                ->where('school_id', $school->id)
                ->whereIn('student_id', $studentIds)
                ->where('academic_year', $validated['academic_year'])
                ->where('status', '!=', 'cancelled');

            if (! empty($validated['term'])) {
                $invoiceQuery->where('term', $validated['term']);
            }

            $invoices = $invoiceQuery->orderBy('student_id')->orderBy('issued_at')->get();
        }

        if (in_array($validated['document_type'], ['statements', 'both'], true)) {
            foreach ($students as $student) {
                $statements->put($student->id, $statementPrinter->build(
                    $school,
                    $student,
                    $validated['academic_year'],
                    $validated['term'] ?? null,
                ));
            }
        }

        return view('admin.invoices.bulk-print-output', [
            'school' => $school,
            'students' => $students,
            'cohortEnrollments' => $cohortEnrollments,
            'invoices' => $invoices,
            'statements' => $statements,
            'academicYear' => $validated['academic_year'],
            'term' => $validated['term'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'selectedClass' => ! empty($validated['class_id']) ? $selectedClass : null,
            'documentType' => $validated['document_type'],
        ]);
    }

    private function resolveSchool(?User $user): ?School
    {
        if ($user?->school) {
            return $user->school;
        }

        return $user?->hasRole('super-admin') ? School::first() : null;
    }

    private function gradeVariants(string $grade): array
    {
        $variants = [trim($grade)];
        if (preg_match('/^form\s*(\d+)/i', trim($grade), $matches)) {
            $variants = array_merge($variants, [$matches[1], 'Form ' . $matches[1], 'Form' . $matches[1], 'Grade ' . $matches[1]]);
        } elseif (is_numeric(trim($grade))) {
            $variants = array_merge($variants, ['Form ' . trim($grade), 'Form' . trim($grade), 'Grade ' . trim($grade)]);
        }

        return array_values(array_unique($variants));
    }
}
