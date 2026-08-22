<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Student;
use App\Rules\TenantExists;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditNoteController extends Controller
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

        $query = CreditNote::where('school_id', $school->id)
            ->with('customer', 'student', 'invoice', 'creator');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $creditNotes = $query->orderByDesc('credit_note_date')->paginate(25);

        $totals = [
            'draft'     => CreditNote::where('school_id', $school->id)->where('status', 'draft')->sum('total_amount'),
            'issued'    => CreditNote::where('school_id', $school->id)->where('status', 'issued')->sum('total_amount'),
            'applied'   => CreditNote::where('school_id', $school->id)->where('status', 'applied')->sum('total_amount'),
            'cancelled' => CreditNote::where('school_id', $school->id)->where('status', 'cancelled')->sum('total_amount'),
        ];

        return view('admin.credit-notes.index', compact('creditNotes', 'totals'));
    }

    public function create()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $customers = Customer::where('school_id', $school->id)->orderBy('name')->get();
        $students  = Student::whereHas('enrollments', fn ($q) => $q->where('school_id', $school->id))
            ->orderBy('first_name')->get();
        $invoices  = Invoice::where('school_id', $school->id)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->orderByDesc('invoice_date')
            ->get();

        return view('admin.credit-notes.create', compact('customers', 'students', 'invoices'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $request->validate([
            'type'             => 'required|in:customer,student',
            'customer_id'      => ['required_if:type,customer', 'nullable', TenantExists::make('customers')],
            'student_id'       => ['required_if:type,student', 'nullable', TenantExists::make('students')],
            'invoice_id'       => ['nullable', TenantExists::make('invoices')],
            'total_amount'     => 'required|numeric|min:0.01',
            'credit_note_date' => 'required|date',
            'reason'           => 'required|string|max:1000',
            'notes'            => 'nullable|string|max:2000',
            'status'           => 'required|in:draft,issued',
        ]);

        DB::beginTransaction();
        try {
            // Generate unique credit note number
            $lastNumber = CreditNote::where('school_id', $school->id)->max('credit_note_number');
            $nextSeq    = $lastNumber ? ((int) substr($lastNumber, -4)) + 1 : 1;
            $creditNoteNumber = 'CN-' . date('Y') . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $creditNote = CreditNote::create([
                'school_id'        => $school->id,
                'credit_note_number' => $creditNoteNumber,
                'credit_note_date' => $request->credit_note_date,
                'type'             => $request->type,
                'customer_id'      => $request->type === 'customer' ? $request->customer_id : null,
                'student_id'       => $request->type === 'student' ? $request->student_id : null,
                'invoice_id'       => $request->invoice_id,
                'total_amount'     => $request->total_amount,
                'applied_amount'   => 0,
                'balance'          => $request->total_amount,
                'status'           => $request->status,
                'reason'           => $request->reason,
                'notes'            => $request->notes,
                'created_by'       => Auth::id(),
            ]);

            // If issued, post to general ledger immediately
            if ($creditNote->status === 'issued') {
                $this->postCreditNoteToLedger($creditNote);
            }

            DB::commit();

            return redirect()->route('admin.credit-notes.show', $creditNote)
                ->with('success', "Credit note {$creditNote->credit_note_number} created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Credit note creation failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create credit note: ' . $e->getMessage());
        }
    }

    public function show(CreditNote $creditNote)
    {
        $school = Auth::user()?->school;
        if (! $school || $creditNote->school_id !== $school->id) {
            abort(403);
        }

        $creditNote->load('customer', 'student', 'invoice', 'creator');

        return view('admin.credit-notes.show', compact('creditNote'));
    }

    public function edit(CreditNote $creditNote)
    {
        $school = Auth::user()?->school;
        if (! $school || $creditNote->school_id !== $school->id) {
            abort(403);
        }

        if (! in_array($creditNote->status, ['draft'])) {
            return redirect()->route('admin.credit-notes.show', $creditNote)
                ->with('error', 'Only draft credit notes can be edited.');
        }

        $customers = Customer::where('school_id', $school->id)->orderBy('name')->get();
        $students  = Student::whereHas('enrollments', fn ($q) => $q->where('school_id', $school->id))
            ->orderBy('first_name')->get();
        $invoices  = Invoice::where('school_id', $school->id)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->orderByDesc('invoice_date')
            ->get();

        return view('admin.credit-notes.edit', compact('creditNote', 'customers', 'students', 'invoices'));
    }

    public function update(Request $request, CreditNote $creditNote)
    {
        $school = Auth::user()?->school;
        if (! $school || $creditNote->school_id !== $school->id) {
            abort(403);
        }

        if ($creditNote->status !== 'draft') {
            return redirect()->route('admin.credit-notes.show', $creditNote)
                ->with('error', 'Only draft credit notes can be edited.');
        }

        $request->validate([
            'type'             => 'required|in:customer,student',
            'customer_id'      => ['required_if:type,customer', 'nullable', TenantExists::make('customers')],
            'student_id'       => ['required_if:type,student', 'nullable', TenantExists::make('students')],
            'invoice_id'       => ['nullable', TenantExists::make('invoices')],
            'total_amount'     => 'required|numeric|min:0.01',
            'credit_note_date' => 'required|date',
            'reason'           => 'required|string|max:1000',
            'notes'            => 'nullable|string|max:2000',
            'status'           => 'required|in:draft,issued',
        ]);

        DB::beginTransaction();
        try {
            $wasIssued = $creditNote->status === 'draft' && $request->status === 'issued';

            $creditNote->update([
                'credit_note_date' => $request->credit_note_date,
                'type'             => $request->type,
                'customer_id'      => $request->type === 'customer' ? $request->customer_id : null,
                'student_id'       => $request->type === 'student' ? $request->student_id : null,
                'invoice_id'       => $request->invoice_id,
                'total_amount'     => $request->total_amount,
                'balance'          => $request->total_amount - $creditNote->applied_amount,
                'status'           => $request->status,
                'reason'           => $request->reason,
                'notes'            => $request->notes,
            ]);

            // If being issued for the first time, post to ledger
            if ($wasIssued) {
                $this->postCreditNoteToLedger($creditNote->fresh());
            }

            DB::commit();

            return redirect()->route('admin.credit-notes.show', $creditNote)
                ->with('success', 'Credit note updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function issue(CreditNote $creditNote)
    {
        $school = Auth::user()?->school;
        if (! $school || $creditNote->school_id !== $school->id || $creditNote->status !== 'draft') {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $creditNote->update(['status' => 'issued']);
            $this->postCreditNoteToLedger($creditNote->fresh());
            DB::commit();

            return redirect()->route('admin.credit-notes.show', $creditNote)
                ->with('success', 'Credit note issued and posted to ledger.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to issue credit note: ' . $e->getMessage());
        }
    }

    public function cancel(CreditNote $creditNote)
    {
        $school = Auth::user()?->school;
        if (! $school || $creditNote->school_id !== $school->id) {
            abort(403);
        }

        if (! in_array($creditNote->status, ['draft', 'issued'])) {
            return back()->with('error', 'Only draft or issued credit notes can be cancelled.');
        }

        $creditNote->update(['status' => 'cancelled']);

        return redirect()->route('admin.credit-notes.index')
            ->with('success', 'Credit note cancelled.');
    }

    public function applyToInvoice(Request $request, CreditNote $creditNote)
    {
        $school = Auth::user()?->school;
        if (! $school || $creditNote->school_id !== $school->id) {
            abort(403);
        }

        $request->validate([
            'invoice_id'    => ['required', TenantExists::make('invoices')],
            'apply_amount'  => 'required|numeric|min:0.01|max:' . $creditNote->balance,
        ]);

        $invoice = Invoice::where('school_id', $school->id)->findOrFail($request->invoice_id);

        DB::beginTransaction();
        try {
            $applyAmount = min($request->apply_amount, $creditNote->balance, $invoice->outstanding_balance ?? $invoice->total_amount);

            // Reduce invoice balance
            $invoice->increment('amount_paid', $applyAmount);
            $newBalance = $invoice->total_amount - $invoice->fresh()->amount_paid;
            $invoice->update([
                'status' => $newBalance <= 0 ? 'paid' : ($invoice->status === 'overdue' ? 'overdue' : 'partial'),
            ]);

            // Update credit note
            $newApplied = $creditNote->applied_amount + $applyAmount;
            $creditNote->update([
                'applied_amount' => $newApplied,
                'balance'        => $creditNote->total_amount - $newApplied,
                'invoice_id'     => $invoice->id,
                'status'         => ($creditNote->total_amount - $newApplied) <= 0 ? 'applied' : 'issued',
            ]);

            DB::commit();

            return redirect()->route('admin.credit-notes.show', $creditNote)
                ->with('success', "Applied {$applyAmount} to invoice #{$invoice->invoice_number}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Application failed: ' . $e->getMessage());
        }
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function postCreditNoteToLedger(CreditNote $creditNote): void
    {
        try {
            // Debit: Accounts Receivable Control (or Student Debtors) — reduces what is owed TO the school
            // Credit: Revenue correction / Fee Revenue reversal
            $school = $creditNote->school;

            $debitAccount = \App\Models\Account::where('school_id', $creditNote->school_id)
                ->where('code', '5100') // Fee Revenue account
                ->first()
                ?? \App\Models\Account::where('school_id', $creditNote->school_id)
                    ->where('type', 'revenue')
                    ->first();

            $creditAccount = \App\Models\Account::where('school_id', $creditNote->school_id)
                ->where('code', '1200') // Accounts Receivable
                ->first()
                ?? \App\Models\Account::where('school_id', $creditNote->school_id)
                    ->where('type', 'asset')
                    ->first();

            if (! $debitAccount || ! $creditAccount) {
                Log::warning("Credit note {$creditNote->credit_note_number}: Could not find accounts for ledger posting.");
                return;
            }

            $this->accountingService->createJournalBatch([
                'school_id'        => $creditNote->school_id,
                'transaction_date' => $creditNote->credit_note_date->toDateString(),
                'reference_number' => $creditNote->credit_note_number,
                'description'      => "Credit Note: {$creditNote->credit_note_number} — {$creditNote->reason}",
                'source_type'      => 'credit_note',
                'source_id'        => $creditNote->id,
                'status'           => 'posted',
                'created_by'       => $creditNote->created_by ?? Auth::id(),
                'entries'          => [
                    [
                        'account_id' => $debitAccount->id,
                        'entry_type' => 'debit',
                        'amount'     => $creditNote->total_amount,
                        'memo'       => "Revenue reversal — {$creditNote->credit_note_number}",
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'entry_type' => 'credit',
                        'amount'     => $creditNote->total_amount,
                        'memo'       => "Reduce receivable — {$creditNote->credit_note_number}",
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to post credit note {$creditNote->credit_note_number} to ledger: " . $e->getMessage());
        }
    }
}
