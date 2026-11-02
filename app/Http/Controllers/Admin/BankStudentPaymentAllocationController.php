<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Student;
use App\Services\Finance\AccountingPeriodService;
use App\Services\Finance\BankStudentPaymentAllocationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BankStudentPaymentAllocationController extends Controller
{
    public function __construct(private BankStudentPaymentAllocationService $allocationService) {}

    public function index(Request $request)
    {
        $this->authorizeReview($request);
        $school = $this->school();
        $accounts = Account::query()->where('school_id', $school->id)->where('type', 'asset')->active()
            ->where(function ($query) {
                $query->where('category', 'bank')->orWhere('code', 'like', '13%')
                    ->orWhere('code', '1800')->orWhere('name', 'like', '%bank%');
            })->orderBy('code')->get();

        $accountId = (int) $request->query('account_id', 0);
        if (! $accountId) {
            $accountId = (int) ($accounts->first(fn ($account) => str_contains(strtolower($account->name . ' ' . $account->code), 'afc'))?->id
                ?? $accounts->first()?->id ?? 0);
        }
        $account = $accountId ? $accounts->firstWhere('id', $accountId) : null;
        abort_if($accountId && ! $account, 404);

        $transactions = $account
            ? $this->allocationService->pendingQuery($school->id, $account->id)->paginate(25)->withQueryString()
            : null;
        $referenceCounts = [];
        if ($transactions) {
            $references = $transactions->getCollection()->pluck('reference_number')->filter(fn ($ref) => trim((string) $ref) !== '')->unique()->values();
            foreach ($references as $reference) {
                $referenceCounts[$reference] = BankTransaction::query()->where('school_id', $school->id)
                    ->where('account_id', $account->id)->where('source', 'bank_statement')
                    ->where('reference_number', $reference)->count();
            }
            $transactions->getCollection()->transform(function ($transaction) use ($referenceCounts) {
                $reference = trim((string) $transaction->reference_number);
                $transaction->allocation_review = $this->allocationService->suggest($transaction, $referenceCounts[$reference] ?? 1);
                return $transaction;
            });
        }

        $period = app(AccountingPeriodService::class)->getPeriodForDate('2026-01-30', $school->id);
        return view('admin.bank-reconciliations.student-payment-allocation', compact('accounts', 'account', 'transactions', 'period'));
    }

    public function searchStudents(Request $request)
    {
        $this->authorizeReview($request);
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $students = $this->allocationService->searchStudents($this->school()->id, $validated['q']);
        return response()->json($students->map(fn (Student $student) => [
            'id' => $student->id,
            'label' => trim($student->first_name . ' ' . $student->other_names . ' ' . $student->last_name),
            'admission_number' => $student->admission_number,
            'registration_number' => $student->registration_number,
        ]));
    }

    public function studentInvoices(Request $request, Student $student)
    {
        $this->authorizeReview($request);
        abort_unless((int) $student->school_id === (int) $this->school()->id, 404);
        $invoices = $this->allocationService->openTermOneInvoices($student->school_id, $student->id);
        return response()->json($invoices->map(fn ($invoice) => [
            'id' => $invoice->id,
            'label' => $invoice->number . ' · Term 1 2026 · balance $' . number_format((float) $invoice->balance, 2),
            'balance' => (float) $invoice->balance,
        ]));
    }

    public function postSelected(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can('payments.create') && $user->can('bank-reconciliations.edit'), 403);
        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1', 'max:5'],
            'selected_ids.*' => ['required', 'integer', 'distinct'],
            'items' => ['required', 'array'],
            'items.*.student_id' => ['required', 'integer'],
            'items.*.invoice_id' => ['required', 'integer'],
            'items.*.confirm_reference_review' => ['nullable', 'accepted'],
        ]);
        $school = $this->school();
        $posted = [];
        $errors = [];

        foreach ($validated['selected_ids'] as $id) {
            try {
                $transaction = BankTransaction::query()->where('school_id', $school->id)->findOrFail($id);
                $item = $validated['items'][$id] ?? null;
                if (! $item) {
                    throw ValidationException::withMessages(['selection' => 'Choose a student and invoice for every selected bank line.']);
                }
                $payment = $this->allocationService->post(
                    $transaction,
                    (int) $item['student_id'],
                    (int) $item['invoice_id'],
                    (bool) ($item['confirm_reference_review'] ?? false),
                    (int) $user->id
                );
                $posted[] = ['payment_id' => $payment->id, 'receipt_url' => route('admin.payments.receipt', $payment), 'amount' => (float) $payment->amount];
            } catch (\Throwable $exception) {
                report($exception);
                $errors[] = 'Bank line #' . $id . ': ' . ($exception instanceof ValidationException
                    ? collect($exception->errors())->flatten()->first()
                    : 'posting failed; no changes were retained for this line.');
            }
        }

        return redirect()->route('admin.bank-reconciliations.student-payment-allocation', ['account_id' => $request->input('account_id')])
            ->with('allocation_posted', $posted)->with('allocation_errors', $errors);
    }

    private function school()
    {
        $school = request()->user()?->school;
        abort_unless($school, 403, 'A school tenant is required.');
        return $school;
    }

    private function authorizeReview(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->can('bank-reconciliations.view') && $user->can('payments.view'), 403);
    }
}
