<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\PaymentAllocation;
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

        return view('admin.payments.create', [
            'invoice' => $invoice,
            'school' => $school,
            'methods' => $methods,
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
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
        ]);

        $amount = (float) $validated['amount'];

        if ($amount > (float) $invoice->balance) {
            $amount = (float) $invoice->balance;
        }

        $method = $validated['method'];

        DB::transaction(function () use ($school, $invoice, $validated, $amount, $method) {
            $payment = Payment::create([
                'school_id' => $school->id,
                'student_id' => $invoice->student_id,
                'guardian_id' => $invoice->guardian_id,
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

            $this->accountingService->postPayment($payment, $invoice);
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', 'Payment recorded and invoice updated successfully.');
    }
}
