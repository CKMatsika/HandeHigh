<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\LedgerEntry;
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

        $academicYears = $school->enrollments()
            ->distinct()
            ->pluck('academic_year')
            ->sort()
            ->values();

        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.invoices.create', [
            'school' => $school,
            'students' => $students,
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

        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(Invoice::where('school_id', $school->id)->count() + 1, 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $student, $enrollment, $invoiceNumber, $school) {
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
                    'school_id' => $school->id,
                    'invoice_id' => $invoice->id,
                    'fee_structure_id' => null,
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

    public function bulkCreate()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $enrollments = Enrollment::with(['student', 'class'])
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->orderBy('academic_year')
            ->orderBy('term')
            ->get();

        $academicYears = $enrollments->pluck('academic_year')->unique()->sort()->values();
        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.invoices.bulk-create', [
            'school' => $school,
            'enrollments' => $enrollments,
            'academicYears' => $academicYears,
            'terms' => $terms,
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
            'fee_items' => ['required', 'array', 'min:1'],
            'fee_items.*.description' => ['required', 'string', 'max:255'],
            'fee_items.*.category' => ['nullable', 'string', 'max:50'],
            'fee_items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $enrollments = Enrollment::with(['student', 'guardian'])
            ->where('school_id', $school->id)
            ->whereIn('id', $validated['enrollment_ids'])
            ->get();

        $createdInvoices = [];
        $errors = [];

        DB::transaction(function () use ($validated, $enrollments, &$createdInvoices, &$errors) {
            foreach ($enrollments as $enrollment) {
                try {
                    // Check if invoice already exists
                    $existingInvoice = Invoice::where('school_id', $enrollment->school_id)
                        ->where('student_id', $enrollment->student_id)
                        ->where('academic_year', $validated['academic_year'])
                        ->where('term', $validated['term'])
                        ->first();

                    if ($existingInvoice) {
                        $errors[] = "Invoice already exists for {$enrollment->student->first_name} {$enrollment->student->last_name}";
                        continue;
                    }

                    $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(Invoice::where('school_id', $enrollment->school_id)->count() + 1, 6, '0', STR_PAD_LEFT);

                    $invoice = Invoice::create([
                        'school_id' => $enrollment->school_id,
                        'student_id' => $enrollment->student_id,
                        'guardian_id' => $enrollment->student->guardian_id,
                        'enrollment_id' => $enrollment->id,
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
                    foreach ($validated['fee_items'] as $feeItem) {
                        $lineTotal = (float) $feeItem['amount'];
                        $totalAmount += $lineTotal;

                        InvoiceItem::create([
                            'school_id' => $enrollment->school_id,
                            'invoice_id' => $invoice->id,
                            'fee_structure_id' => null,
                            'description' => $feeItem['description'],
                            'category' => $feeItem['category'] ?? 'fees',
                            'quantity' => 1,
                            'unit_amount' => (float) $feeItem['amount'],
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
                    Log::error('Bulk invoice creation failed for enrollment ' . $enrollment->id . ': ' . $e->getMessage());
                    $errors[] = "Failed to create invoice for {$enrollment->student->first_name} {$enrollment->student->last_name}: " . $e->getMessage();
                }
            }
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', count($createdInvoices) . ' invoices created successfully.' . (count($errors) > 0 ? ' Errors: ' . implode('; ', $errors) : ''));
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

                    $invoiceNumber = 'INV-' . str_replace('-', '', $targetYear) . '-' . str_pad(Invoice::where('school_id', $enrollment->school_id)->count() + 1, 6, '0', STR_PAD_LEFT);

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
                    foreach ($feeStructures as $feeStructure) {
                        // Skip boarding/transport if not applicable
                        if (!$validated['include_boarding'] && str_contains($feeStructure->category, 'boarding')) continue;
                        if (!$validated['include_transport'] && str_contains($feeStructure->category, 'transport')) continue;

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
