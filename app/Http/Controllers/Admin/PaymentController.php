<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Rules\TenantExists;
use App\Services\AccountingService;
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
                ->route('admin.invoices.index')
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

        return view('admin.payments.create', [
            'invoice' => $invoice,
            'school' => $school,
            'methods' => $methods,
            'bankAccounts' => $bankAccounts,
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
                ->route('admin.invoices.index')
                ->with('status', 'This invoice is already fully paid.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string'],
            'account_id' => ['nullable', 'integer', TenantExists::make('accounts')],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
        ]);

        $amount = (float) $validated['amount'];

        if ($amount > (float) $invoice->balance) {
            $amount = (float) $invoice->balance;
        }

        $method = $validated['method'];
        $accountId = !empty($validated['account_id']) ? (int) $validated['account_id'] : null;

        $payment = DB::transaction(function () use ($school, $invoice, $validated, $amount, $method, $accountId) {
            $payment = Payment::create([
                'school_id' => $school->id,
                'student_id' => $invoice->student_id,
                'guardian_id' => $invoice->guardian_id,
                'account_id' => $accountId,
                'method' => $method,
                'reference' => $validated['reference'] ?? null,
                'amount' => $amount,
                'paid_at' => $validated['paid_at'],
                'status' => 'completed',
            ]);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);

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
            ->with('status', 'Payment recorded and invoice updated successfully.')
            ->with('receipt_url', route('admin.payments.receipt', $payment))
            ->with('receipt_payment_id', $payment->id);
    }

    /**
     * Display and print official student fee payment receipt.
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
}
