<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashTransferController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        // Get cash accounts (petty cash, cash on hand)
        $cashAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where('category', 'cash')
            ->where('is_active', true)
            ->get();

        // Get bank accounts
        $bankAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where('category', 'bank')
            ->where('is_active', true)
            ->get();

        // Get recent transfers from cashbook
        $recentTransfers = \App\Models\Cashbook::where('school_id', $school->id)
            ->where('category', 'transfer')
            ->with('account')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.cash-transfers.index', compact('cashAccounts', 'bankAccounts', 'recentTransfers'));
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        // Get cash accounts (source accounts)
        $cashAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where('category', 'cash')
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function ($account) {
                return [$account->code => $account->name . ' (Balance: $' . $this->getAccountBalance($account) . ')'];
            });

        // Get bank accounts (destination accounts)
        $bankAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where('category', 'bank')
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function ($account) {
                return [$account->code => $account->name . ' (Balance: $' . $this->getAccountBalance($account) . ')'];
            });

        return view('admin.cash-transfers.create', compact('cashAccounts', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $validated = $request->validate([
            'from_account' => ['required', 'string'],
            'to_account' => ['required', 'string', 'different:from_account'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'transfer_date' => ['required', 'date'],
        ]);

        $fromAccountCode = $validated['from_account'];
        $toAccountCode = $validated['to_account'];
        $amount = (float) $validated['amount'];

        // Check if source account has sufficient balance
        $fromAccount = Account::where('school_id', $school->id)
            ->where('code', $fromAccountCode)
            ->firstOrFail();

        $currentBalance = $this->getAccountBalance($fromAccount);

        if ($currentBalance < $amount) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Insufficient balance. Available balance: $' . $currentBalance]);
        }

        try {
            // Create the transfer
            $journalBatch = $this->accountingService->postInterAccountTransfer(
                $fromAccountCode,
                $toAccountCode,
                $amount,
                $validated['description'],
                $validated['transfer_date']
            );

            return redirect()
                ->route('admin.cash-transfers.index')
                ->with('status', 'Transfer completed successfully. Reference: ' . $journalBatch->reference_number);

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Transfer failed: ' . $e->getMessage()]);
        }
    }

    private function getAccountBalance($account): float
    {
        return \App\Models\Cashbook::where('account_id', $account->id)
            ->latest()
            ->value('balance_after') ?? 0;
    }
}
