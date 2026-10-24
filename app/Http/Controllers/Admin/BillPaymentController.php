<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Rules\TenantExists;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillPaymentController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Show form to record a payment for a supplier bill
     */
    public function create(Bill $bill)
    {
        $this->authorizeSchoolAccess($bill);

        if ($bill->status === 'paid' || $bill->balance <= 0) {
            return redirect()
                ->route('admin.bills.show', $bill)
                ->with('status', 'This bill has already been fully paid.');
        }

        $school = auth()->user()->school;

        // Active bank and cash accounts in General Ledger
        $accounts = Account::where('school_id', $school->id)
            ->whereIn('category', ['bank', 'cash'])
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();

        $bankAccounts = BankAccount::where('school_id', $school->id)
            ->where('is_active', true)
            ->get();

        return view('admin.bills.payments.create', compact('bill', 'accounts', 'bankAccounts'));
    }

    /**
     * Store a payment against a supplier bill with GL & cashbook posting
     */
    public function store(Request $request, Bill $bill)
    {
        $this->authorizeSchoolAccess($bill);
        $school = auth()->user()->school;

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:bank_transfer,cash,check,mobile_money,bank'],
            'account_id' => ['nullable', TenantExists::make('accounts')],
            'bank_account_id' => ['nullable', TenantExists::make('bank_accounts')],
            'reference' => ['nullable', 'string', 'max:255'],
            'check_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($request, $bill, $school, $validated) {
            // Lock bill for concurrent payment posting
            $lockedBill = Bill::where('id', $bill->id)->lockForUpdate()->firstOrFail();

            if ($lockedBill->balance <= 0) {
                return redirect()
                    ->route('admin.bills.show', $bill)
                    ->withErrors(['error' => 'This bill has already been fully settled.']);
            }

            $amount = (float) $validated['amount'];
            if ($amount > (float) $lockedBill->balance + 0.001) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['amount' => "Payment amount ($amount) exceeds remaining bill balance ({$lockedBill->balance})."]);
            }

            // Resolve target GL Account
            $targetAccountId = $validated['account_id'] ?? null;
            if (! $targetAccountId && !empty($validated['bank_account_id'])) {
                $bank = BankAccount::where('school_id', $school->id)->find($validated['bank_account_id']);
                $targetAccountId = $bank?->account_id;
            }

            // Create BillPayment record
            $payment = BillPayment::create([
                'school_id' => $school->id,
                'bill_id' => $lockedBill->id,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'account_id' => $targetAccountId,
                'amount' => $amount,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? ('BPAY-' . strtoupper(uniqid())),
                'check_number' => $validated['check_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Update Bill paid amount, balance, and status
            $newPaid = round((float) $lockedBill->paid_amount + $amount, 2);
            $newBalance = max(0, round((float) $lockedBill->total_amount - $newPaid, 2));
            $newStatus = $newBalance <= 0 ? 'paid' : 'partial';

            $lockedBill->update([
                'paid_amount' => $newPaid,
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            // Post to General Ledger and Cashbook
            try {
                $journalBatch = $this->accountingService->postBillPayment($payment);
            } catch (\Exception $e) {
                Log::error('Bill payment posting failed: ' . $e->getMessage());
                throw new \RuntimeException('Failed to post bill payment to General Ledger / Cashbook: ' . $e->getMessage(), 0, $e);
            }

            return redirect()
                ->route('admin.bills.show', $lockedBill)
                ->with('status', "Payment of USD " . number_format($amount, 2) . " successfully recorded and posted to General Ledger and Cashbook.");
        });
    }

    protected function authorizeSchoolAccess(Bill $bill)
    {
        $school = auth()->user()?->school;
        if (! $school || $bill->school_id !== $school->id) {
            abort(403);
        }
    }
}
