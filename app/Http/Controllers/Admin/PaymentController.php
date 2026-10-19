<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Rules\TenantExists;
use App\Services\AccountingService;
use App\Services\Finance\CurrencyWordsService;
use App\Services\Finance\HandeFeeVotesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function create(Invoice $invoice)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        if ($invoice->balance <= 0) {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'This invoice is already fully paid.');
        }

        $methods = [
            'cash' => 'Cash',
            'bank' => 'Bank transfer / deposit',
            'mobile_money' => 'Mobile money',
            'other' => 'Other',
        ];

        // Fetch bank and cash accounts available for deposit
        $bankAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function ($query) {
                $query->whereIn('category', ['bank', 'cash'])
                    ->orWhere('code', 'like', '13%')
                    ->orWhereIn('code', ['1100', '1101', '1102']);
            })
            ->active()
            ->orderBy('code')
            ->get();

        $invoice->loadMissing(['student', 'guardian', 'items.feeStructure', 'allocations']);

        // Build item allocation data sorted by FIFO priority
        $items = $invoice->items->map(function (InvoiceItem $item) {
            $paid = (float) $item->allocations()->sum('amount');
            $lineTotal = (float) $item->line_total;
            $balance = max(0.0, $lineTotal - $paid);
            $priority = (int) ($item->feeStructure?->allocation_priority ?? 10);
            $voteCode = $item->feeStructure?->vote_code ?? null;

            return [
                'id' => $item->id,
                'description' => $item->description,
                'category' => $item->category,
                'vote_code' => $voteCode,
                'priority' => $priority,
                'line_total' => $lineTotal,
                'paid' => $paid,
                'balance' => $balance,
            ];
        })->filter(fn($it) => $it['balance'] > 0)->sortBy('priority')->values();

        // Check if student has advance payments with unallocated balance
        $advancePayments = Payment::where('school_id', $school->id)
            ->where('student_id', $invoice->student_id)
            ->where('is_advance', true)
            ->where('advance_balance', '>', 0)
            ->get();

        $totalAdvanceAvailable = (float) $advancePayments->sum('advance_balance');

        return view('admin.payments.create', [
            'invoice' => $invoice,
            'items' => $items,
            'school' => $school,
            'methods' => $methods,
            'bankAccounts' => $bankAccounts,
            'advancePayments' => $advancePayments,
            'totalAdvanceAvailable' => $totalAdvanceAvailable,
        ]);
    }

    public function store(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        if ($invoice->balance <= 0) {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'This invoice is already fully paid.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'mopse_receipt_number' => ['nullable', 'string', 'max:50'],
            'method' => ['required', 'string'],
            'account_id' => ['nullable', 'integer', TenantExists::make('accounts')],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'allocations' => ['nullable', 'array'],
            'allocations.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $amount = (float) $validated['amount'];

        if ($amount > (float) $invoice->balance) {
            $amount = (float) $invoice->balance;
        }

        $method = $validated['method'];
        $accountId = !empty($validated['account_id']) ? (int) $validated['account_id'] : null;
        $mopseReceipt = !empty($validated['mopse_receipt_number']) ? trim($validated['mopse_receipt_number']) : null;
        $allocationsInput = $request->input('allocations', []);

        // Validate allocations sum if specified
        $filteredAllocations = [];
        $sumAllocations = 0.0;
        foreach ($allocationsInput as $itemId => $allocatedVal) {
            $allocAmount = (float) $allocatedVal;
            if ($allocAmount > 0.001) {
                $filteredAllocations[(int) $itemId] = $allocAmount;
                $sumAllocations += $allocAmount;
            }
        }

        if (!empty($filteredAllocations) && abs($sumAllocations - $amount) > 0.05) {
            return back()
                ->withErrors(['amount' => "The sum of vote allocations (\${$sumAllocations}) must equal the total amount paid (\${$amount})."])
                ->withInput();
        }

        $payment = DB::transaction(function () use ($school, $invoice, $validated, $amount, $method, $accountId, $mopseReceipt, $filteredAllocations, $user) {
            $payment = Payment::create([
                'school_id' => $school->id,
                'student_id' => $invoice->student_id,
                'guardian_id' => $invoice->guardian_id,
                'created_by' => $user?->id,
                'account_id' => $accountId,
                'method' => $method,
                'reference' => $validated['reference'] ?? null,
                'mopse_receipt_number' => $mopseReceipt,
                'amount' => $amount,
                'paid_at' => $validated['paid_at'],
                'status' => 'completed',
            ]);

            if (!empty($filteredAllocations)) {
                $invoiceItems = InvoiceItem::with('feeStructure')->whereIn('id', array_keys($filteredAllocations))->get()->keyBy('id');
                foreach ($filteredAllocations as $itemId => $allocVal) {
                    $item = $invoiceItems->get($itemId);
                    PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $itemId,
                        'vote_code' => $item?->feeStructure?->vote_code,
                        'amount' => $allocVal,
                    ]);
                }
            } else {
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                ]);
            }

            $newBalance = max(0, (float) $invoice->balance - $amount);
            $newStatus = $newBalance <= 0 ? 'paid' : 'partial';

            $invoice->update([
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            $this->accountingService->postPayment($payment, $invoice, $accountId);

            return $payment;
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Payment recorded successfully.')
            ->with('receipt_url', route('admin.payments.receipt', $payment))
            ->with('receipt_mopse_url', route('admin.payments.receipt-mopse', $payment))
            ->with('receipt_payment_id', $payment->id);
    }

    /**
     * Create advance payment form (receive prepayment without an existing invoice)
     */
    public function createAdvance(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $selectedStudentId = $request->input('student_id');
        $students = Student::where('school_id', $school->id)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $methods = [
            'cash' => 'Cash',
            'bank' => 'Bank transfer / deposit',
            'mobile_money' => 'Mobile money',
            'other' => 'Other',
        ];

        $bankAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function ($query) {
                $query->whereIn('category', ['bank', 'cash'])
                    ->orWhere('code', 'like', '13%')
                    ->orWhereIn('code', ['1100', '1101', '1102']);
            })
            ->active()
            ->orderBy('code')
            ->get();

        return view('admin.payments.create-advance', [
            'school' => $school,
            'students' => $students,
            'selectedStudentId' => $selectedStudentId,
            'methods' => $methods,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Store advance payment without an existing invoice
     */
    public function storeAdvance(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', TenantExists::make('students')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'mopse_receipt_number' => ['nullable', 'string', 'max:50'],
            'method' => ['required', 'string'],
            'account_id' => ['nullable', 'integer', TenantExists::make('accounts')],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::where('school_id', $school->id)->where('id', $validated['student_id'])->firstOrFail();
        $amount = (float) $validated['amount'];
        $accountId = !empty($validated['account_id']) ? (int) $validated['account_id'] : null;
        $mopseReceipt = !empty($validated['mopse_receipt_number']) ? trim($validated['mopse_receipt_number']) : null;

        $payment = DB::transaction(function () use ($school, $student, $validated, $amount, $accountId, $mopseReceipt, $user) {
            $payment = Payment::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'guardian_id' => $student->guardian_id,
                'created_by' => $user?->id,
                'account_id' => $accountId,
                'method' => $validated['method'],
                'reference' => $validated['reference'] ?? ($validated['notes'] ?? 'Advance Prepayment'),
                'mopse_receipt_number' => $mopseReceipt,
                'amount' => $amount,
                'paid_at' => $validated['paid_at'],
                'status' => 'completed',
                'is_advance' => true,
                'advance_balance' => $amount,
            ]);

            $this->accountingService->postAdvancePayment($payment, $accountId);

            return $payment;
        });

        return redirect()
            ->route('admin.payments.receipt-mopse', $payment)
            ->with('status', 'Advance fee payment received and official MOPSE receipt generated successfully.');
    }

    /**
     * Apply unallocated advance credit to an invoice
     */
    public function applyAdvance(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $invoice->school_id !== $school->id) {
            abort(403);
        }

        if ($invoice->balance <= 0) {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('status', 'This invoice is already fully paid.');
        }

        $validated = $request->validate([
            'payment_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'allocations' => ['nullable', 'array'],
            'allocations.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $payment = Payment::where('school_id', $school->id)
            ->where('student_id', $invoice->student_id)
            ->where('id', $validated['payment_id'])
            ->where('is_advance', true)
            ->where('advance_balance', '>', 0)
            ->firstOrFail();

        $applyAmount = (float) $validated['amount'];
        $applyAmount = min($applyAmount, (float) $payment->advance_balance, (float) $invoice->balance);

        $allocationsInput = $request->input('allocations', []);
        $filteredAllocations = [];
        $sumAllocations = 0.0;
        foreach ($allocationsInput as $itemId => $allocatedVal) {
            $allocVal = (float) $allocatedVal;
            if ($allocVal > 0.001) {
                $filteredAllocations[(int) $itemId] = $allocVal;
                $sumAllocations += $allocVal;
            }
        }

        if (!empty($filteredAllocations) && abs($sumAllocations - $applyAmount) > 0.05) {
            return back()->withErrors(['amount' => "Allocated total (\${$sumAllocations}) must equal applied advance credit (\${$applyAmount})."]);
        }

        DB::transaction(function () use ($payment, $invoice, $applyAmount, $filteredAllocations) {
            // Deduct from advance balance
            $newAdvBalance = max(0.0, (float) $payment->advance_balance - $applyAmount);
            $payment->update(['advance_balance' => $newAdvBalance]);

            // Create allocations
            if (!empty($filteredAllocations)) {
                $invoiceItems = InvoiceItem::with('feeStructure')->whereIn('id', array_keys($filteredAllocations))->get()->keyBy('id');
                foreach ($filteredAllocations as $itemId => $allocVal) {
                    $item = $invoiceItems->get($itemId);
                    PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $itemId,
                        'vote_code' => $item?->feeStructure?->vote_code,
                        'amount' => $allocVal,
                    ]);
                }
            } else {
                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $applyAmount,
                ]);
            }

            // Update invoice balance
            $newInvoiceBalance = max(0.0, (float) $invoice->balance - $applyAmount);
            $newStatus = $newInvoiceBalance <= 0 ? 'paid' : 'partial';

            $invoice->update([
                'balance' => $newInvoiceBalance,
                'status' => $newStatus,
            ]);

            $this->accountingService->postAdvanceAllocation($payment, $invoice, $applyAmount);
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Advance payment credit applied to invoice successfully.');
    }

    /**
     * Display and print official student fee payment receipt (Standard view).
     */
    public function receipt(Payment $payment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $payment->school_id !== $school->id) {
            abort(403);
        }

        $payment->loadMissing([
            'school',
            'student.enrollments',
            'guardian',
            'account',
            'creator',
            'allocations.invoice.items',
        ]);

        return view('admin.payments.receipt', [
            'payment' => $payment,
            'school' => $school,
            'student' => $payment->student,
            'guardian' => $payment->guardian,
            'allocations' => $payment->allocations,
        ]);
    }

    /**
     * Display and print authentic Buhera RDC / Hande Secondary School receipt voucher (Image 2).
     */
    public function receiptMopse(Payment $payment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $payment->school_id !== $school->id) {
            abort(403);
        }

        $payment->loadMissing([
            'school',
            'student.enrollments',
            'guardian',
            'account',
            'creator',
            'allocations.invoiceItem.feeStructure',
            'allocations.invoice',
        ]);

        $primaryInvoice = $payment->allocations?->first()?->invoice;
        $student = $payment->student;

        // Group allocations by official receipt rows
        $scheduleRows = [
            'TUITION' => ['label' => 'Tuition', 'amount' => 0.0],
            'BUILDING_FUND' => ['label' => 'Building Fund', 'amount' => 0.0],
            'SPORTS' => ['label' => 'Sports, Arts And Culture', 'amount' => 0.0],
            'PRACTICALS_ICT' => ['label' => 'Practicals', 'amount' => 0.0],
            'GPF_ADMIN' => ['label' => 'General Purpose Fund & Administration Cost', 'amount' => 0.0],
            'BOARDING_FEES' => ['label' => 'Boarding Fee', 'amount' => 0.0],
            'COUNCIL_LEVY' => ['label' => 'Council Levy', 'amount' => 0.0],
            'BSPZ' => ['label' => 'B.S.P.Z.', 'amount' => 0.0],
            'FOOD' => ['label' => '(Others) Specify: FOOD', 'amount' => 0.0],
            'SCIENCE_LEVY' => ['label' => 'SCIENCE LEVY', 'amount' => 0.0],
        ];

        // Map item allocations to schedule rows
        $allocatedAny = false;
        foreach ($payment->allocations as $alloc) {
            $code = $alloc->vote_code ?? $alloc->invoiceItem?->feeStructure?->vote_code;
            if (!$code) {
                $category = strtolower($alloc->invoiceItem?->category ?? '');
                $desc = strtolower($alloc->invoiceItem?->description ?? '');
                if (str_contains($desc, 'tuition') || $category === 'tuition') $code = 'TUITION';
                elseif (str_contains($desc, 'building') || str_contains($desc, 'bld')) $code = 'BUILDING_FUND';
                elseif (str_contains($desc, 'sport')) $code = 'SPORTS';
                elseif (str_contains($desc, 'ict') || str_contains($desc, 'practical')) $code = 'PRACTICALS_ICT';
                elseif (str_contains($desc, 'general') || str_contains($desc, 'gpf') || str_contains($desc, 'admin')) $code = 'GPF_ADMIN';
                elseif (str_contains($desc, 'board')) $code = 'BOARDING_FEES';
                elseif (str_contains($desc, 'food') || str_contains($desc, 'lunch')) $code = 'FOOD';
                elseif (str_contains($desc, 'science') || str_contains($desc, 'chem') || str_contains($desc, 'bio') || str_contains($desc, 'phys')) $code = 'SCIENCE_LEVY';
            }

            if ($code && isset($scheduleRows[$code])) {
                $scheduleRows[$code]['amount'] += (float) $alloc->amount;
                $allocatedAny = true;
            }
        }

        // If payment was not item-allocated (e.g. advance payment or blanket allocation), allocate visually following standard schedule
        if (!$allocatedAny) {
            $isBoarding = (bool) ($student?->is_boarding);
            $amountLeft = (float) $payment->amount;

            $votesOrder = $isBoarding ? [
                'TUITION' => 20.00,
                'BUILDING_FUND' => 15.00,
                'SPORTS' => 10.00,
                'PRACTICALS_ICT' => 15.00,
                'GPF_ADMIN' => 10.00,
                'SCIENCE_LEVY' => 20.00,
                'BOARDING_FEES' => 380.00,
            ] : [
                'TUITION' => 20.00,
                'BUILDING_FUND' => 15.00,
                'SPORTS' => 10.00,
                'PRACTICALS_ICT' => 15.00,
                'GPF_ADMIN' => 10.00,
                'SCIENCE_LEVY' => 20.00,
                'FOOD' => 45.00,
            ];

            foreach ($votesOrder as $vk => $standardAmt) {
                if ($amountLeft <= 0) break;
                $take = min($amountLeft, $standardAmt);
                $scheduleRows[$vk]['amount'] = $take;
                $amountLeft -= $take;
            }

            if ($amountLeft > 0) {
                $overflowKey = $isBoarding ? 'BOARDING_FEES' : 'FOOD';
                $scheduleRows[$overflowKey]['amount'] += $amountLeft;
            }
        }

        $amountInWords = CurrencyWordsService::toWords($payment->amount);

        return view('admin.payments.receipt-mopse', [
            'payment' => $payment,
            'primaryInvoice' => $primaryInvoice,
            'student' => $student,
            'scheduleRows' => $scheduleRows,
            'amountInWords' => $amountInWords,
            'school' => $school,
        ]);
    }
}
