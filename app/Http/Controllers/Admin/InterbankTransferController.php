<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InterbankTransfer;
use App\Rules\TenantExists;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InterbankTransferController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $transfers = InterbankTransfer::where('school_id', $school->id)
            ->with(['fromAccount', 'toAccount'])
            ->orderByDesc('transfer_date')
            ->paginate(20);

        return view('admin.interbank-transfers.index', compact('transfers'));
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

        return view('admin.interbank-transfers.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'transfer_date' => ['required', 'date'],
            'from_bank_account_id' => ['required', 'different:to_bank_account_id', TenantExists::make('accounts')],
            'to_bank_account_id' => ['required', TenantExists::make('accounts')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $transferNumber = 'TR-' . date('Ymd') . '-' . str_pad(InterbankTransfer::where('school_id', $school->id)->count() + 1, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($school, $validated, $transferNumber) {
            $transfer = InterbankTransfer::create([
                'school_id' => $school->id,
                'transfer_number' => $transferNumber,
                'transfer_date' => $validated['transfer_date'],
                'from_bank_account_id' => $validated['from_bank_account_id'],
                'to_bank_account_id' => $validated['to_bank_account_id'],
                'amount' => $validated['amount'],
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => 'completed',
                'created_by' => Auth::id(),
            ]);

            // Post journal entries using the accounting service
            $this->accountingService->postInterbankTransfer($transfer);
        });

        return redirect()->route('admin.interbank-transfers.index')->with('success', 'Transfer recorded.');
    }
}
