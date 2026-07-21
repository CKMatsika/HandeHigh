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
        
        // Create bank account in chart of accounts
        $bankAccount = Account::create([
            'school_id' => $school->id,
            'code' => '13' . str_pad(Account::where('school_id', $school->id)->max('id') + 1, 3, '0', STR_PAD_LEFT),
            'name' => $request->account_name,
            'type' => 'asset',
            'category' => 'bank',
            'opening_balance' => $request->opening_balance,
            'currency' => $request->currency,
            'description' => $request->notes,
            'is_active' => true,
        ]);

        // Create opening balance transaction in cashbook
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
        }

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Bank account created successfully.');
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
