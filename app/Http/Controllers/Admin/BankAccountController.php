<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Cashbook;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $school = auth()->user()->school;
        
        // Get bank accounts from chart of accounts
        $bankAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function($query) {
                $query->where('category', 'bank')
                      ->orWhere('category', 'cash')  // Include cash accounts
                      ->orWhere('code', 'like', '13%') // Bank accounts typically start with 13xx
                      ->orWhere('code', '1100');      // Cash on hand account
            })
            ->where('is_active', true)
            ->withCount(['cashbookEntries as recent_transactions' => function($query) {
                $query->where('transaction_date', '>=', now()->subDays(7));
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.bank-accounts.index', compact('bankAccounts'));
    }

    public function create()
    {
        return view('admin.bank-accounts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_type' => 'required|string|in:savings,current,checking',
            'currency' => 'required|string|max:10',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $school = auth()->user()->school;

        // Find next available bank account code in 1300 series
        $existingCodes = Account::where('school_id', $school->id)
            ->where('code', 'like', '13%')
            ->pluck('code')
            ->toArray();

        $nextNum = 1;
        while (in_array(sprintf('13%02d', $nextNum), $existingCodes)) {
            $nextNum++;
        }
        $accountCode = sprintf('13%02d', $nextNum);

        $parentBank = Account::where('school_id', $school->id)
            ->where('code', '1300')
            ->first();

        $desc = "Bank: {$request->bank_name} | Acc No: {$request->account_number}" 
            . ($request->branch_name ? " | Branch: {$request->branch_name}" : '')
            . ($request->notes ? " | {$request->notes}" : '');

        // Create bank account in chart of accounts
        $bankAccount = Account::create([
            'school_id' => $school->id,
            'code' => $accountCode,
            'name' => $request->account_name . " ({$request->bank_name})",
            'type' => 'asset',
            'category' => 'bank',
            'parent_id' => $parentBank?->id,
            'opening_balance' => $request->opening_balance,
            'currency' => $request->currency,
            'description' => $desc,
            'is_postable' => true,
            'is_active' => true,
        ]);

        // Keep legacy bank_accounts table synchronized if table exists
        try {
            \App\Models\BankAccount::create([
                'school_id' => $school->id,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank_name' => $request->bank_name,
                'branch_name' => $request->branch_name,
                'account_type' => $request->account_type,
                'opening_balance' => $request->opening_balance,
                'current_balance' => $request->opening_balance,
                'currency' => $request->currency,
                'is_active' => true,
                'notes' => $request->notes,
            ]);
        } catch (\Throwable $e) {
            // Ignore legacy table errors
        }

        // Create opening balance transaction in cashbook & bank reconciliation
        if ($request->opening_balance > 0) {
            Cashbook::create([
                'school_id' => $school->id,
                'account_id' => $bankAccount->id,
                'transaction_type' => 'income',
                'category' => 'opening_balance',
                'description' => 'Opening balance for ' . $request->account_name,
                'amount' => $request->opening_balance,
                'balance_after' => $request->opening_balance,
                'transaction_date' => now(),
                'payment_method' => 'bank_transfer',
                'created_by' => auth()->id(),
            ]);

            \App\Models\CashbookTransaction::create([
                'school_id' => $school->id,
                'account_id' => $bankAccount->id,
                'transaction_date' => now()->toDateString(),
                'reference_number' => 'OP-BAL-' . $bankAccount->id,
                'description' => 'Opening balance for ' . $request->account_name,
                'amount' => $request->opening_balance,
                'transaction_type' => 'debit',
                'category' => 'income',
                'status' => 'unmatched',
                'created_by' => auth()->id(),
            ]);
        }

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', "Bank account [{$bankAccount->code}] {$bankAccount->name} created successfully.");
    }

    public function show($id)
    {
        $school = auth()->user()->school;
        $bankAccount = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function($query) {
                $query->where('category', 'bank')
                      ->orWhere('category', 'cash');
            })
            ->where('id', $id)
            ->firstOrFail();
            
        $recentTransactions = Cashbook::where('account_id', $bankAccount->id)
            ->with('relatedInvoice', 'relatedPayment')
            ->latest()
            ->take(20)
            ->get();

        return view('admin.bank-accounts.show', compact('bankAccount', 'recentTransactions'));
    }

    public function edit($id)
    {
        $school = auth()->user()->school;
        $bankAccount = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function($query) {
                $query->where('category', 'bank')
                      ->orWhere('category', 'cash');
            })
            ->where('id', $id)
            ->firstOrFail();
            
        return view('admin.bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, $id)
    {
        $school = auth()->user()->school;
        $bankAccount = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function($query) {
                $query->where('category', 'bank')
                      ->orWhere('category', 'cash');
            })
            ->where('id', $id)
            ->firstOrFail();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $bankAccount->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy($id)
    {
        $school = auth()->user()->school;
        $bankAccount = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function($query) {
                $query->where('category', 'bank')
                      ->orWhere('category', 'cash');
            })
            ->where('id', $id)
            ->firstOrFail();
        
        // Check if account has transactions
        $hasTransactions = Cashbook::where('account_id', $bankAccount->id)->exists();
        
        if ($hasTransactions) {
            return redirect()->route('admin.bank-accounts.index')
                ->with('error', 'Cannot delete account with existing transactions.');
        }

        $bankAccount->delete();

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
