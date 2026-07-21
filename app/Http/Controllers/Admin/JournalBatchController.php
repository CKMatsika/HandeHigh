<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalBatch;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JournalBatchController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function index(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $status = $request->input('status');
        $query = JournalBatch::where('school_id', $school->id)
            ->withCount('entries')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $batches = $query->paginate(20)->appends($request->only('status'));

        return view('admin.journals.index', compact('batches', 'status'));
    }

    public function create()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $accounts = Account::where('school_id', $school->id)
            ->active()
            ->orderBy('code')
            ->get();

        return view('admin.journals.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', 'exists:accounts,id'],
            'entries.*.entry_type' => ['required', 'in:debit,credit'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        $batch = $this->accountingService->createJournalBatch([
            'school_id' => $school->id,
            'transaction_date' => $validated['transaction_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'description' => $validated['description'],
            'source_type' => 'manual',
            'status' => 'posted',
            'created_by' => Auth::id(),
            'entries' => $validated['entries'],
        ]);

        return redirect()->route('admin.journals.show', $batch)->with('success', 'Journal posted.');
    }

    public function show(JournalBatch $journalBatch)
    {
        $school = Auth::user()?->school;
        if (! $school || $journalBatch->school_id !== $school->id) {
            abort(403);
        }

        $journalBatch->load(['entries.account', 'creator', 'approver']);

        return view('admin.journals.show', compact('journalBatch'));
    }
}
