<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Cashbook;
use App\Models\CreditNote;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceReportingService
{
    /**
     * Calculate Debtor Aging matrix with flexible filtering and grouping.
     */
    public function getDebtorsAging(School $school, array $filters = []): array
    {
        $asOf = !empty($filters['as_of_date']) ? Carbon::parse($filters['as_of_date'])->endOfDay() : now()->endOfDay();
        $minBalance = isset($filters['min_balance']) && is_numeric($filters['min_balance']) ? (float)$filters['min_balance'] : 0.01;
        $academicYear = $filters['academic_year'] ?? null;
        $term = $filters['term'] ?? null;
        $form = $filters['form'] ?? null;
        $className = $filters['class_name'] ?? null;
        $studentId = $filters['student_id'] ?? null;
        $feeType = $filters['fee_type'] ?? null;
        $groupBy = $filters['group_by'] ?? 'none'; // 'none', 'form', 'class', 'student', 'fee_type'
        $startDate = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;

        $query = Invoice::where('school_id', $school->id)
            ->where('balance', '>=', $minBalance)
            ->with(['student', 'guardian', 'items.feeStructure']);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        if ($term) {
            $query->where('term', $term);
        }

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('issued_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('issued_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('issued_at', '<=', $endDate);
        }

        if ($form) {
            $query->whereHas('student', function ($q) use ($form) {
                $q->where('grade', $form);
            });
        }

        if ($className) {
            $query->whereHas('student', function ($q) use ($className) {
                $q->where('class_name', $className);
            });
        }

        if ($feeType) {
            $query->where(function ($q) use ($feeType) {
                $q->where('type', $feeType)
                    ->orWhereHas('items', function ($itemQ) use ($feeType) {
                        $itemQ->where('category', $feeType);
                    });
            });
        }

        $invoices = $query->orderBy('due_date', 'asc')->get();

        $rows = collect();
        $totalInvoiced = 0.0;
        $totalPaid = 0.0;
        $totalCredit = 0.0;
        $totalOutstanding = 0.0;

        $bucketTotals = [
            'current' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '91_120' => 0.0,
            '120_plus' => 0.0,
        ];

        $bucketCounts = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '91_120' => 0,
            '120_plus' => 0,
        ];

        // Fetch credit notes per invoice/student for adjustment calculation
        $creditNotes = CreditNote::where('school_id', $school->id)
            ->whereIn('status', ['applied', 'issued'])
            ->get()
            ->groupBy('invoice_id');

        foreach ($invoices as $invoice) {
            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->startOfDay() : ($invoice->issued_at ? Carbon::parse($invoice->issued_at)->startOfDay() : $asOf->copy()->startOfDay());
            
            // Days overdue calculation relative to as_of date
            $daysOverdue = $asOf->gt($dueDate) ? (int) $dueDate->diffInDays($asOf, false) : 0;
            if ($daysOverdue < 0) {
                $daysOverdue = 0;
            }

            $bucketKey = match (true) {
                $daysOverdue <= 0 => 'current',
                $daysOverdue <= 30 => '1_30',
                $daysOverdue <= 60 => '31_60',
                $daysOverdue <= 90 => '61_90',
                $daysOverdue <= 120 => '91_120',
                default => '120_plus',
            };

            $invoiceCreditNotes = $creditNotes->get($invoice->id, collect());
            $creditAmount = (float) $invoiceCreditNotes->sum('applied_amount');
            $originalAmount = (float) $invoice->total_amount;
            $outstanding = (float) $invoice->balance;
            $paidAmount = max(0.0, $originalAmount - $outstanding - $creditAmount);

            $student = $invoice->student;
            $studentForm = $student?->grade ?? 'Unassigned';
            $studentClass = $student?->class_name ?? 'Unassigned';
            $studentName = $student ? "{$student->first_name} {$student->last_name}" : ($invoice->guardian ? $invoice->guardian->name : 'N/A');
            $admissionNumber = $student?->admission_number ?? 'N/A';

            $row = [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'student_id' => $student?->id,
                'student_name' => $studentName,
                'admission_number' => $admissionNumber,
                'form' => $studentForm,
                'class_name' => $studentClass,
                'academic_year' => $invoice->academic_year ?? 'N/A',
                'term' => $invoice->term ?? 'N/A',
                'fee_type' => $invoice->type ?? 'Tuition',
                'issued_at' => $invoice->issued_at ? $invoice->issued_at->format('Y-m-d') : 'N/A',
                'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : ($invoice->issued_at ? $invoice->issued_at->format('Y-m-d') : 'N/A'),
                'days_overdue' => $daysOverdue,
                'original_amount' => $originalAmount,
                'paid_amount' => $paidAmount,
                'credit_amount' => $creditAmount,
                'outstanding' => $outstanding,
                'bucket' => $bucketKey,
                'bucket_label' => match($bucketKey) {
                    'current' => 'Current',
                    '1_30' => '1–30 Days',
                    '31_60' => '31–60 Days',
                    '61_90' => '61–90 Days',
                    '91_120' => '91–120 Days',
                    '120_plus' => '120+ Days',
                },
                'current_amount' => $bucketKey === 'current' ? $outstanding : 0.0,
                'bucket_1_30_amount' => $bucketKey === '1_30' ? $outstanding : 0.0,
                'bucket_31_60_amount' => $bucketKey === '31_60' ? $outstanding : 0.0,
                'bucket_61_90_amount' => $bucketKey === '61_90' ? $outstanding : 0.0,
                'bucket_91_120_amount' => $bucketKey === '91_120' ? $outstanding : 0.0,
                'bucket_120_plus_amount' => $bucketKey === '120_plus' ? $outstanding : 0.0,
            ];

            $rows->push($row);

            $totalInvoiced += $originalAmount;
            $totalPaid += $paidAmount;
            $totalCredit += $creditAmount;
            $totalOutstanding += $outstanding;

            $bucketTotals[$bucketKey] += $outstanding;
            $bucketCounts[$bucketKey]++;
        }

        // Grouping logic
        $groupedData = collect();
        if ($groupBy === 'form') {
            $groupedData = $rows->groupBy('form')->map(function ($groupRows, $key) {
                return [
                    'group_key' => $key,
                    'group_label' => 'Form: ' . $key,
                    'rows' => $groupRows,
                    'total_invoiced' => $groupRows->sum('original_amount'),
                    'total_paid' => $groupRows->sum('paid_amount'),
                    'total_credit' => $groupRows->sum('credit_amount'),
                    'total_outstanding' => $groupRows->sum('outstanding'),
                    'current' => $groupRows->sum('current_amount'),
                    'bucket_1_30' => $groupRows->sum('bucket_1_30_amount'),
                    'bucket_31_60' => $groupRows->sum('bucket_31_60_amount'),
                    'bucket_61_90' => $groupRows->sum('bucket_61_90_amount'),
                    'bucket_91_120' => $groupRows->sum('bucket_91_120_amount'),
                    'bucket_120_plus' => $groupRows->sum('bucket_120_plus_amount'),
                ];
            });
        } elseif ($groupBy === 'class') {
            $groupedData = $rows->groupBy('class_name')->map(function ($groupRows, $key) {
                return [
                    'group_key' => $key,
                    'group_label' => 'Class: ' . $key,
                    'rows' => $groupRows,
                    'total_invoiced' => $groupRows->sum('original_amount'),
                    'total_paid' => $groupRows->sum('paid_amount'),
                    'total_credit' => $groupRows->sum('credit_amount'),
                    'total_outstanding' => $groupRows->sum('outstanding'),
                    'current' => $groupRows->sum('current_amount'),
                    'bucket_1_30' => $groupRows->sum('bucket_1_30_amount'),
                    'bucket_31_60' => $groupRows->sum('bucket_31_60_amount'),
                    'bucket_61_90' => $groupRows->sum('bucket_61_90_amount'),
                    'bucket_91_120' => $groupRows->sum('bucket_91_120_amount'),
                    'bucket_120_plus' => $groupRows->sum('bucket_120_plus_amount'),
                ];
            });
        } elseif ($groupBy === 'student') {
            $groupedData = $rows->groupBy('student_name')->map(function ($groupRows, $key) {
                return [
                    'group_key' => $key,
                    'group_label' => $key . ' (' . ($groupRows->first()['admission_number'] ?? '') . ')',
                    'rows' => $groupRows,
                    'total_invoiced' => $groupRows->sum('original_amount'),
                    'total_paid' => $groupRows->sum('paid_amount'),
                    'total_credit' => $groupRows->sum('credit_amount'),
                    'total_outstanding' => $groupRows->sum('outstanding'),
                    'current' => $groupRows->sum('current_amount'),
                    'bucket_1_30' => $groupRows->sum('bucket_1_30_amount'),
                    'bucket_31_60' => $groupRows->sum('bucket_31_60_amount'),
                    'bucket_61_90' => $groupRows->sum('bucket_61_90_amount'),
                    'bucket_91_120' => $groupRows->sum('bucket_91_120_amount'),
                    'bucket_120_plus' => $groupRows->sum('bucket_120_plus_amount'),
                ];
            });
        } elseif ($groupBy === 'fee_type') {
            $groupedData = $rows->groupBy('fee_type')->map(function ($groupRows, $key) {
                return [
                    'group_key' => $key,
                    'group_label' => 'Fee Type: ' . ucfirst($key),
                    'rows' => $groupRows,
                    'total_invoiced' => $groupRows->sum('original_amount'),
                    'total_paid' => $groupRows->sum('paid_amount'),
                    'total_credit' => $groupRows->sum('credit_amount'),
                    'total_outstanding' => $groupRows->sum('outstanding'),
                    'current' => $groupRows->sum('current_amount'),
                    'bucket_1_30' => $groupRows->sum('bucket_1_30_amount'),
                    'bucket_31_60' => $groupRows->sum('bucket_31_60_amount'),
                    'bucket_61_90' => $groupRows->sum('bucket_61_90_amount'),
                    'bucket_91_120' => $groupRows->sum('bucket_91_120_amount'),
                    'bucket_120_plus' => $groupRows->sum('bucket_120_plus_amount'),
                ];
            });
        }

        // Available filter options for dropdowns
        $availableYears = Invoice::where('school_id', $school->id)->distinct()->pluck('academic_year')->filter()->sortDesc()->values();
        $availableForms = Student::where('school_id', $school->id)->distinct()->pluck('grade')->filter()->sort()->values();
        $availableClasses = Student::where('school_id', $school->id)->distinct()->pluck('class_name')->filter()->sort()->values();

        return [
            'rows' => $rows,
            'grouped_data' => $groupedData,
            'group_by' => $groupBy,
            'as_of_date' => $asOf->format('Y-m-d'),
            'totals' => [
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'total_credit' => $totalCredit,
                'total_outstanding' => $totalOutstanding,
                'current' => $bucketTotals['current'],
                'bucket_1_30' => $bucketTotals['1_30'],
                'bucket_31_60' => $bucketTotals['31_60'],
                'bucket_61_90' => $bucketTotals['61_90'],
                'bucket_91_120' => $bucketTotals['91_120'],
                'bucket_120_plus' => $bucketTotals['120_plus'],
            ],
            'bucket_counts' => $bucketCounts,
            'filters' => $filters,
            'available_years' => $availableYears,
            'available_forms' => $availableForms,
            'available_classes' => $availableClasses,
        ];
    }

    /**
     * Generate detailed, tenant-safe student financial statement.
     */
    public function getStudentStatement(School $school, Student $student, array $filters = []): array
    {
        if ($student->school_id !== $school->id) {
            abort(403, 'Unauthorized student statement access.');
        }

        $academicYear = $filters['academic_year'] ?? null;
        $term = $filters['term'] ?? null;
        $startDate = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;

        // Opening balance calculation: sum of all transactions prior to startDate (or prior terms)
        $openingBalance = 0.0;
        if ($startDate) {
            $priorInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('issued_at', '<', $startDate)
                ->sum('total_amount');

            $priorPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('paid_at', '<', $startDate)
                ->sum('amount');

            $priorCredits = CreditNote::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('credit_note_date', '<', $startDate)
                ->whereIn('status', ['applied', 'issued'])
                ->sum('applied_amount');

            $openingBalance = (float)($priorInvoices - $priorPayments - $priorCredits);
        }

        // Invoices Query
        $invoicesQuery = Invoice::with(['items.feeStructure'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id);

        if ($academicYear) {
            $invoicesQuery->where('academic_year', $academicYear);
        }
        if ($term) {
            $invoicesQuery->where('term', $term);
        }
        if ($startDate && $endDate) {
            $invoicesQuery->whereBetween('issued_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $invoicesQuery->where('issued_at', '>=', $startDate);
        } elseif ($endDate) {
            $invoicesQuery->where('issued_at', '<=', $endDate);
        }

        $invoices = $invoicesQuery->orderBy('issued_at', 'asc')->get();

        // Payments Query
        $paymentsQuery = Payment::with('allocations.invoice')
            ->where('school_id', $school->id)
            ->where('student_id', $student->id);

        if ($startDate && $endDate) {
            $paymentsQuery->whereBetween('paid_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $paymentsQuery->where('paid_at', '>=', $startDate);
        } elseif ($endDate) {
            $paymentsQuery->where('paid_at', '<=', $endDate);
        }

        $payments = $paymentsQuery->orderBy('paid_at', 'asc')->get();

        // Credit Notes Query
        $creditNotesQuery = CreditNote::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['applied', 'issued']);

        if ($startDate && $endDate) {
            $creditNotesQuery->whereBetween('credit_note_date', [$startDate, $endDate]);
        }

        $creditNotes = $creditNotesQuery->orderBy('credit_note_date', 'asc')->get();

        // Combine into chronological events ledger
        $events = collect();

        foreach ($invoices as $invoice) {
            $events->push([
                'type' => 'invoice',
                'date' => $invoice->issued_at ? $invoice->issued_at->format('Y-m-d') : '',
                'raw_date' => $invoice->issued_at ?? now(),
                'reference' => $invoice->number,
                'description' => 'Invoice ' . $invoice->number . ($invoice->type ? ' (' . ucfirst($invoice->type) . ')' : '') . ($invoice->term ? ' - ' . $invoice->term : ''),
                'debit' => (float)$invoice->total_amount,
                'credit' => 0.0,
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'description' => $item->description,
                        'category' => $item->category,
                        'amount' => (float)$item->line_total,
                    ];
                }),
                'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A',
                'status' => $invoice->status,
            ]);
        }

        foreach ($payments as $payment) {
            $events->push([
                'type' => 'payment',
                'date' => $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '',
                'raw_date' => $payment->paid_at ?? now(),
                'reference' => $payment->reference ?? 'PMT-' . $payment->id,
                'description' => 'Payment received via ' . ucfirst($payment->method ?? 'Cash'),
                'debit' => 0.0,
                'credit' => (float)$payment->amount,
                'items' => collect(),
                'due_date' => null,
                'status' => $payment->status,
            ]);
        }

        foreach ($creditNotes as $cn) {
            $events->push([
                'type' => 'credit_note',
                'date' => $cn->credit_note_date ? $cn->credit_note_date->format('Y-m-d') : '',
                'raw_date' => $cn->credit_note_date ?? now(),
                'reference' => $cn->credit_note_number,
                'description' => 'Credit Note: ' . ($cn->reason ?? 'Fee Adjustment'),
                'debit' => 0.0,
                'credit' => (float)$cn->applied_amount,
                'items' => collect(),
                'due_date' => null,
                'status' => $cn->status,
            ]);
        }

        // Sort events chronologically
        $sortedEvents = $events->sortBy(function ($event) {
            return $event['raw_date'];
        })->values();

        // Calculate running balances
        $runningBalance = $openingBalance;
        $totalDebits = 0.0;
        $totalCredits = 0.0;

        $ledgerRows = $sortedEvents->map(function ($event) use (&$runningBalance, &$totalDebits, &$totalCredits) {
            $debit = (float)$event['debit'];
            $credit = (float)$event['credit'];
            $runningBalance += ($debit - $credit);
            $totalDebits += $debit;
            $totalCredits += $credit;

            $event['running_balance'] = $runningBalance;
            return $event;
        });

        $closingBalance = $runningBalance;

        return [
            'student' => $student,
            'school' => $school,
            'academic_year' => $academicYear,
            'term' => $term,
            'start_date' => $startDate?->format('Y-m-d'),
            'end_date' => $endDate?->format('Y-m-d'),
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'ledger_rows' => $ledgerRows,
        ];
    }

    /**
     * Itemized Fee Collection Report with payment method & class breakdowns.
     */
    public function getFeeCollections(School $school, array $filters = []): array
    {
        $startDate = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : now()->startOfMonth()->startOfDay();
        $endDate = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : now()->endOfDay();
        $paymentMethod = $filters['payment_method'] ?? null;
        $form = $filters['form'] ?? null;
        $className = $filters['class_name'] ?? null;
        $academicYear = $filters['academic_year'] ?? null;
        $term = $filters['term'] ?? null;

        // Query Payments
        $paymentsQuery = Payment::with(['student', 'guardian', 'allocations.invoice'])
            ->where('school_id', $school->id)
            ->whereBetween('paid_at', [$startDate, $endDate]);

        if ($paymentMethod) {
            $paymentsQuery->where('method', $paymentMethod);
        }

        if ($form) {
            $paymentsQuery->whereHas('student', function ($q) use ($form) {
                $q->where('grade', $form);
            });
        }

        if ($className) {
            $paymentsQuery->whereHas('student', function ($q) use ($className) {
                $q->where('class_name', $className);
            });
        }

        if ($academicYear) {
            $paymentsQuery->whereHas('allocations.invoice', function ($q) use ($academicYear) {
                $q->where('academic_year', $academicYear);
            });
        }

        if ($term) {
            $paymentsQuery->whereHas('allocations.invoice', function ($q) use ($term) {
                $q->where('term', $term);
            });
        }

        $payments = $paymentsQuery->orderBy('paid_at', 'desc')->get();

        // Query Receipts (for direct cash/bank receipts)
        $receiptsQuery = Receipt::with(['customer', 'creator', 'items'])
            ->where('school_id', $school->id)
            ->whereBetween('receipt_date', [$startDate, $endDate]);

        if ($paymentMethod) {
            $receiptsQuery->where('payment_method', $paymentMethod);
        }

        $receipts = $receiptsQuery->orderBy('receipt_date', 'desc')->get();

        $collections = collect();

        foreach ($payments as $payment) {
            $student = $payment->student;
            $firstInvoice = $payment->allocations->first()?->invoice;

            $collections->push([
                'id' => 'PMT-' . $payment->id,
                'source' => 'Payment',
                'date' => $payment->paid_at ? $payment->paid_at->format('Y-m-d') : 'N/A',
                'reference' => $payment->reference ?? ('PMT-' . $payment->id),
                'student_name' => $student ? "{$student->first_name} {$student->last_name}" : ($payment->guardian ? $payment->guardian->name : 'N/A'),
                'admission_number' => $student?->admission_number ?? 'N/A',
                'form' => $student?->grade ?? 'Unassigned',
                'class_name' => $student?->class_name ?? 'Unassigned',
                'payment_method' => ucfirst($payment->method ?? 'Cash'),
                'fee_type' => $firstInvoice?->type ?? 'School Fees',
                'amount' => (float)$payment->amount,
                'cashier' => 'System',
                'status' => $payment->status ?? 'Completed',
            ]);
        }

        foreach ($receipts as $receipt) {
            $collections->push([
                'id' => 'RCT-' . $receipt->id,
                'source' => 'Receipt',
                'date' => $receipt->receipt_date ? $receipt->receipt_date->format('Y-m-d') : 'N/A',
                'reference' => $receipt->receipt_number,
                'student_name' => $receipt->customer_name ?? ($receipt->customer?->name ?? 'Direct Payer'),
                'admission_number' => 'N/A',
                'form' => 'General',
                'class_name' => 'General',
                'payment_method' => ucfirst($receipt->payment_method ?? 'Cash'),
                'fee_type' => $receipt->items->first()?->description ?? ($receipt->type ?? 'Receipt'),
                'amount' => (float)$receipt->grand_total,
                'cashier' => $receipt->creator?->name ?? 'Cashier',
                'status' => 'Completed',
            ]);
        }

        $sortedCollections = $collections->sortByDesc('date')->values();
        $totalCollected = $sortedCollections->sum('amount');
        $transactionCount = $sortedCollections->count();

        // Breakdowns
        $byMethod = $sortedCollections->groupBy('payment_method')->map(function ($group, $method) {
            return [
                'method' => $method,
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total');

        $byForm = $sortedCollections->groupBy('form')->map(function ($group, $formName) {
            return [
                'form' => $formName,
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total');

        $byClass = $sortedCollections->groupBy('class_name')->map(function ($group, $cName) {
            return [
                'class_name' => $cName,
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total');

        return [
            'collections' => $sortedCollections,
            'total_collected' => $totalCollected,
            'transaction_count' => $transactionCount,
            'by_method' => $byMethod,
            'by_form' => $byForm,
            'by_class' => $byClass,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'filters' => $filters,
        ];
    }

    /**
     * Outstanding Fees register grouped by student, form, or class.
     */
    public function getOutstandingFees(School $school, array $filters = []): array
    {
        $minBalance = isset($filters['min_balance']) && is_numeric($filters['min_balance']) ? (float)$filters['min_balance'] : 0.01;
        $academicYear = $filters['academic_year'] ?? null;
        $term = $filters['term'] ?? null;
        $form = $filters['form'] ?? null;
        $className = $filters['class_name'] ?? null;
        $groupBy = $filters['group_by'] ?? 'form'; // 'form', 'class', 'none'

        $query = Invoice::where('school_id', $school->id)
            ->where('balance', '>=', $minBalance)
            ->with(['student', 'guardian']);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }
        if ($term) {
            $query->where('term', $term);
        }
        if ($form) {
            $query->whereHas('student', function ($q) use ($form) {
                $q->where('grade', $form);
            });
        }
        if ($className) {
            $query->whereHas('student', function ($q) use ($className) {
                $q->where('class_name', $className);
            });
        }

        $invoices = $query->get();

        // Aggregate by student
        $studentInvoices = $invoices->groupBy(function ($inv) {
            return $inv->student_id ?: 'guardian_' . $inv->guardian_id;
        });

        $rows = collect();
        $totalCharges = 0.0;
        $totalPaid = 0.0;
        $totalOutstanding = 0.0;

        foreach ($studentInvoices as $key => $invGroup) {
            $firstInv = $invGroup->first();
            $student = $firstInv->student;
            $studentName = $student ? "{$student->first_name} {$student->last_name}" : ($firstInv->guardian ? $firstInv->guardian->name : 'N/A');
            $admissionNumber = $student?->admission_number ?? 'N/A';
            $formName = $student?->grade ?? 'Unassigned';
            $classNameVal = $student?->class_name ?? 'Unassigned';

            $charges = (float)$invGroup->sum('total_amount');
            $balance = (float)$invGroup->sum('balance');
            $paid = max(0.0, $charges - $balance);

            $rows->push([
                'student_id' => $student?->id,
                'student_name' => $studentName,
                'admission_number' => $admissionNumber,
                'form' => $formName,
                'class_name' => $classNameVal,
                'invoice_count' => $invGroup->count(),
                'total_charges' => $charges,
                'total_paid' => $paid,
                'outstanding' => $balance,
                'collection_rate' => $charges > 0 ? round(($paid / $charges) * 100, 1) : 0,
            ]);

            $totalCharges += $charges;
            $totalPaid += $paid;
            $totalOutstanding += $balance;
        }

        $sortedRows = $rows->sortByDesc('outstanding')->values();

        $groupedData = collect();
        if ($groupBy === 'form') {
            $groupedData = $sortedRows->groupBy('form')->map(function ($group, $key) {
                return [
                    'group_label' => 'Form: ' . $key,
                    'rows' => $group,
                    'total_charges' => $group->sum('total_charges'),
                    'total_paid' => $group->sum('total_paid'),
                    'total_outstanding' => $group->sum('outstanding'),
                    'student_count' => $group->count(),
                ];
            });
        } elseif ($groupBy === 'class') {
            $groupedData = $sortedRows->groupBy('class_name')->map(function ($group, $key) {
                return [
                    'group_label' => 'Class: ' . $key,
                    'rows' => $group,
                    'total_charges' => $group->sum('total_charges'),
                    'total_paid' => $group->sum('total_paid'),
                    'total_outstanding' => $group->sum('outstanding'),
                    'student_count' => $group->count(),
                ];
            });
        }

        return [
            'rows' => $sortedRows,
            'grouped_data' => $groupedData,
            'group_by' => $groupBy,
            'total_students' => $sortedRows->count(),
            'total_charges' => $totalCharges,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'overall_collection_rate' => $totalCharges > 0 ? round(($totalPaid / $totalCharges) * 100, 1) : 0,
            'filters' => $filters,
        ];
    }

    /**
     * Fee Collection Periodic Summary & Trends (Daily, Weekly, Monthly, Term, Academic Year).
     */
    public function getCollectionSummary(School $school, array $filters = []): array
    {
        $currentYear = (int)($filters['year'] ?? now()->year);
        $previousYear = $currentYear - 1;

        // Daily collections (past 30 days)
        $daily = Payment::where('school_id', $school->id)
            ->where('paid_at', '>=', now()->subDays(30)->startOfDay())
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy(DB::raw('DATE(paid_at)'), 'desc')
            ->get();

        // Monthly collections for current year
        $monthlyCurrent = Payment::where('school_id', $school->id)
            ->whereYear('paid_at', $currentYear)
            ->select(DB::raw('MONTH(paid_at) as month'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('MONTH(paid_at)'))
            ->pluck('total', 'month');

        $monthlyPrevious = Payment::where('school_id', $school->id)
            ->whereYear('paid_at', $previousYear)
            ->select(DB::raw('MONTH(paid_at) as month'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('MONTH(paid_at)'))
            ->pluck('total', 'month');

        $monthlyComparison = [];
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        foreach ($months as $num => $name) {
            $curr = (float)($monthlyCurrent[$num] ?? 0.0);
            $prev = (float)($monthlyPrevious[$num] ?? 0.0);
            $diff = $curr - $prev;
            $percent = $prev > 0 ? round(($diff / $prev) * 100, 1) : ($curr > 0 ? 100.0 : 0.0);

            $monthlyComparison[] = [
                'month_num' => $num,
                'month_name' => $name,
                'current_year' => $curr,
                'previous_year' => $prev,
                'variance' => $diff,
                'variance_percent' => $percent,
            ];
        }

        // Term Collections for current year
        $termCollections = Invoice::where('school_id', $school->id)
            ->where('academic_year', 'like', "%{$currentYear}%")
            ->select('term', DB::raw('SUM(total_amount) as total_billed'), DB::raw('SUM(total_amount - balance) as total_collected'))
            ->groupBy('term')
            ->get()
            ->map(function ($item) {
                $billed = (float)$item->total_billed;
                $collected = (float)$item->total_collected;
                return [
                    'term' => $item->term ?? 'Unknown',
                    'billed' => $billed,
                    'collected' => $collected,
                    'outstanding' => $billed - $collected,
                    'collection_rate' => $billed > 0 ? round(($collected / $billed) * 100, 1) : 0,
                ];
            });

        $totalYearCurrent = (float)Payment::where('school_id', $school->id)->whereYear('paid_at', $currentYear)->sum('amount');
        $totalYearPrevious = (float)Payment::where('school_id', $school->id)->whereYear('paid_at', $previousYear)->sum('amount');
        $yearGrowth = $totalYearPrevious > 0 ? round((($totalYearCurrent - $totalYearPrevious) / $totalYearPrevious) * 100, 1) : 0;

        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'total_current_year' => $totalYearCurrent,
            'total_previous_year' => $totalYearPrevious,
            'year_growth_percent' => $yearGrowth,
            'daily_collections' => $daily,
            'monthly_comparison' => $monthlyComparison,
            'term_collections' => $termCollections,
        ];
    }

    /**
     * Income & Expenditure calculation from Chart of Accounts & Journal Entries.
     */
    public function getIncomeExpenditure(School $school, array $filters = []): array
    {
        $startDate = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : now()->startOfYear()->startOfDay();
        $endDate = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : now()->endOfDay();

        // Revenue Accounts
        $revenueAccounts = Account::where('school_id', $school->id)
            ->where('type', 'revenue')
            ->with(['journalEntries' => function ($q) use ($school, $startDate, $endDate) {
                $q->whereHas('batch', function ($bQ) use ($school, $startDate, $endDate) {
                    $bQ->where('school_id', $school->id)
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                });
            }])
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                $debits = (float)$account->journalEntries->where('entry_type', 'debit')->sum('amount');
                $credits = (float)$account->journalEntries->where('entry_type', 'credit')->sum('amount');
                $netRevenue = $credits - $debits;
                return [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'category' => $account->category ?? 'Operating Revenue',
                    'amount' => $netRevenue,
                ];
            })->filter(function ($item) {
                return abs($item['amount']) > 0.001;
            })->values();

        // Expense Accounts
        $expenseAccounts = Account::where('school_id', $school->id)
            ->where('type', 'expense')
            ->with(['journalEntries' => function ($q) use ($school, $startDate, $endDate) {
                $q->whereHas('batch', function ($bQ) use ($school, $startDate, $endDate) {
                    $bQ->where('school_id', $school->id)
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                });
            }])
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                $debits = (float)$account->journalEntries->where('entry_type', 'debit')->sum('amount');
                $credits = (float)$account->journalEntries->where('entry_type', 'credit')->sum('amount');
                $netExpense = $debits - $credits;
                return [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'category' => $account->category ?? 'Operating Expense',
                    'amount' => $netExpense,
                ];
            })->filter(function ($item) {
                return abs($item['amount']) > 0.001;
            })->values();

        $totalIncome = (float)$revenueAccounts->sum('amount');
        $totalExpenditure = (float)$expenseAccounts->sum('amount');
        $netSurplusDeficit = $totalIncome - $totalExpenditure;

        $incomeByCategory = $revenueAccounts->groupBy('category')->map(function ($items, $cat) {
            return [
                'category' => $cat,
                'total' => $items->sum('amount'),
                'accounts' => $items,
            ];
        });

        $expenditureByCategory = $expenseAccounts->groupBy('category')->map(function ($items, $cat) {
            return [
                'category' => $cat,
                'total' => $items->sum('amount'),
                'accounts' => $items,
            ];
        });

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'revenue_accounts' => $revenueAccounts,
            'expense_accounts' => $expenseAccounts,
            'income_by_category' => $incomeByCategory,
            'expenditure_by_category' => $expenditureByCategory,
            'total_income' => $totalIncome,
            'total_expenditure' => $totalExpenditure,
            'net_surplus_deficit' => $netSurplusDeficit,
        ];
    }

    /**
     * Cashbook & Bank Summary for tenant school.
     */
    public function getCashbookBankSummary(School $school): array
    {
        $bankAccounts = BankAccount::where('school_id', $school->id)->get();
        $cashAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->whereIn('code', ['1100', '1301', '1302', '1303', '1010'])
            ->get();

        $recentCashbook = Cashbook::where('school_id', $school->id)
            ->latest('transaction_date')
            ->take(15)
            ->get();

        $totalBankBalance = (float)$bankAccounts->sum('current_balance');
        $totalCashReceipts = (float)Receipt::where('school_id', $school->id)->sum('grand_total');
        $totalPayments = (float)Payment::where('school_id', $school->id)->sum('amount');

        return [
            'bank_accounts' => $bankAccounts,
            'cash_accounts' => $cashAccounts,
            'recent_cashbook' => $recentCashbook,
            'total_bank_balance' => $totalBankBalance,
            'total_cash_receipts' => $totalCashReceipts,
            'total_payments' => $totalPayments,
        ];
    }

    /**
     * Management Finance Dashboard KPIs & Aggregations.
     */
    public function getFinanceDashboardKPIs(School $school): array
    {
        $currentYear = now()->year;

        $totalBilled = (float)Invoice::where('school_id', $school->id)->sum('total_amount');
        $totalCollected = (float)Payment::where('school_id', $school->id)->sum('amount');
        $totalOutstanding = (float)Invoice::where('school_id', $school->id)->sum('balance');
        $collectionRate = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0;

        // Aging breakdown
        $agingData = $this->getDebtorsAging($school);

        // Top 10 Debtors
        $topDebtors = Invoice::where('school_id', $school->id)
            ->where('balance', '>', 0)
            ->with(['student', 'guardian'])
            ->select('student_id', 'guardian_id', DB::raw('SUM(balance) as total_debt'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('student_id', 'guardian_id')
            ->orderByDesc('total_debt')
            ->take(10)
            ->get()
            ->map(function ($item) {
                $student = $item->student;
                return [
                    'student_name' => $student ? "{$student->first_name} {$student->last_name}" : ($item->guardian ? $item->guardian->name : 'N/A'),
                    'admission_number' => $student?->admission_number ?? 'N/A',
                    'form' => $student?->grade ?? 'N/A',
                    'class_name' => $student?->class_name ?? 'N/A',
                    'total_debt' => (float)$item->total_debt,
                    'invoice_count' => $item->invoice_count,
                ];
            });

        // Collections by payment method
        $collectionsByMethod = Payment::where('school_id', $school->id)
            ->select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => ucfirst($item->method ?? 'Cash'),
                    'total' => (float)$item->total,
                    'count' => $item->count,
                ];
            });

        // Outstanding by Form
        $outstandingByForm = Invoice::where('school_id', $school->id)
            ->where('balance', '>', 0)
            ->whereHas('student')
            ->with('student')
            ->get()
            ->groupBy(function ($inv) {
                return $inv->student?->grade ?? 'Unassigned';
            })->map(function ($invs, $formName) {
                return [
                    'form' => $formName,
                    'total_outstanding' => (float)$invs->sum('balance'),
                    'student_count' => $invs->pluck('student_id')->unique()->count(),
                ];
            })->sortByDesc('total_outstanding')->values();

        return [
            'total_billed' => $totalBilled,
            'total_collected' => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'collection_rate' => $collectionRate,
            'aging_totals' => $agingData['totals'],
            'bucket_counts' => $agingData['bucket_counts'],
            'top_debtors' => $topDebtors,
            'collections_by_method' => $collectionsByMethod,
            'outstanding_by_form' => $outstandingByForm,
        ];
    }
}
