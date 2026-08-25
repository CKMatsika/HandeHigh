<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Services\Finance\AccountingPeriodService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingPeriodController extends Controller
{
    public function __construct(
        protected AccountingPeriodService $periodService
    ) {}

    public function index(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $periods = AccountingPeriod::where('school_id', $school->id)
            ->with(['closedByUser', 'reopenedByUser'])
            ->orderByDesc('start_date')
            ->paginate(15);

        $currentPeriod = $this->periodService->getCurrentPeriod($school->id);

        return view('admin.accounts.periods.index', compact('periods', 'currentPeriod', 'school'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_type' => ['required', 'in:monthly,quarterly,term,annual'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $this->periodService->createPeriod($validated, $school->id);

        return redirect()->route('admin.accounting-periods.index')
            ->with('success', "Accounting period '{$validated['name']}' created successfully.");
    }

    public function close(Request $request, AccountingPeriod $accountingPeriod)
    {
        $school = Auth::user()?->school;
        if (! $school || (int) $accountingPeriod->school_id !== (int) $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->periodService->closePeriod($accountingPeriod, Auth::user(), $validated['closing_notes'] ?? null);

        return redirect()->route('admin.accounting-periods.index')
            ->with('success', "Accounting period '{$accountingPeriod->name}' has been CLOSED. Transactions are now locked for this period.");
    }

    public function reopen(Request $request, AccountingPeriod $accountingPeriod)
    {
        $school = Auth::user()?->school;
        if (! $school || (int) $accountingPeriod->school_id !== (int) $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->periodService->reopenPeriod($accountingPeriod, Auth::user(), $validated['reason']);

        return redirect()->route('admin.accounting-periods.index')
            ->with('success', "Accounting period '{$accountingPeriod->name}' has been REOPENED. Audit record logged.");
    }

    public function validateTrialBalance(AccountingPeriod $accountingPeriod)
    {
        $school = Auth::user()?->school;
        if (! $school || (int) $accountingPeriod->school_id !== (int) $school->id) {
            abort(403);
        }

        $result = $this->periodService->validateTrialBalanceForPeriod($accountingPeriod);

        return response()->json($result);
    }
}
