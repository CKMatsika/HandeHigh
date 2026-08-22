<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Cashbook;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Payment;
use App\Rules\TenantExists;
use Illuminate\Http\Request;

class CashbookController extends Controller
{
    public function index(Request $request)
    {
        $school = auth()->user()->school;
        
        $query = Cashbook::where('school_id', $school->id)
            ->with('account', 'relatedInvoice', 'relatedPayment', 'createdBy');

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
        
        $categories = ['fees', 'salaries', 'supplies', 'maintenance', 'utilities', 'rent', 'opening_balance', 'transfer', 'other'];
        $transactionTypes = ['income', 'expense', 'transfer'];

        return view('admin.cashbook.index', compact('transactions', 'bankAccounts', 'categories', 'transactionTypes'));
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
            
        $categories = ['fees', 'salaries', 'supplies', 'maintenance', 'utilities', 'rent', 'opening_balance', 'transfer', 'other'];
        $paymentMethods = ['cash', 'bank_transfer', 'check', 'credit_card', 'mobile_money'];
        
        return view('admin.cashbook.create', compact('bankAccounts', 'categories', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $school = auth()->user()?->school;
        if (! $school) {
            abort(403);
        }

        $request->validate([
            'account_id' => ['required', TenantExists::make('accounts')],
            'transaction_type' => 'required|in:income,expense,transfer',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $account = Account::where('school_id', $school->id)->findOrFail($request->account_id);
        
        // Calculate balance after transaction
        $currentBalance = $account->current_balance ?? 0;
        $balanceAfter = $request->transaction_type === 'expense' 
            ? $currentBalance - $request->amount 
            : $currentBalance + $request->amount;

        $transaction = Cashbook::create([
            'school_id' => $school->id,
            'account_id' => $account->id,
            'transaction_type' => $request->transaction_type,
            'category' => $request->category,
            'description' => $request->description,
            'amount' => $request->amount,
            'balance_after' => $balanceAfter,
            'transaction_date' => $request->transaction_date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        // Post manually created transaction to general ledger
        try {
            app(\App\Services\AccountingService::class)->postManualCashbookTransaction($transaction);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to post manual cashbook entry to accounting: ' . $e->getMessage());
        }

        return redirect()->route('admin.cashbook.index')
            ->with('success', 'Transaction recorded successfully.');
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
        $categories = ['fees', 'salaries', 'supplies', 'maintenance', 'utilities', 'rent', 'opening_balance', 'transfer', 'other'];
        $paymentMethods = ['cash', 'bank_transfer', 'check', 'credit_card', 'mobile_money'];
        
        return view('admin.cashbook.edit', compact('cashbook', 'bankAccounts', 'categories', 'paymentMethods'));
    }

    public function update(Request $request, Cashbook $cashbook)
    {
        $this->authorizeSchoolAccess($cashbook);
        $school = auth()->user()?->school;
        
        $request->validate([
            'bank_account_id' => ['required', TenantExists::make('bank_accounts')],
            'transaction_type' => 'required|in:income,expense,transfer',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $oldBankAccount = $cashbook->bank_account_id ? BankAccount::where('school_id', $school->id)->find($cashbook->bank_account_id) : null;
        $newBankAccount = BankAccount::where('school_id', $school->id)->findOrFail($request->bank_account_id);
        
        // Reverse old balance change if applicable
        if ($oldBankAccount) {
            $oldBalanceAfter = $cashbook->transaction_type === 'expense'
                ? $oldBankAccount->current_balance + $cashbook->amount
                : $oldBankAccount->current_balance - $cashbook->amount;
            $oldBankAccount->update(['current_balance' => $oldBalanceAfter]);
        }

        // Calculate new balance after transaction
        $newBalanceAfter = $request->transaction_type === 'expense'
            ? $newBankAccount->current_balance - $request->amount
            : $newBankAccount->current_balance + $request->amount;

        $cashbook->update([
            'bank_account_id' => $newBankAccount->id,
            'transaction_type' => $request->transaction_type,
            'category' => $request->category,
            'description' => $request->description,
            'amount' => $request->amount,
            'balance_after' => $newBalanceAfter,
            'transaction_date' => $request->transaction_date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
        ]);

        // Update new bank account balance
        $newBankAccount->update(['current_balance' => $newBalanceAfter]);

        return redirect()->route('admin.cashbook.index')
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Cashbook $cashbook)
    {
        $this->authorizeSchoolAccess($cashbook);
        $school = auth()->user()?->school;
        
        $account = $cashbook->account_id ? Account::where('school_id', $school->id)->find($cashbook->account_id) : null;
        
        // Get current balance from cashbook entries (not journal entries)
        $currentBalance = \App\Models\Cashbook::where('school_id', $school->id)
            ->where('account_id', $cashbook->account_id)
            ->where('id', '!=', $cashbook->id) // Exclude current entry
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->value('balance_after') ?? 0;
        
        // Reverse balance change
        $balanceAfter = $cashbook->transaction_type === 'expense' 
            ? $currentBalance + $cashbook->amount 
            : $currentBalance - $cashbook->amount;

        $cashbook->delete();

        return redirect()->route('admin.cashbook.index')
            ->with('success', 'Transaction deleted successfully.');
    }

    protected function authorizeSchoolAccess(Cashbook $cashbook)
    {
        if ($cashbook->school_id !== auth()->user()?->school_id) {
            abort(403);
        }
    }
}
