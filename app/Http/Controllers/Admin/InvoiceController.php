<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\LedgerEntry;
use App\Models\SchoolClass;
use App\Rules\TenantExists;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

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
            'academicYears' => $academicYears,
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
            'student_id' => ['required', 'integer', TenantExists::make('students')],
            'academic_year' => ['required', 'string'],
            'term' => ['required', 'string'],
            'issued_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fee_structure_id' => ['nullable', 'integer'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $student = Student::where('school_id', $school->id)
            ->where('id', $validated['student_id'])
            ->firstOrFail();

        // Check if invoice already exists
        $existingInvoice = Invoice::where('school_id', $school->id)
            ->where('student_id', $validated['student_id'])
            ->where('academic_year', $validated['academic_year'])
            ->where('term', $validated['term'])
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

        DB::transaction(function () use ($validated, $student, $enrollment, $school) {
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

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_structure_id' => !empty($item['fee_structure_id']) ? (int)$item['fee_structure_id'] : null,
                    'description' => $item['description'],
                    'category' => $item['category'] ?? 'fees',
                    'quantity' => (int) $item['quantity'],
                    'unit_amount' => (float) $item['unit_amount'],
                    'line_total' => $lineTotal,
                ]);
            }

            $invoice->update([
                'total_amount' => $totalAmount,
                'balance' => $totalAmount,
            ]);

            $this->accountingService->postInvoice($invoice);
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', 'Invoice created successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        if ($invoice->status === 'cancelled') {
            return back()
                ->with('status', 'Invoice is already cancelled.');
        }

        $totalPaid = (float) $invoice->allocations->sum('amount');

        if ($totalPaid > 0) {
            return back()
                ->withErrors(['general' => 'Cannot delete invoice with payments. Cancel the invoice instead.']);
        }

        DB::transaction(function () use ($invoice) {
            // Delete invoice items
            $invoice->items()->delete();

            // Delete ledger entries
            $invoice->ledgerEntries()->delete();

            // Delete the invoice
            $invoice->delete();
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', 'Invoice deleted successfully.');
    }

    public function cancel(Invoice $invoice)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        if ($invoice->status === 'cancelled') {
            return back()
                ->with('status', 'Invoice is already cancelled.');
        }

        DB::transaction(function () use ($invoice) {
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
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Invoice cancelled successfully.');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $status = $request->input('status');

        $query = Invoice::with(['student', 'guardian', 'enrollment'])
            ->where('school_id', $school->id);

        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query
            ->orderByDesc('issued_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->only('status'));

        $statusOptions = ['unpaid', 'partial', 'paid', 'cancelled'];

        return view('admin.invoices.index', [
            'school' => $school,
            'invoices' => $invoices,
            'status' => $status,
            'statusOptions' => $statusOptions,
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

    public function bulkCreate(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

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

        $grades = SchoolClass::where('school_id', $school->id)->whereNotNull('grade')->distinct()->pluck('grade')
            ->merge(Student::where('school_id', $school->id)->whereNotNull('grade')->distinct()->pluck('grade'))
            ->merge(FeeStructure::where('school_id', $school->id)->whereNotNull('grade')->distinct()->pluck('grade'))
            ->filter()->unique()->values();

        if ($grades->isEmpty()) {
            $grades = collect(['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6']);
        }

        $classesQuery = SchoolClass::where('school_id', $school->id);
        if ($grade) {
            $gradeVars = $this->getGradeVariations($grade);
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

        if ($grade) {
            $gradeVars = $this->getGradeVariations($grade);
            $feeStructureQuery->where(function ($q) use ($gradeVars) {
                $q->whereIn('grade', $gradeVars)
                  ->orWhereNull('grade')
                  ->orWhere('grade', '');
            });
        }

        $feeStructures = $feeStructureQuery->orderBy('category')->orderBy('code')->get();

        // 3. Query matching active enrollments & students
        $enrollmentQuery = Enrollment::with([
            'student.guardians',
            'student.invoices' => function ($q) use ($academicYear, $term) {
                $q->where('academic_year', $academicYear)
                  ->where('term', $term)
                  ->where('status', '!=', 'cancelled');
            },
            'class',
        ])
        ->where('school_id', $school->id)
        ->where('status', 'active');

        if ($academicYear) {
            $enrollmentQuery->where(function ($q) use ($academicYear) {
                $q->where('academic_year', $academicYear)
                  ->orWhereNull('academic_year');
            });
        }

        if ($grade) {
            $gradeVars = $this->getGradeVariations($grade);
            $enrollmentQuery->where(function ($q) use ($gradeVars) {
                $q->whereIn('grade', $gradeVars)
                  ->orWhereHas('student', fn ($sq) => $sq->whereIn('grade', $gradeVars))
                  ->orWhereHas('class', fn ($cq) => $cq->whereIn('grade', $gradeVars));
            });
        }

        if ($className) {
            $enrollmentQuery->where(function ($q) use ($className) {
                $q->where('class_name', $className)
                  ->orWhereHas('student', fn ($sq) => $sq->where('class_name', $className))
                  ->orWhereHas('class', fn ($cq) => $cq->where('name', $className));
            });
        }

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

        $enrollments = $enrollmentQuery->get()->sortBy(function ($enr) {
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

            foreach ($feeStructures as $fee) {
                $isBoardingFee = str_contains(strtolower($fee->category ?? ''), 'boarding') 
                    || $fee->service_type === 'boarding' 
                    || str_contains(strtolower($fee->label ?? ''), 'boarding');

                $isTransportFee = str_contains(strtolower($fee->category ?? ''), 'transport') 
                    || $fee->service_type === 'transport' 
                    || str_contains(strtolower($fee->label ?? ''), 'transport');

                // Boarding fee only applies to boarders
                if ($isBoardingFee && ! $isBoarder) {
                    continue;
                }

                // Transport fee only applies to students with transport
                if ($isTransportFee && ! $hasTransport) {
                    continue;
                }

                // Optional fees apply only if toggle is enabled
                if ($fee->is_optional && ! $includeOptional) {
                    continue;
                }

                $amount = (float) $fee->amount;
                $studentTotal += $amount;
                $appliedItems[] = [
                    'id' => $fee->id,
                    'label' => $fee->label,
                    'category' => $fee->category ?? 'fees',
                    'amount' => $amount,
                ];
            }

            if (! $existingInvoice) {
                $totalBatchAmount += $studentTotal;
            }

            $studentPreviews[] = [
                'enrollment' => $enrollment,
                'student' => $student,
                'grade' => $enrollment->grade ?? $student->grade ?? '—',
                'class_name' => $enrollment->class_name ?? $student->class_name ?? '—',
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
            'readyCount' => $readyCount,
            'alreadyInvoicedCount' => $alreadyInvoicedCount,
        ]);
    }

    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string'],
            'term' => ['required', 'string'],
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', TenantExists::make('enrollments')],
            'issued_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'grade' => ['nullable', 'string'],
            'include_optional' => ['nullable', 'boolean'],
            'custom_fee_items' => ['nullable', 'array'],
            'custom_fee_items.*.description' => ['required_with:custom_fee_items', 'string', 'max:255'],
            'custom_fee_items.*.category' => ['nullable', 'string', 'max:50'],
            'custom_fee_items.*.amount' => ['required_with:custom_fee_items', 'numeric', 'min:0'],
            'fee_items' => ['nullable', 'array'],
            'fee_items.*.description' => ['required_with:fee_items', 'string', 'max:255'],
            'fee_items.*.category' => ['nullable', 'string', 'max:50'],
            'fee_items.*.amount' => ['required_with:fee_items', 'numeric', 'min:0'],
        ]);

        $academicYear = $validated['academic_year'];
        $term = $validated['term'];
        $issuedAt = $validated['issued_at'];
        $dueDate = $validated['due_date'] ?? null;
        $includeOptional = $request->boolean('include_optional');

        // Fetch enrollments
        $enrollments = Enrollment::with(['student.guardians', 'class'])
            ->where('school_id', $school->id)
            ->whereIn('id', $validated['enrollment_ids'])
            ->get();

        // Fetch fee structures for this academic year, term, and school
        $grade = $validated['grade'] ?? null;
        $feeStructureQuery = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear)
            ->where('term', $term);

        if ($grade) {
            $gradeVars = $this->getGradeVariations($grade);
            $feeStructureQuery->where(function ($q) use ($gradeVars) {
                $q->whereIn('grade', $gradeVars)
                  ->orWhereNull('grade')
                  ->orWhere('grade', '');
            });
        }
        $feeStructures = $feeStructureQuery->get();

        $customItems = $validated['custom_fee_items'] ?? $validated['fee_items'] ?? [];

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
            &$createdInvoices,
            &$skippedCount,
            &$errors,
            &$totalGeneratedAmount
        ) {
            foreach ($enrollments as $enrollment) {
                try {
                    $student = $enrollment->student;
                    if (! $student) {
                        continue;
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
                        continue;
                    }

                    $isBoarder = $enrollment->isBoarder() || (bool) $student->is_boarding;
                    $hasTransport = (bool) $enrollment->has_transport || (bool) $student->has_transport;

                    // Resolve items for this student
                    $itemsToCreate = [];
                    $studentTotal = 0.0;

                    // 1. From Fee Structures
                    $studentGrade = $enrollment->grade ?? $student->grade;
                    $studentGradeVars = $this->getGradeVariations($studentGrade);

                    $applicableFees = $feeStructures->filter(function ($fee) use ($studentGradeVars) {
                        if (empty($fee->grade)) {
                            return true;
                        }
                        return empty($studentGradeVars) || in_array($fee->grade, $studentGradeVars);
                    });

                    if ($applicableFees->isEmpty() && $feeStructures->isNotEmpty()) {
                        $applicableFees = $feeStructures;
                    }

                    foreach ($applicableFees as $fee) {
                        $isBoardingFee = str_contains(strtolower($fee->category ?? ''), 'boarding') 
                            || $fee->service_type === 'boarding' 
                            || str_contains(strtolower($fee->label ?? ''), 'boarding');

                        $isTransportFee = str_contains(strtolower($fee->category ?? ''), 'transport') 
                            || $fee->service_type === 'transport' 
                            || str_contains(strtolower($fee->label ?? ''), 'transport');

                        if ($isBoardingFee && ! $isBoarder) {
                            continue;
                        }
                        if ($isTransportFee && ! $hasTransport) {
                            continue;
                        }
                        if ($fee->is_optional && ! $includeOptional) {
                            continue;
                        }

                        $amount = (float) $fee->amount;
                        $studentTotal += $amount;
                        $itemsToCreate[] = [
                            'fee_structure_id' => $fee->id,
                            'description' => $fee->label,
                            'category' => $fee->category ?? 'fees',
                            'quantity' => 1,
                            'unit_amount' => $amount,
                            'line_total' => $amount,
                        ];
                    }

                    // 2. Custom fee items (if entered)
                    foreach ($customItems as $cItem) {
                        $isBoardingItem = str_contains(strtolower($cItem['category'] ?? ''), 'boarding')
                            || str_contains(strtolower($cItem['description'] ?? ''), 'boarding');

                        if ($isBoardingItem && ! $isBoarder) {
                            continue;
                        }

                        $amount = (float) $cItem['amount'];
                        $studentTotal += $amount;
                        $itemsToCreate[] = [
                            'fee_structure_id' => null,
                            'description' => $cItem['description'],
                            'category' => $cItem['category'] ?? 'fees',
                            'quantity' => 1,
                            'unit_amount' => $amount,
                            'line_total' => $amount,
                        ];
                    }

                    if (empty($itemsToCreate)) {
                        continue;
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
                            'description' => $itemData['description'],
                            'category' => $itemData['category'],
                            'quantity' => $itemData['quantity'],
                            'unit_amount' => $itemData['unit_amount'],
                            'line_total' => $itemData['line_total'],
                        ]);
                    }

                    $this->accountingService->postInvoice($invoice);

                    $createdInvoices[] = $invoice;
                    $totalGeneratedAmount += $studentTotal;
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

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', $message);
    }

    public function autoGenerate()
    {
        $user = Auth::user();
        $school = $user?->school;

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
        $school = $user?->school;

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
        $currentEnrollments = Enrollment::with(['student', 'guardian', 'class'])
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
                        ->first();

                    if ($existingInvoice) {
                        continue; // Skip if already exists
                    }

                    // Get fee structures for this student's grade
                    $feeStructures = FeeStructure::where('school_id', $enrollment->school_id)
                        ->where('academic_year', $targetYear)
                        ->where('term', $targetTerm)
                        ->where('grade', $enrollment->grade)
                        ->where(function ($query) use ($validated, $enrollment) {
                            $query->where('is_optional', false)
                                  ->orWhere(function ($subQuery) use ($validated, $enrollment) {
                                      if ($validated['include_optional_fees']) {
                                          $subQuery->where('is_optional', true);
                                      }
                                  });
                        })
                        ->get();

                    if ($feeStructures->isEmpty()) {
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
                    $isBoarder = $enrollment->isBoarder();
                    $hasTransport = (bool) $enrollment->has_transport;

                    foreach ($feeStructures as $feeStructure) {
                        $isBoardingFee = str_contains(strtolower($feeStructure->category ?? ''), 'boarding') || $feeStructure->service_type === 'boarding';
                        $isTransportFee = str_contains(strtolower($feeStructure->category ?? ''), 'transport') || $feeStructure->service_type === 'transport';

                        // Boarding fee applies only if student is officially a boarder
                        if ($isBoardingFee && ! $isBoarder) {
                            continue;
                        }

                        // Transport fee applies only if student has transport
                        if ($isTransportFee && ! $hasTransport && ! ($validated['include_transport'] ?? false)) {
                            continue;
                        }

                        $lineTotal = (float) $feeStructure->amount;
                        $totalAmount += $lineTotal;

                        InvoiceItem::create([
                            'school_id' => $enrollment->school_id,
                            'invoice_id' => $invoice->id,
                            'fee_structure_id' => $feeStructure->id,
                            'description' => $feeStructure->label,
                            'category' => $feeStructure->category,
                            'quantity' => 1,
                            'unit_amount' => (float) $feeStructure->amount,
                            'line_total' => $lineTotal,
                        ]);
                    }

                    $invoice->update([
                        'total_amount' => $totalAmount,
                        'balance' => $totalAmount,
                    ]);

                    $this->accountingService->postInvoice($invoice);

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
        $school = $user?->school;

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
        $school = $user?->school;

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        if ($invoice->status === 'cancelled') {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'Cancelled invoices cannot be edited.');
        }

        $invoice->load(['student', 'guardian', 'items', 'allocations']);

        $totalPaid = (float) $invoice->allocations->sum('amount');

        return view('admin.invoices.edit', [
            'school' => $school,
            'invoice' => $invoice,
            'totalPaid' => $totalPaid,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        if ($invoice->status === 'cancelled') {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'Cancelled invoices cannot be edited.');
        }

        $invoice->load(['items', 'allocations']);

        $validated = $request->validate([
            'issued_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'items.*.id' => ['nullable', 'integer'], // For existing items
            'removed_items' => ['nullable', 'array'], // IDs of items to remove
            'removed_items.*' => ['integer'],
        ]);

        $totalPaid = (float) $invoice->allocations->sum('amount');
        $newTotal = 0.0;

        // Calculate new total from submitted items
        foreach ($validated['items'] as $item) {
            $newTotal += ((int) $item['quantity']) * ((float) $item['unit_amount']);
        }

        if ($newTotal + 0.00001 < $totalPaid) {
            return back()
                ->withErrors(['items' => 'Invoice total cannot be lower than the amount already paid (' . number_format($totalPaid, 2) . ').'])
                ->withInput();
        }

        $oldTotal = (float) $invoice->total_amount;
        $delta = $newTotal - $oldTotal;

        DB::transaction(function () use ($invoice, $validated, $newTotal, $totalPaid, $delta) {
            $existingItemIds = $invoice->items->pluck('id')->toArray();
            $submittedItemIds = [];

            // Update existing items and create new ones
            foreach ($validated['items'] as $itemData) {
                $lineTotal = ((int) $itemData['quantity']) * ((float) $itemData['unit_amount']);

                if (isset($itemData['id']) && in_array($itemData['id'], $existingItemIds)) {
                    // Update existing item
                    $item = $invoice->items()->find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'description' => $itemData['description'],
                            'category' => $itemData['category'] ?? $item->category,
                            'quantity' => (int) $itemData['quantity'],
                            'unit_amount' => (float) $itemData['unit_amount'],
                            'line_total' => $lineTotal,
                        ]);
                        $submittedItemIds[] = $itemData['id'];
                    }
                } else {
                    // Create new item
                    $newItem = $invoice->items()->create([
                        'school_id' => $invoice->school_id,
                        'invoice_id' => $invoice->id,
                        'fee_structure_id' => null,
                        'description' => $itemData['description'],
                        'category' => $itemData['category'] ?? 'fees',
                        'quantity' => (int) $itemData['quantity'],
                        'unit_amount' => (float) $itemData['unit_amount'],
                        'line_total' => $lineTotal,
                    ]);
                    $submittedItemIds[] = $newItem->id;
                }
            }

            // Remove items that were deleted
            $itemsToRemove = array_diff($existingItemIds, $submittedItemIds);
            if (!empty($itemsToRemove)) {
                $invoice->items()->whereIn('id', $itemsToRemove)->delete();
            }

            // Update invoice totals and status
            $newBalance = max(0, $newTotal - $totalPaid);
            $newStatus = $newBalance <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

            $invoice->update([
                'issued_at' => $validated['issued_at'],
                'due_date' => $validated['due_date'] ?? null,
                'total_amount' => $newTotal,
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            // Create ledger entries for total changes
            if (abs($delta) >= 0.00001) {
                $entryDate = now()->toDateString();
                $amount = abs($delta);

                if ($delta > 0) {
                    // Invoice increased - debit receivables, credit revenue
                    LedgerEntry::create([
                        'school_id' => $invoice->school_id,
                        'student_id' => $invoice->student_id,
                        'invoice_id' => $invoice->id,
                        'entry_date' => $entryDate,
                        'account_code' => 'FEES_RECEIVABLE',
                        'type' => 'debit',
                        'amount' => $amount,
                        'description' => 'Invoice adjustment (increase) for ' . $invoice->number,
                    ]);

                    LedgerEntry::create([
                        'school_id' => $invoice->school_id,
                        'student_id' => $invoice->student_id,
                        'invoice_id' => $invoice->id,
                        'entry_date' => $entryDate,
                        'account_code' => 'FEES_REVENUE',
                        'type' => 'credit',
                        'amount' => $amount,
                        'description' => 'Invoice adjustment (increase) for ' . $invoice->number,
                    ]);
                } else {
                    // Invoice decreased - credit receivables, debit revenue
                    LedgerEntry::create([
                        'school_id' => $invoice->school_id,
                        'student_id' => $invoice->student_id,
                        'invoice_id' => $invoice->id,
                        'entry_date' => $entryDate,
                        'account_code' => 'FEES_RECEIVABLE',
                        'type' => 'credit',
                        'amount' => $amount,
                        'description' => 'Invoice adjustment (decrease) for ' . $invoice->number,
                    ]);

                    LedgerEntry::create([
                        'school_id' => $invoice->school_id,
                        'student_id' => $invoice->student_id,
                        'invoice_id' => $invoice->id,
                        'entry_date' => $entryDate,
                        'account_code' => 'FEES_REVENUE',
                        'type' => 'debit',
                        'amount' => $amount,
                        'description' => 'Invoice adjustment (decrease) for ' . $invoice->number,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Invoice updated successfully.');
    }

    public function print(Invoice $invoice)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        $invoice->load(['student', 'guardian', 'enrollment', 'items', 'allocations.payment']);

        return view('admin.invoices.print', [
            'school' => $school,
            'invoice' => $invoice,
        ]);
    }
}
