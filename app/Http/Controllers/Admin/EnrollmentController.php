<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerEntry;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Imports\EnrollmentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $query = Enrollment::with(['student.guardians', 'invoices', 'class'])
            ->where('school_id', $school->id);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('student', function ($sq) use ($search, $school) {
                $sq->where('school_id', $school->id);
                $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

                foreach ($terms as $term) {
                    $sq->where(function ($termQuery) use ($term) {
                        $termQuery->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('admission_number', 'like', "%{$term}%")
                            ->orWhere('registration_number', 'like', "%{$term}%");
                    });
                }
            });
        }

        if ($request->filled('grade')) {
            $grade = $request->input('grade');
            $query->where(function ($q) use ($grade, $school) {
                $q->where('grade', $grade)
                    ->orWhereHas('student', fn ($sq) => $sq->where('school_id', $school->id)->where('grade', $grade))
                    ->orWhereHas('class', fn ($cq) => $cq->where('school_id', $school->id)->where('grade', $grade));
            });
        }

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->input('academic_year'));
        }

        if ($request->filled('term')) {
            $query->where('term', $request->input('term'));
        }

        if ($request->filled('stream') || $request->filled('class_name')) {
            $stream = $request->input('stream') ?: $request->input('class_name');
            $query->where(function ($q) use ($stream, $school) {
                $q->where('class_name', $stream)
                  ->orWhereHas('class', fn ($cq) => $cq->where('school_id', $school->id)->where('name', $stream));
            });
        }

        if ($request->filled('gender')) {
            $query->whereHas('student', fn ($sq) => $sq->where('school_id', $school->id)->where('gender', $request->gender));
        }

        if ($request->filled('status')) {
            if ($request->status !== 'all') {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('student_type')) {
            $query->where('student_type', $request->student_type);
        }

        if ($request->input('residency') === 'boarding') {
            $query->where(function ($q) use ($school) {
                $q->where('is_boarding', true)
                    ->orWhere('student_type', 'boarding')
                    ->orWhereHas('student', fn ($sq) => $sq->where('school_id', $school->id)->where('is_boarding', true));
            });
        } elseif ($request->input('residency') === 'day') {
            $query->where(function ($q) use ($school) {
                $q->where(function ($enrollmentQuery) {
                    $enrollmentQuery->where('is_boarding', false)->orWhereNull('is_boarding');
                })->where(function ($typeQuery) {
                    $typeQuery->where('student_type', '!=', 'boarding')->orWhereNull('student_type');
                })->whereHas('student', function ($studentQuery) use ($school) {
                    $studentQuery->where('school_id', $school->id)
                        ->where(function ($boardingQuery) {
                            $boardingQuery->where('is_boarding', false)->orWhereNull('is_boarding');
                        });
                });
            });
        }

        // Keep filter choices scoped to this school's enrolments and classes.
        $gradeOptions = Enrollment::where('school_id', $school->id)
            ->whereNotNull('grade')->where('grade', '!=', '')
            ->distinct()->orderBy('grade')->pluck('grade')
            ->merge(SchoolClass::where('school_id', $school->id)
                ->whereNotNull('grade')->where('grade', '!=', '')
                ->distinct()->orderBy('grade')->pluck('grade'))
            ->unique()->sort()->values();

        $classOptions = Enrollment::where('school_id', $school->id)
            ->whereNotNull('class_name')->where('class_name', '!=', '')
            ->distinct()->orderBy('class_name')->pluck('class_name')
            ->merge(SchoolClass::where('school_id', $school->id)
                ->whereNotNull('name')->where('name', '!=', '')
                ->distinct()->orderBy('name')->pluck('name'))
            ->unique()->sort()->values();

        $academicYearOptions = Enrollment::where('school_id', $school->id)
            ->whereNotNull('academic_year')->where('academic_year', '!=', '')
            ->distinct()->orderByDesc('academic_year')->pluck('academic_year');
        $termOptions = Enrollment::where('school_id', $school->id)
            ->whereNotNull('term')->where('term', '!=', '')
            ->distinct()->orderBy('term')->pluck('term');
        $statusOptions = Enrollment::where('school_id', $school->id)
            ->whereNotNull('status')->where('status', '!=', '')
            ->distinct()->orderBy('status')->pluck('status');

        $enrollments = $query->orderByDesc('enrollment_date')
            ->paginate(15)
            ->appends($request->query());

        return view('admin.enrollments.index', [
            'school' => $school,
            'enrollments' => $enrollments,
            'gradeOptions' => $gradeOptions,
            'classOptions' => $classOptions,
            'academicYearOptions' => $academicYearOptions,
            'termOptions' => $termOptions,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function show(Enrollment $enrollment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $enrollment->school_id !== $school->id) {
            abort(403);
        }

        $enrollment->load(['student.guardians', 'invoices.items', 'invoices.allocations']);

        return view('admin.enrollments.show', [
            'school' => $school,
            'enrollment' => $enrollment,
        ]);
    }

    public function edit(Enrollment $enrollment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $enrollment->school_id !== $school->id) {
            abort(403);
        }

        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.enrollments.edit', [
            'school' => $school,
            'enrollment' => $enrollment,
            'terms' => $terms,
        ]);
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $enrollment->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:20'],
            'grade' => ['required', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:50'],
            'is_boarding' => ['nullable', 'boolean'],
            'has_transport' => ['nullable', 'boolean'],
            'enrollment_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:20'],
        ]);

        $resolvedClass = null;
        if (! empty($validated['class_name'])) {
            $resolvedClass = SchoolClass::where('school_id', $school->id)
                ->where('name', $validated['class_name'])
                ->where(function ($q) use ($validated) {
                    $q->where('grade', $validated['grade'])
                      ->orWhere('grade', 'like', "%{$validated['grade']}%");
                })
                ->first()
                ?? SchoolClass::where('school_id', $school->id)
                ->where('name', $validated['class_name'])
                ->first();
        }

        $classification = app(\App\Services\StudentCategoryService::class)->fromRequest($request, $enrollment->student);
        $enrollment->update([
            'class_id' => $resolvedClass?->id ?? $enrollment->class_id,
            'academic_year' => $validated['academic_year'],
            'term' => $validated['term'],
            'grade' => $validated['grade'],
            'class_name' => $validated['class_name'] ?? null,
            'is_boarding' => $classification['is_boarding'],
            'student_type' => $classification['is_boarding'] ? 'boarding' : 'day',
            'has_transport' => $request->boolean('has_transport'),
            'enrollment_date' => $validated['enrollment_date'],
            'status' => $validated['status'],
        ]);

        $enrollment->student?->update(array_merge($classification, [
            'grade' => $validated['grade'],
            'class_name' => $validated['class_name'] ?? null,
            'has_transport' => $request->boolean('has_transport'),
        ]));

        return redirect()
            ->route('admin.enrollments.show', $enrollment)
            ->with('status', 'Enrollment updated successfully.');
    }

    public function print(Enrollment $enrollment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $enrollment->school_id !== $school->id) {
            abort(403);
        }

        $enrollment->load(['student.guardians', 'invoices.items', 'invoices.allocations']);

        return view('admin.enrollments.print', [
            'school' => $school,
            'enrollment' => $enrollment,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $currentYear = now()->format('Y');
        $defaultAcademicYear = $currentYear;
        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.enrollments.create', [
            'school' => $school,
            'defaultAcademicYear' => $defaultAcademicYear,
            'terms' => $terms,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $validated = $request->validate([
            'guardian_first_name' => ['required', 'string', 'max:255'],
            'guardian_last_name' => ['required', 'string', 'max:255'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'guardian_address' => ['nullable', 'string', 'max:255'],
            'guardian_city' => ['nullable', 'string', 'max:100'],
            'guardian_country' => ['nullable', 'string', 'max:100'],
            'relationship' => ['nullable', 'string', 'max:50'],

            'student_first_name' => ['required', 'string', 'max:255'],
            'student_last_name' => ['required', 'string', 'max:255'],
            'student_gender' => ['nullable', 'string', 'max:20'],
            'student_date_of_birth' => ['nullable', 'date'],
            'grade' => ['required', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:50'],
            'is_boarding' => ['nullable', 'boolean'],
            'has_transport' => ['nullable', 'boolean'],

            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:20'],
            'enrollment_date' => ['required', 'date'],
        ]);

        $classification = app(\App\Services\StudentCategoryService::class)->fromRequest($request);
        $isBoarding = $classification['is_boarding'];
        $hasTransport = $request->boolean('has_transport');

        $enrollment = DB::transaction(function () use ($validated, $school, $isBoarding, $hasTransport, $classification) {
            $guardianQuery = Guardian::where('school_id', $school->id);

            if (! empty($validated['guardian_email'])) {
                $guardianQuery->where('email', $validated['guardian_email']);
            }

            $guardian = $guardianQuery->first();

            if (! $guardian) {
                $guardian = Guardian::create([
                    'school_id' => $school->id,
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'email' => $validated['guardian_email'] ?? null,
                    'phone' => $validated['guardian_phone'] ?? null,
                    'address' => $validated['guardian_address'] ?? null,
                    'city' => $validated['guardian_city'] ?? null,
                    'country' => $validated['guardian_country'] ?? null,
                ]);
            } else {
                $guardian->fill([
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'phone' => $validated['guardian_phone'] ?? $guardian->phone,
                    'address' => $validated['guardian_address'] ?? $guardian->address,
                    'city' => $validated['guardian_city'] ?? $guardian->city,
                    'country' => $validated['guardian_country'] ?? $guardian->country,
                ]);
                $guardian->save();
            }

            $student = Student::create([
                'school_id' => $school->id,
                'first_name' => $validated['student_first_name'],
                'funding_status' => $classification['funding_status'] ?? 'regular',
                'last_name' => $validated['student_last_name'],
                'gender' => $validated['student_gender'] ?? null,
                'date_of_birth' => $validated['student_date_of_birth'] ?? null,
                'grade' => $validated['grade'],
                'class_name' => $validated['class_name'] ?? null,
                'is_boarding' => $isBoarding,
                'has_transport' => $hasTransport,
                'status' => 'active',
            ]);

            $guardian->students()->syncWithoutDetaching([
                $student->id => [
                    'relationship' => $validated['relationship'] ?? null,
                    'is_primary' => true,
                ],
            ]);

            $resolvedClass = null;
            if (! empty($validated['class_name'])) {
                $resolvedClass = SchoolClass::where('school_id', $school->id)
                    ->where('name', $validated['class_name'])
                    ->where(function ($q) use ($validated) {
                        $q->where('grade', $validated['grade'])
                          ->orWhere('grade', 'like', "%{$validated['grade']}%");
                    })
                    ->first()
                    ?? SchoolClass::where('school_id', $school->id)
                    ->where('name', $validated['class_name'])
                    ->first();
            }

            $enrollment = Enrollment::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'class_id' => $resolvedClass?->id,
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
                'grade' => $validated['grade'],
                'class_name' => $validated['class_name'] ?? null,
                'is_boarding' => $isBoarding,
                'student_type' => $isBoarding ? 'boarding' : 'day',
                'has_transport' => $hasTransport,
                'enrollment_date' => $validated['enrollment_date'],
                'status' => 'active',
            ]);

            $feeStructures = FeeStructure::where('school_id', $school->id)
                ->where('academic_year', $validated['academic_year'])
                ->where('term', $validated['term'])
                ->where(function ($q) use ($validated) {
                    $q->whereNull('grade')
                        ->orWhere('grade', $validated['grade']);
                })
                ->get();

            $selectedFees = app(\App\Services\Finance\InvoiceFeeSelectionService::class)->select(
                $feeStructures, $validated['grade'], $isBoarding, $hasTransport, false
            );

            $invoiceTotal = 0;

            $invoiceNumber = 'INV-' . $school->code . '-' . now()->format('YmdHis');

            $invoice = Invoice::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'guardian_id' => $guardian->id,
                'enrollment_id' => $enrollment->id,
                'number' => $invoiceNumber,
                'type' => 'fees',
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
                'issued_at' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'status' => 'unpaid',
                'total_amount' => 0,
                'balance' => 0,
            ]);

            foreach ($selectedFees as $fee) {
                $quantity = 1;
                $lineTotal = $fee->amount * $quantity;
                $invoiceTotal += $lineTotal;

                InvoiceItem::create([
                    'school_id' => $school->id,
                    'invoice_id' => $invoice->id,
                    'fee_structure_id' => $fee->id,
                    'vote_code' => $fee->vote_code,
                    'description' => $fee->label,
                    'category' => $fee->category,
                    'quantity' => $quantity,
                    'unit_amount' => $fee->amount,
                    'line_total' => $lineTotal,
                    'project_id' => $fee->project_id,
                    'cost_center_id' => $fee->cost_center_id,
                ]);
            }

            if ($invoiceTotal > 0) {
                $invoice->update([
                    'total_amount' => $invoiceTotal,
                    'balance' => $invoiceTotal,
                ]);
                app(\App\Services\AccountingService::class)->postInvoice($invoice);
                app(\App\Services\Finance\ScholarshipService::class)->applyEligible($invoice);

                $entryDate = now()->toDateString();

                LedgerEntry::create([
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'invoice_id' => $invoice->id,
                    'entry_date' => $entryDate,
                    'account_code' => 'FEES_RECEIVABLE',
                    'type' => 'debit',
                    'amount' => $invoiceTotal,
                    'description' => 'Fees receivable for invoice ' . $invoice->number,
                ]);

                LedgerEntry::create([
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'invoice_id' => $invoice->id,
                    'entry_date' => $entryDate,
                    'account_code' => 'FEES_REVENUE',
                    'type' => 'credit',
                    'amount' => $invoiceTotal,
                    'description' => 'Fees revenue for invoice ' . $invoice->number,
                ]);
            }

            return $enrollment;
        });

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Student enrolled and invoice generated successfully.');
    }

    /**
     * Show the bulk enrollment form.
     *
     * @return \Illuminate\View\View
     */
    public function bulkCreate()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        return view('admin.enrollments.bulk-create', [
            'school' => $school,
        ]);
    }

    /**
     * Process the bulk enrollment file upload.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('file');
            $schoolId = $school->id;
            $import = new EnrollmentsImport($schoolId);

            DB::transaction(function () use ($file, $import) {
                Excel::import($import, $file);
            });

            return redirect()
                ->route('admin.enrollments.index')
                ->with('import_summary', $import->summary());
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->with('import_errors', $failures)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error processing file: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Download the bulk enrollment template.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $template = tempnam(storage_path('app'), 'bulk-enrollment-template-');

        if ($template === false) {
            abort(500, 'Unable to create enrollment template.');
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $headers = [
                'student_name', 'email', 'phone', 'address', 'date_of_birth', 'gender',
                'class_name', 'academic_year', 'term', 'grade', 'is_boarding', 'has_transport', 'status',
                'student_category', 'residency', 'funding_status', 'scholarship_reference', 'scholarship_name',
                'scholarship_sponsor', 'scholarship_coverage_type', 'scholarship_coverage_basis',
                'scholarship_coverage_value', 'scholarship_covered_votes', 'scholarship_starts_on',
                'scholarship_ends_on', 'scholarship_accounting_treatment', 'scholarship_supporting_reference',
                'scholarship_academic_year','scholarship_funding_source','scholarship_sponsor_code','scholarship_recognition_evidence'
            ];

            $sheet->fromArray([$headers]);
            $examples = [
                'John Doe', 'john@example.com', '1234567890', '123 Main St', '2010-05-15', 'male',
                'Form 1 A', '2026', 'Term 1', 'Form 1', 'no', 'yes', 'active',
                'day', 'day', 'regular'
            ];
            $sheet->fromArray([$examples], null, 'A2');

            foreach (range(1, count($headers)) as $column) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($template);

            return response()
                ->download($template, 'bulk-enrollment-template.xlsx')
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            @unlink($template);
            throw $e;
        }
    }
}
