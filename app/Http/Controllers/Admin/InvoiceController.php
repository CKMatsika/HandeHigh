<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\LedgerEntry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Rules\TenantExists;
use App\Services\AccountingService;
use App\Services\Finance\HandeFeeVotesService;
use App\Services\Finance\InvoiceFeeSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private InvoiceFeeSelectionService $feeSelectionService,
    ) {}

    private function resolveSchool(?User $user, ?Invoice $invoice = null): ?School
    {
        if ($user?->school) {
            return $user->school;
        }

        if ($invoice && $invoice->school_id) {
            return $invoice->school ?: School::find($invoice->school_id);
        }

        if ($user?->hasRole('super-admin')) {
            return School::first();
        }

        return null;
    }

    public function create()
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user);

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $students = Student::where('school_id', $school->id)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $feeStructures = FeeStructure::where('school_id', $school->id)
            ->orderBy('grade')
            ->orderBy('residency_type')
            ->orderBy('allocation_priority')
            ->orderBy('category')
            ->orderBy('label')
            ->get();

        $academicYears = $school->enrollments()
            ->distinct()
            ->pluck('academic_year')
            ->sort()
            ->values();

        if ($academicYears->isEmpty()) {
            $academicYears = collect([date('Y'), (string)(date('Y') + 1)]);
        }

        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.invoices.create', [
            'school' => $school,
            'students' => $students,
            'feeStructures' => $feeStructures,
            'standardVotes' => HandeFeeVotesService::getStandardVotes(),
            'academicYears' => $academicYears,
            'terms' => $terms,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user);

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', TenantExists::make('students')],
            'academic_year' => ['required', 'string'],
            'term' => ['required', 'string'],
            'issued_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fee_structure_id' => ['nullable', 'integer', TenantExists::make('fee_structures')],
            'items.*.vote_code' => ['nullable', 'string', 'max:50', Rule::in(array_merge(array_keys(HandeFeeVotesService::getStandardVotes()), ['NONE']))],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $student = Student::where('school_id', $school->id)
            ->where('id', $validated['student_id'])
            ->firstOrFail();

        // Check if active invoice already exists
        $existingInvoice = Invoice::where('school_id', $school->id)
            ->where('student_id', $validated['student_id'])
            ->where('academic_year', $validated['academic_year'])
            ->where('term', $validated['term'])
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingInvoice) {
            return back()
                ->withErrors(['general' => 'Invoice already exists for this student, academic year, and term.'])
                ->withInput();
        }

        // Get enrollment
        $enrollment = Enrollment::where('school_id', $school->id)
            ->where('student_id', $validated['student_id'])
            ->where('academic_year', $validated['academic_year'])
            ->where('term', $validated['term'])
            ->first();

        DB::transaction(function () use ($validated, $student, $enrollment, $school, $user) {
            $invoiceNumber = Invoice::generateNextNumber($school->id);

            $invoice = Invoice::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'guardian_id' => $student->guardian_id,
                'enrollment_id' => $enrollment?->id,
                'number' => $invoiceNumber,
                'type' => 'fees',
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
                'total_amount' => 0,
                'balance' => 0,
                'issued_at' => $validated['issued_at'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => 'unpaid',
            ]);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_amount'];
                $totalAmount += $lineTotal;

                $feeStructure = !empty($item['fee_structure_id'])
                    ? FeeStructure::where('school_id', $school->id)->findOrFail($item['fee_structure_id'])
                    : null;
                $voteCode = $item['vote_code'] ?? null;
                if ($voteCode === 'NONE') {
                    $voteCode = null;
                } elseif (! $voteCode) {
                    $voteCode = $feeStructure?->vote_code;
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_structure_id' => !empty($item['fee_structure_id']) ? (int)$item['fee_structure_id'] : null,
                    'vote_code' => $voteCode,
                    'description' => $item['description'],
                    'category' => $item['category'] ?? 'fees',
                    'quantity' => (int) $item['quantity'],
                    'unit_amount' => (float) $item['unit_amount'],
                    'line_total' => $lineTotal,
                    'project_id' => $feeStructure?->project_id,
                    'cost_center_id' => $feeStructure?->cost_center_id,
                ]);
            }

            $invoice->update([
                'total_amount' => $totalAmount,
                'balance' => $totalAmount,
            ]);

            $this->accountingService->postInvoice($invoice);
            app(\App\Services\Finance\ScholarshipService::class)->applyEligible($invoice);

            AuditLog::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'action' => 'create',
                'module' => 'accounting',
                'model_type' => Invoice::class,
                'model_id' => $invoice->id,
                'description' => "Created invoice {$invoice->number} for {$student->first_name} {$student->last_name} ({$validated['academic_year']} - {$validated['term']}) total: $" . number_format($totalAmount, 2),
                'new_values' => [
                    'invoice_number' => $invoice->number,
                    'student_id' => $student->id,
                    'student_name' => "{$student->first_name} {$student->last_name}",
                    'academic_year' => $validated['academic_year'],
                    'term' => $validated['term'],
                    'total_amount' => $totalAmount,
                    'items_count' => count($validated['items']),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', 'Invoice created successfully.');
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user, $invoice);

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        $this->protectScholarshipInvoice($invoice);
        $totalPaid = (float) $invoice->allocations->sum('amount');

        if ($totalPaid > 0) {
            return back()
                ->withErrors(['general' => 'Cannot delete an invoice with recorded payments. Please void or reverse payment allocations before deleting.']);
        }

        $reason = trim((string) ($request->input('deletion_reason') ?: $request->input('reason') ?: ''));
        if (empty($reason)) {
            $reason = 'No specific reason provided';
        }

        $invoice->loadMissing(['student', 'items']);
        $student = $invoice->student;
        $studentName = $student ? "{$student->first_name} {$student->last_name}" : 'Student #' . $invoice->student_id;
        $invoiceNumber = $invoice->number;
        $totalAmount = (float) $invoice->total_amount;

        DB::transaction(function () use ($invoice, $school, $user, $student, $studentName, $reason, $totalAmount) {
            // 1. Record comprehensive Audit Log before deletion
            AuditLog::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'action' => 'delete',
                'module' => 'accounting',
                'model_type' => Invoice::class,
                'model_id' => $invoice->id,
                'description' => "Deleted invoice {$invoice->number} for {$studentName} ({$invoice->academic_year} - {$invoice->term}) total: $" . number_format($totalAmount, 2) . ". Reason: {$reason}",
                'old_values' => [
                    'invoice_number' => $invoice->number,
                    'student_id' => $invoice->student_id,
                    'student_name' => $studentName,
                    'student_admission_number' => $student?->admission_number,
                    'grade' => $student?->grade,
                    'class_name' => $student?->class_name,
                    'academic_year' => $invoice->academic_year,
                    'term' => $invoice->term,
                    'total_amount' => $totalAmount,
                    'balance' => (float) $invoice->balance,
                    'issued_at' => $invoice->issued_at?->format('Y-m-d'),
                    'due_date' => $invoice->due_date?->format('Y-m-d'),
                    'previous_status' => $invoice->status,
                    'deletion_reason' => $reason,
                    'deleted_by_user_id' => $user->id,
                    'deleted_by_name' => $user->name,
                    'deleted_by_email' => $user->email,
                    'deleted_at' => now()->toDateTimeString(),
                    'items' => $invoice->items->map(fn($item) => [
                        'description' => $item->description,
                        'category' => $item->category,
                        'quantity' => (int) $item->quantity,
                        'unit_amount' => (float) $item->unit_amount,
                        'line_total' => (float) $item->line_total,
                    ])->values()->toArray(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // 2. Delete invoice items
            $invoice->items()->delete();

            // 3. Delete ledger entries
            $invoice->ledgerEntries()->delete();

            // 4. Delete journal batches and their entries
            JournalBatch::where('school_id', $school->id)
                ->where('source_type', 'invoice')
                ->where('source_id', $invoice->id)
                ->each(function ($batch) {
                    $batch->entries()->delete();
                    $batch->delete();
                });

            // 5. Delete the invoice
            $invoice->delete();
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', "Invoice {$invoiceNumber} was permanently removed from active listings, and the audit log trail has been recorded.");
    }

    public function cancel(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user, $invoice);

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        $this->protectScholarshipInvoice($invoice);
        if ($invoice->status === 'cancelled') {
            return back()
                ->with('status', 'Invoice is already cancelled.');
        }

        $student = $invoice->student;
        $studentName = $student ? "{$student->first_name} {$student->last_name}" : 'Student #' . $invoice->student_id;

        DB::transaction(function () use ($invoice, $school, $user, $studentName) {
            $invoice->update(['status' => 'cancelled']);

            // Create cancellation ledger entries
            if ($invoice->balance > 0) {
                LedgerEntry::create([
                    'school_id' => $invoice->school_id,
                    'student_id' => $invoice->student_id,
                    'invoice_id' => $invoice->id,
                    'entry_date' => now()->toDateString(),
                    'account_code' => 'FEES_RECEIVABLE',
                    'type' => 'credit',
                    'amount' => $invoice->balance,
                    'description' => 'Invoice cancellation for ' . $invoice->number,
                ]);

                LedgerEntry::create([
                    'school_id' => $invoice->school_id,
                    'student_id' => $invoice->student_id,
                    'invoice_id' => $invoice->id,
                    'entry_date' => now()->toDateString(),
                    'account_code' => 'FEES_REVENUE',
                    'type' => 'debit',
                    'amount' => $invoice->balance,
                    'description' => 'Invoice cancellation for ' . $invoice->number,
                ]);
            }

            AuditLog::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'action' => 'update',
                'module' => 'accounting',
                'model_type' => Invoice::class,
                'model_id' => $invoice->id,
                'description' => "Cancelled invoice {$invoice->number} for {$studentName} ({$invoice->academic_year} - {$invoice->term}) balance: $" . number_format($invoice->balance, 2),
                'new_values' => ['status' => 'cancelled'],
                'old_values' => ['status' => 'unpaid'],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Invoice cancelled successfully.');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user);

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $statusOptions = ['unpaid', 'partial', 'paid', 'cancelled'];
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $academicYear = trim((string) $request->input('academic_year', ''));
        $term = trim((string) $request->input('term', ''));

        $query = Invoice::with(['student', 'guardian', 'enrollment', 'allocations'])
            ->where('school_id', $school->id);

        if ($search !== '') {
            $searchTerms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($searchTerms as $searchTerm) {
                $like = '%' . $searchTerm . '%';
                $query->where(function ($match) use ($searchTerm, $like, $school) {
                    $match->where('number', 'like', $like)
                        ->orWhereHas('student', function ($studentQuery) use ($like, $searchTerm, $school) {
                            $studentQuery->where('school_id', $school->id)
                                ->where(function ($nameQuery) use ($like, $searchTerm) {
                                    $nameQuery->where('first_name', 'like', $like)
                                        ->orWhere('other_names', 'like', $like)
                                        ->orWhere('last_name', 'like', $like)
                                        ->orWhere('admission_number', 'like', $like)
                                        ->orWhere('registration_number', 'like', $like);

                                    if (ctype_digit($searchTerm)) {
                                        $nameQuery->orWhere('id', (int) $searchTerm);
                                    }
                                });
                        })
                        ->orWhereHas('guardian', function ($guardianQuery) use ($like, $school) {
                            $guardianQuery->where('school_id', $school->id)
                                ->where(function ($nameQuery) use ($like) {
                                    $nameQuery->where('first_name', 'like', $like)
                                        ->orWhere('last_name', 'like', $like)
                                        ->orWhere('email', 'like', $like)
                                        ->orWhere('phone', 'like', $like);
                                });
                        });

                    if (ctype_digit($searchTerm)) {
                        $match->orWhere('id', (int) $searchTerm)
                            ->orWhere('student_id', (int) $searchTerm);
                    }
                });
            }
        }

        if (in_array($status, $statusOptions, true)) {
            $query->where('status', $status);
        }

        if ($academicYear !== '') {
            $query->where('academic_year', $academicYear);
        }

        if ($term !== '') {
            $query->where('term', $term);
        }

        $invoices = $query
            ->orderByDesc('issued_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->only(['search', 'status', 'academic_year', 'term']));

        $academicYearOptions = Invoice::where('school_id', $school->id)
            ->whereNotNull('academic_year')->where('academic_year', '!=', '')
            ->distinct()->orderByDesc('academic_year')->pluck('academic_year');
        $termOptions = Invoice::where('school_id', $school->id)
            ->whereNotNull('term')->where('term', '!=', '')
            ->distinct()->orderBy('term')->pluck('term');

        return view('admin.invoices.index', [
            'school' => $school,
            'invoices' => $invoices,
            'search' => $search,
            'status' => $status,
            'statusOptions' => $statusOptions,
            'academicYear' => $academicYear,
            'academicYearOptions' => $academicYearOptions,
            'term' => $term,
            'termOptions' => $termOptions,
        ]);
    }

    private function getGradeVariations(?string $grade): array
    {
        if (empty($grade)) {
            return [];
        }

        $cleaned = trim($grade);
        $variations = [$cleaned];

        if (preg_match('/^form\s*(\d+)/i', $cleaned, $matches)) {
            $num = $matches[1];
            $variations[] = $num;
            $variations[] = "Form {$num}";
            $variations[] = "Form{$num}";
            $variations[] = "Grade {$num}";
        } elseif (is_numeric($cleaned)) {
            $variations[] = "Form {$cleaned}";
            $variations[] = "Form{$cleaned}";
            $variations[] = "Grade {$cleaned}";
        }

        return array_values(array_unique($variations));
    }

    private function getTermVariations(?string $term): array
    {
        if (empty($term)) {
            return [];
        }

        $cleaned = trim($term);
        $variations = [$cleaned];

        if (preg_match('/(?:term\s*|first\s*term|1)/i', $cleaned) && (str_contains(strtolower($cleaned), '1') || str_contains(strtolower($cleaned), 'first'))) {
            $variations[] = 'Term 1';
            $variations[] = 'First Term';
            $variations[] = '1';
        } elseif (preg_match('/(?:term\s*|second\s*term|2)/i', $cleaned) && (str_contains(strtolower($cleaned), '2') || str_contains(strtolower($cleaned), 'second'))) {
            $variations[] = 'Term 2';
            $variations[] = 'Second Term';
            $variations[] = '2';
        } elseif (preg_match('/(?:term\s*|third\s*term|3)/i', $cleaned) && (str_contains(strtolower($cleaned), '3') || str_contains(strtolower($cleaned), 'third'))) {
            $variations[] = 'Term 3';
            $variations[] = 'Third Term';
            $variations[] = '3';
        }

        return array_values(array_unique($variations));
    }

    public function bulkCreate(Request $request)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user);

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $academicYear = $request->input('academic_year', now()->format('Y'));
        $term = $request->input('term', 'Term 1');
        $grade = $request->input('grade');
        $className = $request->input('class_name');
        $residency = $request->input('residency', 'all'); // 'all', 'boarding', 'day'
        $includeOptional = $request->boolean('include_optional', false);
        $issuedAt = $request->input('issued_at', now()->format('Y-m-d'));
        $dueDate = $request->input('due_date', now()->addDays(30)->format('Y-m-d'));

        // 1. Available dropdown options
        $academicYears = $school->enrollments()->distinct()->pluck('academic_year')
            ->merge(FeeStructure::where('school_id', $school->id)->distinct()->pluck('academic_year'))
            ->merge([now()->format('Y'), (string) (now()->year + 1)])
            ->filter()->unique()->sort()->values();

        $terms = ['Term 1', 'Term 2', 'Term 3'];

        $classMapping = app(\App\Services\StudentClassMappingService::class);
        $grades = SchoolClass::where('school_id', $school->id)->whereNotNull('grade')->distinct()->pluck('grade')
            ->merge(Student::where('school_id', $school->id)->whereNotNull('grade')->distinct()->pluck('grade'))
            ->merge(FeeStructure::where('school_id', $school->id)->whereNotNull('grade')->distinct()->pluck('grade'))
            ->filter()
            ->map(fn ($option) => $classMapping->canonicalGrade($school->id, $option, $option, $academicYear) ?? trim($option))
            ->unique()->sort()->values();

        if ($grades->isEmpty()) {
            $grades = collect(['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6']);
        }

        $classesQuery = SchoolClass::where('school_id', $school->id);
        if ($grade) {
            $canonicalFilterGrade = $classMapping->canonicalGrade($school->id, $grade, $grade, $academicYear) ?? $grade;
            $gradeVars = $classMapping->gradeAliases($canonicalFilterGrade);
            $classesQuery->whereIn('grade', $gradeVars);
        }
        $classes = $classesQuery->distinct()->pluck('name')->filter()->unique()->values();
        if ($classes->isEmpty()) {
            $classes = SchoolClass::where('school_id', $school->id)->distinct()->pluck('name')->filter()->unique()->values();
        }

        // 2. Fetch fee structures for this academic year, term, and grade
        $feeStructureQuery = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear)
            ->where('term', $term);
        $feeStructures = $feeStructureQuery->orderBy('category')->orderBy('code')->get();

        // Selected fee structures (manual selection by ticking)
        if ($request->has('fee_structure_ids')) {
            $selectedFeeStructureIds = array_map('intval', (array) $request->input('fee_structure_ids', []));
        } else {
            // On first visit/filter, default to fee structures matching the
            // selected grade. Structures from other grades remain available
            // for deliberate selection and apply to the filtered cohort.
            $canonicalFilterGrade = $grade ? ($classMapping->canonicalGrade($school->id, $grade, $grade, $academicYear) ?? $grade) : null;
            $gradeVars = $canonicalFilterGrade ? $classMapping->gradeAliasesForSchool($school->id, $canonicalFilterGrade) : [];
            $selectedFeeStructureIds = $feeStructures
                ->filter(fn (FeeStructure $fee) => blank($grade)
                    || blank($fee->grade)
                    || in_array(mb_strtolower(trim($fee->grade)), $gradeVars, true))
                ->pluck('id')
                ->all();
        }

        // 3. Query matching active enrollments & students
        $enrollmentQuery = Enrollment::query()
        ->select([
            'id', 'school_id', 'student_id', 'class_id', 'academic_year', 'term',
            'grade', 'class_name', 'is_boarding', 'has_transport', 'student_type', 'status',
        ])
        ->with([
            'student:id,school_id,first_name,last_name,admission_number,grade,class_name,is_boarding,has_transport',
            'student.invoices' => function ($q) use ($academicYear, $term) {
                $q->where('academic_year', $academicYear)
                  ->where('term', $term)
                  ->where('status', '!=', 'cancelled')
                  ->select('id', 'school_id', 'student_id', 'number', 'academic_year', 'term', 'status');
            },
            'class:id,school_id,grade,name',
        ])
        ->where('school_id', $school->id)
        ->where('status', 'active');

        if ($academicYear) {
            $enrollmentQuery->where(function ($q) use ($academicYear) {
                $q->where('academic_year', $academicYear)
                  ->orWhereNull('academic_year');
            });
        }

        if ($term) {
            $termVars = $this->getTermVariations($term);
            $enrollmentQuery->where(function ($q) use ($termVars) {
                $q->whereIn('term', $termVars)
                  ->orWhereNull('term')
                  ->orWhere('term', '');
            });
        }

        if ($grade) {
            $canonicalFilterGrade = $classMapping->canonicalGrade($school->id, $grade, $grade, $academicYear) ?? $grade;
            $gradeVars = $classMapping->gradeAliases($canonicalFilterGrade);
            $gradeClassNames = $classMapping->gradeAliasesForSchool($school->id, $canonicalFilterGrade);
            $enrollmentQuery->where(function ($q) use ($gradeVars, $gradeClassNames, $school) {
                $q->whereHas('class', fn ($cq) => $cq->where('school_id', $school->id)->whereIn('grade', $gradeVars))
                    ->orWhere(function ($legacy) use ($gradeVars, $gradeClassNames, $school) {
                        $legacy->whereDoesntHave('class')
                            ->where(function ($placement) use ($gradeVars, $gradeClassNames, $school) {
                                $placement->whereIn('enrollments.grade', $gradeVars)
                                    ->orWhereIn('enrollments.class_name', $gradeClassNames)
                                    ->orWhereHas('student', function ($sq) use ($gradeVars, $gradeClassNames, $school) {
                                        $sq->where('school_id', $school->id)
                                            ->where(function ($studentQuery) use ($gradeVars, $gradeClassNames) {
                                                $studentQuery->whereIn('grade', $gradeVars)
                                                    ->orWhereIn('class_name', $gradeClassNames);
                                            });
                                    });
                            });
                    });
            });
        }

        if ($className) {
            $enrollmentQuery->where(function ($q) use ($className, $school) {
                $q->whereHas('class', fn ($cq) => $cq->where('school_id', $school->id)->where('name', $className))
                    ->orWhere(function ($legacy) use ($className, $school) {
                        $legacy->whereDoesntHave('class')
                            ->where(function ($placement) use ($className, $school) {
                                $placement->where('enrollments.class_name', $className)
                                    ->orWhereHas('student', fn ($sq) => $sq->where('school_id', $school->id)->where('class_name', $className));
                            });
                    });
            });
        }

        // Calculate the residency summary in SQL rather than loading a second
        // full Eloquent cohort (including Student models) into PHP memory.
        $cohortRows = (clone $enrollmentQuery)
            ->select('enrollments.student_id')
            ->selectRaw(
                'MAX(CASE WHEN enrollments.is_boarding = 1 OR enrollments.student_type = ? OR EXISTS (SELECT 1 FROM students AS cohort_students WHERE cohort_students.id = enrollments.student_id AND cohort_students.is_boarding = 1) THEN 1 ELSE 0 END) AS is_boarder',
                ['boarding']
            )
            ->groupBy('enrollments.student_id')
            ->toBase();

        $cohortSummary = DB::query()
            ->fromSub($cohortRows, 'cohort')
            ->selectRaw('COUNT(*) AS total_count, COALESCE(SUM(is_boarder), 0) AS boarder_count')
            ->first();

        $cohortTotalCount = (int) ($cohortSummary->total_count ?? 0);
        $cohortBoarderCount = (int) ($cohortSummary->boarder_count ?? 0);
        $cohortDayCount = $cohortTotalCount - $cohortBoarderCount;

        if ($residency === 'boarding') {
            $enrollmentQuery->where(function ($q) {
                $q->where('is_boarding', true)
                  ->orWhere('student_type', 'boarding')
                  ->orWhereHas('student', fn ($sq) => $sq->where('is_boarding', true));
            });
        } elseif ($residency === 'day') {
            $enrollmentQuery->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('is_boarding', false)->orWhereNull('is_boarding');
                })->where(function ($sub) {
                    $sub->where('student_type', '!=', 'boarding')->orWhereNull('student_type');
                })->whereHas('student', function ($sq) {
                    $sq->where('is_boarding', false)->orWhereNull('is_boarding');
                });
            });
        }

        // Deduplicate enrollments by student_id to ensure every student appears exactly once
        $enrollments = $enrollmentQuery->get()
            ->unique('student_id')
            ->sortBy(function ($enr) {
                return ($enr->grade ?? '') . '-' . ($enr->class_name ?? '') . '-' . ($enr->student?->last_name ?? '');
            });

        // 4. Calculate individual preview for each student
        $studentPreviews = [];
        $totalBatchAmount = 0.0;
        $totalBoarders = 0;
        $totalDayScholars = 0;
        $readyCount = 0;
        $alreadyInvoicedCount = 0;

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            $isBoarder = $enrollment->isBoarder() || (bool) $student->is_boarding;
            $hasTransport = (bool) $enrollment->has_transport || (bool) $student->has_transport;
            $existingInvoice = $student->invoices?->first();

            if ($isBoarder) {
                $totalBoarders++;
            } else {
                $totalDayScholars++;
            }

            if ($existingInvoice) {
                $alreadyInvoicedCount++;
            } else {
                $readyCount++;
            }

            // Determine applicable fee items for this student
            $appliedItems = [];
            $studentTotal = 0.0;

            $studentGrade = $enrollment->class?->grade
                ?: $classMapping->canonicalGrade($school->id, $enrollment->grade ?: $student->getRawOriginal('grade'), $enrollment->class_name ?: $student->class_name, $academicYear);
            $applicableFees = $this->feeSelectionService->select(
                $feeStructures,
                $studentGrade,
                $isBoarder,
                $hasTransport,
                $includeOptional,
                $selectedFeeStructureIds,
                // A specific form filter defines the target cohort and allows
                // a deliberately selected schedule to apply to that cohort.
                // With All Forms, retain per-student grade-specific schedules.
                matchGrade: blank($grade),
                studentClassName: $enrollment->class?->name ?: $enrollment->class_name ?: $student->class_name,
            );

            foreach ($applicableFees as $fee) {
                $amount = (float) $fee->amount;
                $studentTotal += $amount;
                $appliedItems[] = [
                    'id' => $fee->id,
                    'label' => $fee->label,
                    'category' => $fee->category ?? 'fees',
                    'amount' => $amount,
                    'vote_code' => $fee->vote_code,
                ];
            }

            if (! $existingInvoice) {
                $totalBatchAmount += $studentTotal;
            }

            $studentPreviews[] = [
                'enrollment' => $enrollment,
                'student' => $student,
                'grade' => $studentGrade ?: '—',
                'class_name' => $enrollment->class_name
                    ?: $student->getRawOriginal('class_name')
                    ?: $enrollment->class?->name
                    ?: '—',
                'is_boarder' => $isBoarder,
                'has_transport' => $hasTransport,
                'existing_invoice' => $existingInvoice,
                'applied_items' => $appliedItems,
                'total_amount' => $studentTotal,
            ];
        }

        return view('admin.invoices.bulk-create', [
            'school' => $school,
            'academicYears' => $academicYears,
            'terms' => $terms,
            'grades' => $grades,
            'classes' => $classes,
            'feeStructures' => $feeStructures,
            'standardVotes' => HandeFeeVotesService::getStandardVotes(),
            'selectedFeeStructureIds' => $selectedFeeStructureIds,
            'selectedYear' => $academicYear,
            'selectedTerm' => $term,
            'selectedGrade' => $grade,
            'selectedClass' => $className,
            'selectedResidency' => $residency,
            'includeOptional' => $includeOptional,
            'issuedAt' => $issuedAt,
            'dueDate' => $dueDate,
            'studentPreviews' => $studentPreviews,
            'totalBatchAmount' => $totalBatchAmount,
            'totalBoarders' => $totalBoarders,
            'totalDayScholars' => $totalDayScholars,
            'cohortTotalCount' => $cohortTotalCount,
            'cohortBoarderCount' => $cohortBoarderCount,
            'cohortDayCount' => $cohortDayCount,
            'readyCount' => $readyCount,
            'alreadyInvoicedCount' => $alreadyInvoicedCount,
        ]);
    }

    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user);

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string'],
            'term' => ['required', 'string'],
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', TenantExists::make('enrollments')],
            'fee_structure_ids' => ['nullable', 'array'],
            'fee_structure_ids.*' => ['integer', TenantExists::make('fee_structures')],
            'fee_selection_submitted' => ['nullable', 'boolean'],
            'match_fee_grade' => ['nullable', 'boolean'],
            'issued_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'grade' => ['nullable', 'string'],
            'include_optional' => ['nullable', 'boolean'],
            'custom_fee_items' => ['nullable', 'array'],
            'custom_fee_items.*.description' => ['required_with:custom_fee_items', 'string', 'max:255'],
            'custom_fee_items.*.category' => ['nullable', 'string', 'max:50'],
            'custom_fee_items.*.amount' => ['required_with:custom_fee_items', 'numeric', 'min:0'],
            'custom_fee_items.*.vote_code' => ['nullable', 'string', 'max:50', Rule::in(array_merge(array_keys(HandeFeeVotesService::getStandardVotes()), ['NONE']))],
            'fee_items' => ['nullable', 'array'],
            'fee_items.*.description' => ['required_with:fee_items', 'string', 'max:255'],
            'fee_items.*.category' => ['nullable', 'string', 'max:50'],
            'fee_items.*.amount' => ['required_with:fee_items', 'numeric', 'min:0'],
            'fee_items.*.vote_code' => ['nullable', 'string', 'max:50', Rule::in(array_merge(array_keys(HandeFeeVotesService::getStandardVotes()), ['NONE']))],
        ]);

        $academicYear = $validated['academic_year'];
        $term = $validated['term'];
        $issuedAt = $validated['issued_at'];
        $dueDate = $validated['due_date'] ?? null;
        $includeOptional = $request->boolean('include_optional');
        $matchFeeGrade = $request->has('match_fee_grade')
            ? $request->boolean('match_fee_grade')
            : true;

        // Fetch enrollments and deduplicate by student_id to ensure single invoice per student
        $enrollments = Enrollment::with(['student.guardians', 'class'])
            ->where('school_id', $school->id)
            ->whereIn('id', $validated['enrollment_ids'])
            ->get()
            ->unique('student_id');

        // Fetch fee structures for this academic year, term, and school
        $grade = $validated['grade'] ?? null;
        $feeStructureQuery = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear)
            ->where('term', $term);
        $feeStructures = $feeStructureQuery->get();

        // Keep sibling vote rows available so selecting a package parent can expand to its sub-votes.
        $selectedFeeIds = null;
        if ($request->boolean('fee_selection_submitted')) {
            $selectedFeeIds = array_map('intval', (array) $request->input('fee_structure_ids', []));
        } elseif ($request->has('fee_structure_ids')) {
            $selectedFeeIds = array_map('intval', (array) $request->input('fee_structure_ids', []));
        } else {
            // Preserve compatibility for existing callers that omit the
            // manual-selection fields entirely.
            $selectedFeeIds = null;
        }

        $customItems = $validated['custom_fee_items'] ?? $validated['fee_items'] ?? [];

        if ($feeStructures->isEmpty() && empty($customItems)) {
            return back()
                ->withErrors(['fee_structure_ids' => 'Please select at least one fee structure or enter custom fee items to generate invoices.'])
                ->withInput();
        }

        if ($selectedFeeIds === [] && empty($customItems)) {
            return back()
                ->withErrors(['fee_structure_ids' => 'Select at least one fee structure or enter custom fee items before generating invoices.'])
                ->withInput();
        }

        $createdInvoices = [];
        $skippedCount = 0;
        $errors = [];
        $totalGeneratedAmount = 0.0;

        DB::transaction(function () use (
            $school,
            $enrollments,
            $academicYear,
            $term,
            $issuedAt,
            $dueDate,
            $feeStructures,
            $customItems,
            $includeOptional,
            $selectedFeeIds,
            $matchFeeGrade,
            &$createdInvoices,
            &$skippedCount,
            &$errors,
            &$totalGeneratedAmount
        ) {
            foreach ($enrollments as $enrollment) {
                try {
                    DB::transaction(function () use (
                        $enrollment,
                        $school,
                        $academicYear,
                        $term,
                        $issuedAt,
                        $dueDate,
                        $feeStructures,
                        $customItems,
                        $includeOptional,
                        $selectedFeeIds,
                        $matchFeeGrade,
                        &$createdInvoices,
                        &$skippedCount,
                        &$totalGeneratedAmount
                    ) {
                        $student = $enrollment->student;
                        if (! $student) {
                            return;
                        }

                    // Check if invoice already exists for this student, year, and term
                    $existingInvoice = Invoice::where('school_id', $school->id)
                        ->where('student_id', $student->id)
                        ->where('academic_year', $academicYear)
                        ->where('term', $term)
                        ->where('status', '!=', 'cancelled')
                        ->first();

                    if ($existingInvoice) {
                        $skippedCount++;
                        return;
                    }

                    $isBoarder = $enrollment->isBoarder() || (bool) $student->is_boarding;
                    $hasTransport = (bool) $enrollment->has_transport || (bool) $student->has_transport;

                    // Resolve items for this student
                    $itemsToCreate = [];
                    $studentTotal = 0.0;

                    // 1. From Fee Structures
                    $studentGrade = $enrollment->class?->grade
                        ?: app(\App\Services\StudentClassMappingService::class)->canonicalGrade(
                            $school->id,
                            $enrollment->grade ?: $student->getRawOriginal('grade'),
                            $enrollment->class_name ?: $student->class_name,
                            $academicYear,
                        );
                    $applicableFees = $this->feeSelectionService->select(
                        $feeStructures,
                        $studentGrade,
                        $isBoarder,
                        $hasTransport,
                        $includeOptional,
                        $selectedFeeIds,
                        matchGrade: $matchFeeGrade,
                        studentClassName: $enrollment->class?->name ?: $enrollment->class_name ?: $student->class_name,
                    );

                    foreach ($applicableFees as $fee) {
                        $amount = (float) $fee->amount;
                        $studentTotal += $amount;
                        $itemsToCreate[] = [
                            'fee_structure_id' => $fee->id,
                            'vote_code' => $fee->vote_code,
                            'description' => $fee->label,
                            'category' => $fee->category ?? 'fees',
                            'quantity' => 1,
                            'unit_amount' => $amount,
                            'line_total' => $amount,
                            'project_id' => $fee->project_id,
                            'cost_center_id' => $fee->cost_center_id,
                        ];
                    }

                    // 2. Custom fee items (if entered)
                    foreach ($customItems as $cItem) {
                        $customVoteCode = ($cItem['vote_code'] ?? null) === 'NONE' ? null : ($cItem['vote_code'] ?? null);
                        $isBoardingItem = str_contains(strtolower($cItem['category'] ?? ''), 'boarding')
                            || str_contains(strtolower($cItem['description'] ?? ''), 'boarding')
                            || $customVoteCode === 'BOARDING_FEES';

                        if (($isBoardingItem && ! $isBoarder) || ($customVoteCode === 'FOOD' && $isBoarder)) {
                            continue;
                        }

                        $amount = (float) $cItem['amount'];
                        $studentTotal += $amount;
                        $itemsToCreate[] = [
                            'fee_structure_id' => null,
                            'vote_code' => $customVoteCode,
                            'description' => $cItem['description'],
                            'category' => $cItem['category'] ?? 'fees',
                            'quantity' => 1,
                            'unit_amount' => $amount,
                            'line_total' => $amount,
                            'project_id' => null,
                            'cost_center_id' => null,
                        ];
                    }

                    if (empty($itemsToCreate)) {
                        return;
                    }

                    $invoiceNumber = Invoice::generateNextNumber($school->id, $academicYear);
                    $primaryGuardian = $student->guardians?->first();

                    $invoice = Invoice::create([
                        'school_id' => $school->id,
                        'student_id' => $student->id,
                        'guardian_id' => $primaryGuardian?->id,
                        'enrollment_id' => $enrollment->id,
                        'number' => $invoiceNumber,
                        'type' => 'fees',
                        'academic_year' => $academicYear,
                        'term' => $term,
                        'total_amount' => $studentTotal,
                        'balance' => $studentTotal,
                        'issued_at' => $issuedAt,
                        'due_date' => $dueDate,
                        'status' => 'unpaid',
                    ]);

                    foreach ($itemsToCreate as $itemData) {
                        InvoiceItem::create([
                            'school_id' => $school->id,
                            'invoice_id' => $invoice->id,
                            'fee_structure_id' => $itemData['fee_structure_id'],
                            'vote_code' => $itemData['vote_code'] ?? null,
                            'description' => $itemData['description'],
                            'category' => $itemData['category'],
                            'quantity' => $itemData['quantity'],
                            'unit_amount' => $itemData['unit_amount'],
                            'line_total' => $itemData['line_total'],
                            'project_id' => $itemData['project_id'] ?? null,
                            'cost_center_id' => $itemData['cost_center_id'] ?? null,
                        ]);
                    }

                    $this->accountingService->postInvoice($invoice);
            app(\App\Services\Finance\ScholarshipService::class)->applyEligible($invoice);

                        $createdInvoices[] = $invoice;
                        $totalGeneratedAmount += $studentTotal;
                    });
                } catch (\Exception $e) {
                    Log::error('Bulk invoice creation error for enrollment ' . $enrollment->id . ': ' . $e->getMessage());
                    $errors[] = "Error for {$enrollment->student?->first_name} {$enrollment->student?->last_name}: " . $e->getMessage();
                }
            }
        });

        $message = count($createdInvoices) . ' invoices created successfully (Total: $' . number_format($totalGeneratedAmount, 2) . ').';
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} students skipped because an invoice already exists.";
        }
        if (count($errors) > 0) {
            $message .= ' ' . implode('; ', array_slice($errors, 0, 3));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'created_count' => count($createdInvoices),
                'created_total' => round($totalGeneratedAmount, 2),
                'skipped_count' => $skippedCount,
                'errors' => $errors,
                'redirect_url' => route('admin.invoices.index'),
            ]);
        }

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', $message);
    }

    public function autoGenerate()
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user);

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        return view('admin.invoices.auto-generate', [
            'school' => $school,
        ]);
    }

    public function processAutoGenerate(Request $request)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user);

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $validated = $request->validate([
            'target_academic_year' => ['required', 'string'],
            'target_term' => ['required', 'string'],
            'generation_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:generation_date'],
            'include_boarding' => ['boolean'],
            'include_transport' => ['boolean'],
            'include_optional_fees' => ['boolean'],
        ]);

        $targetYear = $validated['target_academic_year'];
        $targetTerm = $validated['target_term'];

        // Get current active enrollments
        $currentEnrollments = Enrollment::with(['student', 'class'])
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->get();

        $createdInvoices = [];
        $errors = [];

        DB::transaction(function () use ($validated, $currentEnrollments, $targetYear, $targetTerm, &$createdInvoices, &$errors) {
            foreach ($currentEnrollments as $enrollment) {
                try {
                    // Check if invoice already exists for target term
                    $existingInvoice = Invoice::where('school_id', $enrollment->school_id)
                        ->where('student_id', $enrollment->student_id)
                        ->where('academic_year', $targetYear)
                        ->where('term', $targetTerm)
                        ->where('status', '!=', 'cancelled')
                        ->first();

                    if ($existingInvoice) {
                        continue; // Skip if already exists
                    }

                    // Include grade-specific fees and school-wide fee rows.
                    $feeStructures = FeeStructure::where('school_id', $enrollment->school_id)
                        ->where('academic_year', $targetYear)
                        ->where('term', $targetTerm)
                        ->get();

                    $student = $enrollment->student;
                    $isBoarder = $enrollment->isBoarder() || (bool) $student?->is_boarding;
                    $hasTransport = (bool) $enrollment->has_transport || (bool) $student?->has_transport;
                    $applicableFees = $this->feeSelectionService->select(
                        $feeStructures,
                        $enrollment->class?->grade
                            ?: app(\App\Services\StudentClassMappingService::class)->canonicalGrade(
                                $enrollment->school_id,
                                $enrollment->grade ?: $student?->getRawOriginal('grade'),
                                $enrollment->class_name ?: $student?->class_name,
                                $targetYear,
                            ),
                        $isBoarder,
                        $hasTransport,
                        (bool) ($validated['include_optional_fees'] ?? false),
                        null,
                        (bool) ($validated['include_boarding'] ?? false),
                        (bool) ($validated['include_transport'] ?? false),
                        true,
                        $enrollment->class?->name ?: $enrollment->class_name ?: $student?->class_name,
                    );

                    if ($applicableFees->isEmpty()) {
                        $errors[] = "No fee structure found for Grade {$enrollment->grade}, {$targetYear} {$targetTerm}";
                        continue;
                    }

                    $invoiceNumber = Invoice::generateNextNumber($enrollment->school_id, $targetYear);

                    $invoice = Invoice::create([
                        'school_id' => $enrollment->school_id,
                        'student_id' => $enrollment->student_id,
                        'guardian_id' => $enrollment->student->guardian_id,
                        'enrollment_id' => $enrollment->id,
                        'number' => $invoiceNumber,
                        'type' => 'fees',
                        'academic_year' => $targetYear,
                        'term' => $targetTerm,
                        'total_amount' => 0,
                        'balance' => 0,
                        'issued_at' => $validated['generation_date'],
                        'due_date' => $validated['due_date'] ?? null,
                        'status' => 'unpaid',
                    ]);

                    $totalAmount = 0;
                    foreach ($applicableFees as $feeStructure) {
                        $lineTotal = (float) $feeStructure->amount;
                        $totalAmount += $lineTotal;

                        InvoiceItem::create([
                            'school_id' => $enrollment->school_id,
                            'invoice_id' => $invoice->id,
                            'fee_structure_id' => $feeStructure->id,
                            'vote_code' => $feeStructure->vote_code,
                            'description' => $feeStructure->label,
                            'category' => $feeStructure->category,
                            'quantity' => 1,
                            'unit_amount' => (float) $feeStructure->amount,
                            'line_total' => $lineTotal,
                            'project_id' => $feeStructure->project_id,
                            'cost_center_id' => $feeStructure->cost_center_id,
                        ]);
                    }

                    $invoice->update([
                        'total_amount' => $totalAmount,
                        'balance' => $totalAmount,
                    ]);

                    $this->accountingService->postInvoice($invoice);
            app(\App\Services\Finance\ScholarshipService::class)->applyEligible($invoice);

                    $createdInvoices[] = $invoice;
                } catch (\Exception $e) {
                    Log::error('Auto invoice generation failed for enrollment ' . $enrollment->id . ': ' . $e->getMessage());
                    $errors[] = "Failed to create invoice for {$enrollment->student->first_name} {$enrollment->student->last_name}: " . $e->getMessage();
                }
            }
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', count($createdInvoices) . ' auto-generated invoices created successfully.' . (count($errors) > 0 ? ' Errors: ' . implode('; ', $errors) : ''));
    }

    public function show(Invoice $invoice)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user, $invoice);

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        $invoice->load(['student', 'guardian', 'enrollment', 'items', 'allocations.payment', 'ledgerEntries']);

        return view('admin.invoices.show', [
            'school' => $school,
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user, $invoice);

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        if ($invoice->status === 'cancelled') {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'Cancelled invoices cannot be edited.');
        }

        $invoice->load(['student', 'guardian', 'items.feeStructure', 'items.allocations', 'allocations']);

        $totalPaid = (float) $invoice->allocations->sum('amount');
        $feeStructures = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $invoice->academic_year)
            ->where('term', $invoice->term)
            ->orderBy('residency_type')
            ->orderBy('allocation_priority')
            ->orderBy('label')
            ->get();

        $convertSchedule = null;
        if ($totalPaid <= 0.00001 && $invoice->items->count() === 1) {
            $item = $invoice->items->first();
            if (! $item->effective_vote_code && (float) $item->paid_amount <= 0.00001) {
                $residency = $invoice->student?->is_boarding ? 'boarding' : 'day';
                $packageCode = ($residency === 'boarding' ? 'BOARD-PKG-' : 'DAY-PKG-') . $invoice->academic_year;
                $package = $feeStructures->first(fn (FeeStructure $fee) =>
                    $fee->parent_id === null
                    && $fee->residency_type === $residency
                    && $fee->code === $packageCode
                );
                $schedule = $package
                    ? $feeStructures->where('parent_id', $package->id)->whereNotNull('vote_code')->sortBy('allocation_priority')->values()
                    : collect();
                $scheduleTotal = (float) $schedule->sum(fn (FeeStructure $fee) => (float) $fee->amount);

                if ($schedule->count() === 7 && abs($scheduleTotal - (float) $item->line_total) < 0.01) {
                    $convertSchedule = $schedule;
                }
            }
        }

        return view('admin.invoices.edit', [
            'school' => $school,
            'invoice' => $invoice,
            'totalPaid' => $totalPaid,
            'feeStructures' => $feeStructures,
            'standardVotes' => HandeFeeVotesService::getStandardVotes(),
            'convertSchedule' => $convertSchedule,
            'invoiceItems' => $invoice->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'fee_structure_id' => $item->fee_structure_id,
                'vote_code' => $item->effective_vote_code,
                'description' => $item->description,
                'category' => $item->category,
                'quantity' => $item->quantity,
                'unit_amount' => (float) $item->unit_amount,
                'line_total' => (float) $item->line_total,
                'paid_amount' => (float) $item->paid_amount,
            ])->values(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user, $invoice);

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        $this->protectScholarshipInvoice($invoice);
        if ($invoice->status === 'cancelled') {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'Cancelled invoices cannot be edited.');
        }

        $validated = $request->validate([
            'issued_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fee_structure_id' => ['nullable', 'integer', TenantExists::make('fee_structures')],
            'items.*.vote_code' => ['nullable', 'string', 'max:50', Rule::in(array_merge(array_keys(HandeFeeVotesService::getStandardVotes()), ['NONE']))],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'items.*.id' => ['nullable', 'integer', Rule::exists('invoice_items', 'id')->where('invoice_id', $invoice->id)],
            'edit_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $newTotal = 0.0;
        foreach ($validated['items'] as $item) {
            $newTotal += round((int) $item['quantity'] * (float) $item['unit_amount'], 2);
        }
        $newTotal = round($newTotal, 2);

        DB::transaction(function () use ($invoice, $validated, $newTotal, $school, $user) {
            $lockedInvoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $lockedInvoice->load(['items.allocations']);
            $existingItems = $lockedInvoice->items->keyBy('id');
            $submittedItemIds = collect($validated['items'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();
            $totalPaid = (float) $lockedInvoice->allocations()->sum('amount');

            if ($newTotal + 0.00001 < $totalPaid) {
                throw ValidationException::withMessages([
                    'items' => 'Invoice total cannot be lower than the amount already paid (' . number_format($totalPaid, 2) . ').',
                ]);
            }

            foreach ($existingItems as $existingItem) {
                $itemPaid = (float) $existingItem->allocations->sum('amount');
                $isSubmitted = in_array((int) $existingItem->id, $submittedItemIds, true);
                if (! $isSubmitted && $itemPaid > 0.00001) {
                    throw ValidationException::withMessages([
                        'items' => "The paid item '{$existingItem->description}' cannot be removed.",
                    ]);
                }
            }

            $feeIds = collect($validated['items'])
                ->pluck('fee_structure_id')
                ->filter()
                ->unique()
                ->all();
            $feeStructures = FeeStructure::where('school_id', $school->id)
                ->whereIn('id', $feeIds)
                ->get()
                ->keyBy('id');
            if ($feeStructures->count() !== count($feeIds)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected fee structures do not belong to this school.',
                ]);
            }

            $oldItems = $lockedInvoice->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'fee_structure_id' => $item->fee_structure_id,
                'vote_code' => $item->effective_vote_code,
                'description' => $item->description,
                'category' => $item->category,
                'quantity' => $item->quantity,
                'unit_amount' => (float) $item->unit_amount,
                'line_total' => (float) $item->line_total,
                'paid_amount' => (float) $item->allocations->sum('amount'),
            ])->values()->all();
            $oldTotal = (float) $lockedInvoice->total_amount;
            $delta = $newTotal - $oldTotal;

            foreach ($validated['items'] as $itemData) {
                $lineTotal = round((int) $itemData['quantity'] * (float) $itemData['unit_amount'], 2);
                $feeStructure = ! empty($itemData['fee_structure_id'])
                    ? $feeStructures->get((int) $itemData['fee_structure_id'])
                    : null;
                $voteCode = $itemData['vote_code'] ?? null;
                if ($voteCode === 'NONE') {
                    $voteCode = null;
                } elseif (! $voteCode) {
                    $voteCode = $feeStructure?->vote_code;
                }

                $itemAttributes = [
                    'fee_structure_id' => $feeStructure?->id,
                    'vote_code' => $voteCode,
                    'description' => $itemData['description'],
                    'category' => $itemData['category'] ?? 'fees',
                    'quantity' => (int) $itemData['quantity'],
                    'unit_amount' => (float) $itemData['unit_amount'],
                    'line_total' => $lineTotal,
                    'project_id' => $feeStructure?->project_id,
                    'cost_center_id' => $feeStructure?->cost_center_id,
                ];

                if (! empty($itemData['id'])) {
                    $item = $existingItems->get((int) $itemData['id']);
                    $itemPaid = (float) $item->allocations->sum('amount');
                    if ($lineTotal + 0.00001 < $itemPaid) {
                        throw ValidationException::withMessages([
                            'items' => "The total for '{$item->description}' cannot be lower than its paid amount (" . number_format($itemPaid, 2) . ').',
                        ]);
                    }
                    $item->update($itemAttributes);
                } else {
                    $lockedInvoice->items()->create($itemAttributes);
                }
            }

            $itemsToRemove = $existingItems->keys()->diff($submittedItemIds)->all();
            if (!empty($itemsToRemove)) {
                $lockedInvoice->items()->whereIn('id', $itemsToRemove)->delete();
            }

            $newBalance = max(0, $newTotal - $totalPaid);
            $newStatus = $newBalance <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

            $lockedInvoice->update([
                'issued_at' => $validated['issued_at'],
                'due_date' => $validated['due_date'] ?? null,
                'total_amount' => $newTotal,
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            if (abs($delta) >= 0.00001) {
                $entryDate = now()->toDateString();
                $amount = abs($delta);

                if ($delta > 0) {
                    LedgerEntry::create([
                        'school_id' => $lockedInvoice->school_id,
                        'student_id' => $lockedInvoice->student_id,
                        'invoice_id' => $lockedInvoice->id,
                        'entry_date' => $entryDate,
                        'account_code' => 'FEES_RECEIVABLE',
                        'type' => 'debit',
                        'amount' => $amount,
                        'description' => 'Invoice adjustment (increase) for ' . $invoice->number,
                    ]);

                    LedgerEntry::create([
                        'school_id' => $lockedInvoice->school_id,
                        'student_id' => $lockedInvoice->student_id,
                        'invoice_id' => $lockedInvoice->id,
                        'entry_date' => $entryDate,
                        'account_code' => 'FEES_REVENUE',
                        'type' => 'credit',
                        'amount' => $amount,
                        'description' => 'Invoice adjustment (increase) for ' . $invoice->number,
                    ]);
                } else {
                    LedgerEntry::create([
                        'school_id' => $lockedInvoice->school_id,
                        'student_id' => $lockedInvoice->student_id,
                        'invoice_id' => $lockedInvoice->id,
                        'entry_date' => $entryDate,
                        'account_code' => 'FEES_RECEIVABLE',
                        'type' => 'credit',
                        'amount' => $amount,
                        'description' => 'Invoice adjustment (decrease) for ' . $invoice->number,
                    ]);

                    LedgerEntry::create([
                        'school_id' => $lockedInvoice->school_id,
                        'student_id' => $lockedInvoice->student_id,
                        'invoice_id' => $lockedInvoice->id,
                        'entry_date' => $entryDate,
                        'account_code' => 'FEES_REVENUE',
                        'type' => 'debit',
                        'amount' => $amount,
                        'description' => 'Invoice adjustment (decrease) for ' . $invoice->number,
                    ]);
                }
            }

            $newItems = $lockedInvoice->items()->with('allocations')->get()->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'fee_structure_id' => $item->fee_structure_id,
                'vote_code' => $item->effective_vote_code,
                'description' => $item->description,
                'category' => $item->category,
                'quantity' => $item->quantity,
                'unit_amount' => (float) $item->unit_amount,
                'line_total' => (float) $item->line_total,
                'paid_amount' => (float) $item->allocations->sum('amount'),
            ])->values()->all();

            AuditLog::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'action' => 'update',
                'module' => 'accounting',
                'model_type' => Invoice::class,
                'model_id' => $lockedInvoice->id,
                'description' => "Updated invoice {$lockedInvoice->number}. Reason: " . ($validated['edit_reason'] ?? 'Invoice details updated.'),
                'old_values' => [
                    'total_amount' => $oldTotal,
                    'items' => $oldItems,
                ],
                'new_values' => [
                    'total_amount' => $newTotal,
                    'items' => $newItems,
                    'reason' => $validated['edit_reason'] ?? 'Invoice details updated.',
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Invoice updated successfully.');
    }

    private function protectScholarshipInvoice(Invoice $invoice): void
    {
        if ($invoice->scholarshipApplications()->exists() || $invoice->sponsorClaims()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['invoice' => 'Invoices with scholarship history are preserved. Use scholarship reversals and a separate correcting invoice or credit note.']);
        }
    }

    public function print(Invoice $invoice)
    {
        $user = Auth::user();
        $school = $this->resolveSchool($user, $invoice);

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        $invoice->load(['school', 'student', 'guardian', 'enrollment', 'items', 'allocations.payment']);

        return view('admin.invoices.print', [
            'school' => $school,
            'invoice' => $invoice,
        ]);
    }
}
