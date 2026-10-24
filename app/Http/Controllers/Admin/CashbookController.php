<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Cashbook;
use App\Models\Account;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Rules\TenantExists;
use Illuminate\Http\Request;

class CashbookController extends Controller
{
    public function index(Request $request)
    {
        $school = auth()->user()->school;

        $query = Cashbook::where('school_id', $school->id)
            ->with('account', 'relatedInvoice', 'relatedPayment', 'createdBy', 'project', 'costCenter');

        // Filters
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->cost_center_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(50);

        // Get bank accounts from chart of accounts
        $bankAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function($query) {
                $query->where('category', 'bank')
                      ->orWhere('code', 'like', '13%'); // Bank accounts typically start with 13xx
            })
            ->active()
            ->orderBy('code')
            ->get();

        $projects = Project::where('school_id', $school->id)->orderBy('name')->get();
        $costCenters = Department::where('school_id', $school->id)->orderBy('name')->get();

        $categories = ['fees', 'salaries', 'supplies', 'maintenance', 'utilities', 'rent', 'opening_balance', 'transfer', 'other'];
        $transactionTypes = ['income', 'expense', 'transfer'];

        return view('admin.cashbook.index', compact('transactions', 'bankAccounts', 'categories', 'transactionTypes', 'projects', 'costCenters'));
    }

    public function create()
    {
        $school = auth()->user()->school;

        // Get bank accounts from chart of accounts
        $bankAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function($query) {
                $query->where('category', 'bank')
                      ->orWhere('code', 'like', '13%'); // Bank accounts typically start with 13xx
            })
            ->active()
            ->orderBy('code')
            ->get();

        $projects = Project::where('school_id', $school->id)->orderBy('name')->get();
        $costCenters = Department::where('school_id', $school->id)->orderBy('name')->get();

        $categories = ['fees', 'salaries', 'supplies', 'maintenance', 'utilities', 'rent', 'opening_balance', 'transfer', 'other'];
        $paymentMethods = ['cash', 'bank_transfer', 'check', 'credit_card', 'mobile_money'];

        return view('admin.cashbook.create', compact('bankAccounts', 'categories', 'paymentMethods', 'projects', 'costCenters'));
    }

    public function store(Request $request)
    {
        $school = auth()->user()?->school;
        if (! $school) {
            abort(403);
        }

        $request->validate([
            'account_id' => ['required', TenantExists::make('accounts')],
            'project_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'transaction_type' => 'required|in:income,expense,transfer',
            'classification' => 'nullable|string|in:debtor_receipt,student_advance,direct_revenue,supplier_payment,direct_expense,bank_transfer,refund,clearing,opening_balance',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:10',
            'transaction_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'vote_code' => 'nullable|string|max:50',
            'fund_code' => 'nullable|string|max:50',
            'student_id' => 'nullable|integer',
            'vendor_id' => 'nullable|integer',
            'counterparty_name' => 'nullable|string|max:255',
            'supporting_document_ref' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $account = Account::where('school_id', $school->id)->findOrFail($request->account_id);

        // Calculate balance after transaction
        $currentBalance = $account->current_balance ?? 0;
        $balanceAfter = $request->transaction_type === 'expense'
            ? $currentBalance - $request->amount
            : $currentBalance + $request->amount;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $transaction = Cashbook::create([
                'school_id' => $school->id,
                'account_id' => $account->id,
                'project_id' => !empty($request->project_id) ? (int) $request->project_id : null,
                'cost_center_id' => !empty($request->cost_center_id) ? (int) $request->cost_center_id : null,
                'transaction_type' => $request->transaction_type,
                'classification' => $request->classification,
                'category' => $request->category,
                'description' => $request->description,
                'amount' => $request->amount,
                'currency' => $request->currency ?? 'USD',
                'balance_after' => $balanceAfter,
                'transaction_date' => $request->transaction_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'voucher_number' => $request->reference_number,
                'vote_code' => $request->vote_code,
                'fund_code' => $request->fund_code,
                'student_id' => $request->student_id,
                'vendor_id' => $request->vendor_id,
                'counterparty_name' => $request->counterparty_name,
                'supporting_document_ref' => $request->supporting_document_ref,
                'notes' => $request->notes,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            // Post manually created transaction to general ledger
            app(\App\Services\AccountingService::class)->postManualCashbookTransaction($transaction);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('admin.cashbook.index')
                ->with('success', 'Transaction recorded and posted to general ledger successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to post manual cashbook entry to accounting: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Financial posting failed: ' . $e->getMessage()]);
        }
    }


    public function show(Cashbook $cashbook)
    {
        $this->authorizeSchoolAccess($cashbook);
        return view('admin.cashbook.show', compact('cashbook'));
    }

    public function edit(Cashbook $cashbook)
    {
        $this->authorizeSchoolAccess($cashbook);

        $school = auth()->user()->school;
        $bankAccounts = BankAccount::where('school_id', $school->id)->where('is_active', true)->get();
        $accounts = Account::where('school_id', $school->id)->whereIn('category', ['cash', 'bank'])->where('is_active', true)->get();
        $categories = ['fees', 'salaries', 'supplies', 'maintenance', 'utilities', 'rent', 'opening_balance', 'transfer', 'other'];
        $paymentMethods = ['cash', 'bank_transfer', 'check', 'credit_card', 'mobile_money'];

        return view('admin.cashbook.edit', compact('cashbook', 'bankAccounts', 'accounts', 'categories', 'paymentMethods'));
    }

    public function update(Request $request, Cashbook $cashbook)
    {
        $this->authorizeSchoolAccess($cashbook);
        $school = auth()->user()?->school;

        $request->validate([
            'account_id' => ['nullable', TenantExists::make('accounts')],
            'bank_account_id' => ['nullable', TenantExists::make('bank_accounts')],
            'transaction_type' => 'required|in:income,expense,transfer',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $targetAccountId = $request->account_id;
        if (! $targetAccountId && $request->bank_account_id) {
            $bankAcc = BankAccount::where('school_id', $school->id)->find($request->bank_account_id);
            $targetAccountId = $bankAcc?->account_id;
        }

        $account = Account::where('school_id', $school->id)->findOrFail($targetAccountId ?? $cashbook->account_id);

        $cashbook->update([
            'account_id' => $account->id,
            'transaction_type' => $request->transaction_type,
            'category' => $request->category,
            'description' => $request->description,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.cashbook.index')
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Cashbook $cashbook)
    {
        $this->authorizeSchoolAccess($cashbook);

        try {
            app(\App\Services\Finance\CashbookReversalService::class)->reverse(
                $cashbook,
                request('reason', 'User requested reversal from cashbook management')
            );

            return redirect()->route('admin.cashbook.index')
                ->with('success', 'Transaction safely reversed in cashbook and General Ledger.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cashbook reversal failed: ' . $e->getMessage());
            return redirect()->route('admin.cashbook.index')
                ->withErrors(['error' => 'Failed to reverse transaction: ' . $e->getMessage()]);
        }
    }

    protected function authorizeSchoolAccess(Cashbook $cashbook)
    {
        if ($cashbook->school_id !== auth()->user()?->school_id) {
            abort(403);
        }
    }
}
