<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\CashbookTransaction;
use App\Rules\TenantExists;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankReconciliationController extends Controller
{
    public function __construct(private BankReconciliationService $reconciliationService)
    {
    }

    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $recons = BankReconciliation::where('school_id', $school->id)
            ->with('bankAccount')
            ->orderByDesc('reconciliation_date')
            ->paginate(20);

        return view('admin.bank-reconciliations.index', compact('recons'));
    }

    public function create()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        // Get bank accounts from chart of accounts (asset accounts with bank-related codes)
        $bankAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where(function($query) {
                $query->where('category', 'bank')
                      ->orWhere('code', 'like', '13%'); // Bank accounts typically start with 13xx
            })
            ->active()
            ->orderBy('code')
            ->get();

        return view('admin.bank-reconciliations.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'bank_account_id' => ['required', TenantExists::make('accounts')],
            'reconciliation_date' => ['required', 'date'],
            'book_balance' => ['required', 'numeric'],
            'bank_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $reconciled = $validated['book_balance'] - $validated['bank_balance'];

        BankReconciliation::create([
            'school_id' => $school->id,
            'bank_account_id' => $validated['bank_account_id'],
            'reconciliation_date' => $validated['reconciliation_date'],
            'book_balance' => $validated['book_balance'],
            'bank_balance' => $validated['bank_balance'],
            'reconciled_balance' => $reconciled,
            'matched_items' => [],
            'unmatched_items' => [],
            'notes' => $validated['notes'] ?? null,
            'status' => 'completed',
            'reconciled_by' => Auth::id(),
        ]);

        return redirect()->route('admin.bank-reconciliations.index')->with('success', 'Bank reconciliation saved.');
    }

    public function show(BankReconciliation $bankReconciliation)
    {
        $school = Auth::user()?->school;
        if (! $school || $bankReconciliation->school_id !== $school->id) {
            abort(403);
        }

        $bankReconciliation->load('bankAccount');

        return view('admin.bank-reconciliations.show', compact('bankReconciliation'));
    }

    // Enhanced features

    public function transactions(Account $account)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $startDate = request('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = request('end_date', now()->format('Y-m-d'));

        $bankTransactions = BankTransaction::where('account_id', $account->id)
            ->byDateRange($startDate, $endDate)
            ->with(['cashbookMatches'])
            ->orderBy('transaction_date')
            ->get();

        $cashbookTransactions = CashbookTransaction::where('account_id', $account->id)
            ->byDateRange($startDate, $endDate)
            ->with(['bankMatches'])
            ->orderBy('transaction_date')
            ->get();

        $summary = $this->reconciliationService->getReconciliationSummary($account->id, $startDate, $endDate);

        return view('admin.bank-reconciliations.transactions', compact(
            'account', 
            'bankTransactions', 
            'cashbookTransactions',
            'summary',
            'startDate',
            'endDate'
        ));
    }

    public function importStatement(Request $request)
    {
        $validated = $request->validate([
            'account_id' => ['required', TenantExists::make('accounts')],
            'statement_file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'skip_header' => ['boolean'],
            'date_format' => ['string'],
        ]);

        if ($request->hasFile('statement_file')) {
            $file = $request->file('statement_file');
            $filePath = $file->store('statements', 'local');

            $options = [
                'skip_header' => $request->boolean('skip_header', true),
                'date_format' => $request->get('date_format', 'Y-m-d'),
            ];

            $result = $this->reconciliationService->importBankStatement(
                $validated['account_id'], 
                storage_path('app/' . $filePath),
                $options
            );

            // Clean up the file
            unlink(storage_path('app/' . $filePath));

            if ($result['success']) {
                return back()->with('success', $result['message']);
            } else {
                return back()->with('error', $result['message'])->withErrors($result['errors']);
            }
        }

        return back()->with('error', 'No file uploaded.');
    }

    public function autoMatch(Request $request)
    {
        $validated = $request->validate([
            'account_id' => ['required', TenantExists::make('accounts')],
            'confidence_threshold' => ['numeric', 'min:0.5', 'max:1.0'],
        ]);

        $result = $this->reconciliationService->autoMatchTransactions(
            $validated['account_id'],
            ['confidence_threshold' => $validated['confidence_threshold'] ?? 0.8]
        );

        return back()->with('success', $result['message']);
    }

    public function addBankCharge(Request $request)
    {
        $validated = $request->validate([
            'account_id' => ['required', TenantExists::make('accounts')],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $result = $this->reconciliationService->addBankCharge($validated['account_id'], $validated);

        return back()->with('success', $result['message']);
    }

    public function availableMatches(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => ['required', TenantExists::make('accounts')],
            'type' => ['required', 'in:bank,cashbook'],
            'transaction_id' => ['required', 'integer'],
        ]);

        $accountId = $validated['account_id'];
        $type = $validated['type'];
        $txId = $validated['transaction_id'];

        if ($type === 'bank') {
            $sourceTx = BankTransaction::where('school_id', $school->id)
                ->where('account_id', $accountId)
                ->findOrFail($txId);

            $targetAmount = (float) $sourceTx->unmatched_amount;

            $candidates = CashbookTransaction::where('school_id', $school->id)
                ->where('account_id', $accountId)
                ->whereIn('status', ['unmatched', 'partially_matched'])
                ->get()
                ->map(function ($tx) use ($targetAmount) {
                    $unmatched = (float) $tx->unmatched_amount;
                    return [
                        'id' => $tx->id,
                        'date' => $tx->transaction_date->format('M j, Y'),
                        'raw_date' => $tx->transaction_date->format('Y-m-d'),
                        'description' => $tx->description,
                        'reference_number' => $tx->reference_number,
                        'amount' => (float) $tx->amount,
                        'unmatched_amount' => $unmatched,
                        'category' => $tx->category_label ?? $tx->category,
                        'type' => $tx->transaction_type,
                        'is_exact' => abs($unmatched - $targetAmount) < 0.001,
                        'diff' => abs($unmatched - $targetAmount),
                    ];
                })
                ->sortBy([
                    ['is_exact', 'desc'],
                    ['diff', 'asc'],
                    ['raw_date', 'desc'],
                ])
                ->values();

            return response()->json([
                'success' => true,
                'source' => [
                    'id' => $sourceTx->id,
                    'type' => 'bank',
                    'description' => $sourceTx->description,
                    'reference_number' => $sourceTx->reference_number,
                    'amount' => (float) $sourceTx->amount,
                    'unmatched_amount' => (float) $sourceTx->unmatched_amount,
                    'date' => $sourceTx->transaction_date->format('M j, Y'),
                ],
                'matches' => $candidates,
            ]);
        } else {
            $sourceTx = CashbookTransaction::where('school_id', $school->id)
                ->where('account_id', $accountId)
                ->findOrFail($txId);

            $targetAmount = (float) $sourceTx->unmatched_amount;

            $candidates = BankTransaction::where('school_id', $school->id)
                ->where('account_id', $accountId)
                ->whereIn('status', ['unmatched', 'partially_matched'])
                ->get()
                ->map(function ($tx) use ($targetAmount) {
                    $unmatched = (float) $tx->unmatched_amount;
                    return [
                        'id' => $tx->id,
                        'date' => $tx->transaction_date->format('M j, Y'),
                        'raw_date' => $tx->transaction_date->format('Y-m-d'),
                        'description' => $tx->description,
                        'reference_number' => $tx->reference_number,
                        'amount' => (float) $tx->amount,
                        'unmatched_amount' => $unmatched,
                        'category' => $tx->transaction_type,
                        'type' => $tx->transaction_type,
                        'is_exact' => abs($unmatched - $targetAmount) < 0.001,
                        'diff' => abs($unmatched - $targetAmount),
                    ];
                })
                ->sortBy([
                    ['is_exact', 'desc'],
                    ['diff', 'asc'],
                    ['raw_date', 'desc'],
                ])
                ->values();

            return response()->json([
                'success' => true,
                'source' => [
                    'id' => $sourceTx->id,
                    'type' => 'cashbook',
                    'description' => $sourceTx->description,
                    'reference_number' => $sourceTx->reference_number,
                    'amount' => (float) $sourceTx->amount,
                    'unmatched_amount' => (float) $sourceTx->unmatched_amount,
                    'date' => $sourceTx->transaction_date->format('M j, Y'),
                ],
                'matches' => $candidates,
            ]);
        }
    }

    public function manualMatch(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'bank_transaction_id' => ['required', TenantExists::make('bank_transactions')],
            'cashbook_transaction_id' => ['required', TenantExists::make('cashbook_transactions')],
            'match_amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $bankTransaction = BankTransaction::where('school_id', $school->id)->findOrFail($validated['bank_transaction_id']);
        $cashbookTransaction = CashbookTransaction::where('school_id', $school->id)->findOrFail($validated['cashbook_transaction_id']);

        if ($bankTransaction->account_id !== $cashbookTransaction->account_id) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Transactions must belong to the same bank account.'], 422);
            }
            return back()->with('error', 'Transactions must belong to the same bank account.');
        }

        if ($validated['match_amount'] > $bankTransaction->unmatched_amount || $validated['match_amount'] > $cashbookTransaction->unmatched_amount) {
            $msg = 'Match amount cannot exceed the remaining unmatched amount on either transaction.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        try {
            $this->reconciliationService->createMatch(
                $bankTransaction,
                $cashbookTransaction,
                $validated['match_amount'],
                'manual'
            );

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Transactions matched successfully.']);
            }
            return back()->with('success', 'Transactions matched successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to match transactions: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to match transactions: ' . $e->getMessage());
        }
    }

    public function unmatchTransaction(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $bankTxId = $request->input('bank_transaction_id');
        $cashbookTxId = $request->input('cashbook_transaction_id');

        // If only one was provided, resolve the partner from transaction_matches
        if ($bankTxId && ! $cashbookTxId) {
            $match = \DB::table('transaction_matches')
                ->where('bank_transaction_id', $bankTxId)
                ->first();
            if ($match) {
                $cashbookTxId = $match->cashbook_transaction_id;
            }
        } elseif ($cashbookTxId && ! $bankTxId) {
            $match = \DB::table('transaction_matches')
                ->where('cashbook_transaction_id', $cashbookTxId)
                ->first();
            if ($match) {
                $bankTxId = $match->bank_transaction_id;
            }
        }

        $request->merge([
            'bank_transaction_id' => $bankTxId,
            'cashbook_transaction_id' => $cashbookTxId,
        ]);

        $validated = $request->validate([
            'bank_transaction_id' => ['required', TenantExists::make('bank_transactions')],
            'cashbook_transaction_id' => ['required', TenantExists::make('cashbook_transactions')],
        ]);

        $bankTransaction = BankTransaction::where('school_id', $school->id)->findOrFail($validated['bank_transaction_id']);
        $cashbookTransaction = CashbookTransaction::where('school_id', $school->id)->findOrFail($validated['cashbook_transaction_id']);

        // Remove the match
        \DB::table('transaction_matches')
            ->where('bank_transaction_id', $bankTransaction->id)
            ->where('cashbook_transaction_id', $cashbookTransaction->id)
            ->delete();

        // Recalculate matched amounts and status for both transactions
        $remainingBankMatches = \DB::table('transaction_matches')
            ->where('bank_transaction_id', $bankTransaction->id)
            ->get();
        $bankMatchedTotal = (float) $remainingBankMatches->sum('match_amount');
        $bankMatchedWith = $remainingBankMatches->map(function ($m) {
            return [
                'cashbook_transaction_id' => $m->cashbook_transaction_id,
                'amount' => (float) $m->match_amount,
                'type' => $m->match_type,
            ];
        })->values()->all();

        $bankTransaction->update([
            'matched_amount' => $bankMatchedTotal,
            'status' => $bankMatchedTotal >= $bankTransaction->amount ? 'matched' : ($bankMatchedTotal > 0 ? 'partially_matched' : 'unmatched'),
            'matched_with' => !empty($bankMatchedWith) ? $bankMatchedWith : null,
        ]);

        $remainingCashbookMatches = \DB::table('transaction_matches')
            ->where('cashbook_transaction_id', $cashbookTransaction->id)
            ->get();
        $cashbookMatchedTotal = (float) $remainingCashbookMatches->sum('match_amount');
        $cashbookMatchedWith = $remainingCashbookMatches->map(function ($m) {
            return [
                'bank_transaction_id' => $m->bank_transaction_id,
                'amount' => (float) $m->match_amount,
                'type' => $m->match_type,
            ];
        })->values()->all();

        $cashbookTransaction->update([
            'matched_amount' => $cashbookMatchedTotal,
            'status' => $cashbookMatchedTotal >= $cashbookTransaction->amount ? 'matched' : ($cashbookMatchedTotal > 0 ? 'partially_matched' : 'unmatched'),
            'matched_with' => !empty($cashbookMatchedWith) ? $cashbookMatchedWith : null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Match removed successfully.']);
        }

        return back()->with('success', 'Match removed successfully.');
    }

    public function addCashbookTransaction(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => ['required', TenantExists::make('accounts')],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'transaction_type' => ['required', 'in:debit,credit'],
            'category' => ['required', 'in:income,expense,transfer,bank_charge,interest,other'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $transaction = CashbookTransaction::create([
            'school_id' => $school->id,
            'account_id' => $validated['account_id'],
            'transaction_date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'transaction_type' => $validated['transaction_type'],
            'category' => $validated['category'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'unmatched',
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Cashbook transaction added successfully.');
    }

    public function addBankTransaction(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => ['required', TenantExists::make('accounts')],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'transaction_type' => ['required', 'in:debit,credit'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $transaction = BankTransaction::create([
            'school_id' => $school->id,
            'account_id' => $validated['account_id'],
            'transaction_date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'transaction_type' => $validated['transaction_type'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'source' => 'manual',
            'status' => 'unmatched',
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Bank transaction added successfully.');
    }
}
