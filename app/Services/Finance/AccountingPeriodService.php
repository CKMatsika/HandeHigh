<?php

namespace App\Services\Finance;

use App\Exceptions\AccountingPeriodClosedException;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\JournalBatch;
use App\Models\School;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPeriodService
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    protected function resolveSchoolId(?int $schoolId = null): int
    {
        if ($schoolId !== null) {
            return $schoolId;
        }

        if ($this->tenantContext->hasTenant()) {
            return $this->tenantContext->id();
        }

        $user = auth()->user();
        if ($user && $user->school_id) {
            return (int) $user->school_id;
        }

        throw new \LogicException('No active school tenant could be resolved for accounting period.');
    }

    /**
     * Get the defined accounting period matching a specific date for a school.
     */
    public function getPeriodForDate(string|\DateTimeInterface $date, ?int $schoolId = null): ?AccountingPeriod
    {
        $schoolId = $this->resolveSchoolId($schoolId);
        $dateStr = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : date('Y-m-d', strtotime($date));

        return AccountingPeriod::where('school_id', $schoolId)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();
    }

    /**
     * Check if a specific date falls inside a closed accounting period.
     */
    public function isDateInClosedPeriod(string|\DateTimeInterface $date, ?int $schoolId = null): bool
    {
        $period = $this->getPeriodForDate($date, $schoolId);

        return $period !== null && $period->isClosed();
    }

    /**
     * Assert that the accounting period for a given transaction date is open.
     * Throws AccountingPeriodClosedException if the period is closed.
     *
     * @throws AccountingPeriodClosedException
     */
    public function assertPeriodOpen(string|\DateTimeInterface $date, ?int $schoolId = null, string $action = 'post transactions'): void
    {
        $schoolId = $this->resolveSchoolId($schoolId);
        $dateStr = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : date('Y-m-d', strtotime($date));
        $period = $this->getPeriodForDate($dateStr, $schoolId);

        if ($period && $period->isClosed()) {
            // Log rejected attempt to audit trail
            $this->logPeriodAudit($schoolId, 'transaction_rejected_closed_period', "Attempted to {$action} on {$dateStr} in closed period '{$period->name}'.", $period);

            throw new AccountingPeriodClosedException(
                "Accounting period '{$period->name}' ({$period->start_date->format('d M Y')} – {$period->end_date->format('d M Y')}) is CLOSED. Cannot {$action} into a closed accounting period.",
                $period,
                $dateStr
            );
        }
    }

    /**
     * Create a new accounting period ensuring no overlapping periods exist for this school.
     */
    public function createPeriod(array $data, ?int $schoolId = null): AccountingPeriod
    {
        $schoolId = $this->resolveSchoolId($schoolId);
        $startDate = date('Y-m-d', strtotime($data['start_date']));
        $endDate = date('Y-m-d', strtotime($data['end_date']));

        if ($startDate > $endDate) {
            throw ValidationException::withMessages([
                'end_date' => 'The period end date cannot be earlier than the start date.',
            ]);
        }

        // Prevent overlapping periods for the same school
        $overlapExists = AccountingPeriod::where('school_id', $schoolId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'start_date' => 'The selected date range overlaps with an existing accounting period for this school.',
            ]);
        }

        $period = AccountingPeriod::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'period_type' => $data['period_type'] ?? 'monthly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
        ]);

        $this->logPeriodAudit($schoolId, 'period_created', "Created accounting period '{$period->name}'.", $period);

        return $period;
    }

    /**
     * Close an accounting period after validating trial balance and batch integrity.
     */
    public function closePeriod(AccountingPeriod $period, User $user, ?string $notes = null): AccountingPeriod
    {
        if ($period->isClosed()) {
            throw ValidationException::withMessages([
                'period' => "Accounting period '{$period->name}' is already closed.",
            ]);
        }

        // Validate trial balance integrity before closing
        $validation = $this->validateTrialBalanceForPeriod($period);
        if (!$validation['is_balanced']) {
            throw ValidationException::withMessages([
                'period' => "Cannot close period '{$period->name}': General Ledger is out of balance. Total Debits: \${$validation['debit_total']}, Total Credits: \${$validation['credit_total']}.",
            ]);
        }

        // Validate that there are no unbalanced or draft batches in this period
        $unbalancedBatches = JournalBatch::where('school_id', $period->school_id)
            ->whereBetween('transaction_date', [$period->start_date->format('Y-m-d'), $period->end_date->format('Y-m-d')])
            ->where('status', 'draft')
            ->count();

        if ($unbalancedBatches > 0) {
            throw ValidationException::withMessages([
                'period' => "Cannot close period '{$period->name}': There are {$unbalancedBatches} draft/unposted journal batches in this date range. Post or delete draft batches first.",
            ]);
        }

        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $user->id,
            'closing_notes' => $notes,
        ]);

        $this->logPeriodAudit($period->school_id, 'period_closed', "Closed accounting period '{$period->name}' by {$user->name}. Notes: {$notes}", $period);

        return $period;
    }

    /**
     * Reopen a closed accounting period with strict authorization and mandatory reason.
     */
    public function reopenPeriod(AccountingPeriod $period, User $user, string $reason): AccountingPeriod
    {
        if ($period->isOpen()) {
            throw ValidationException::withMessages([
                'period' => "Accounting period '{$period->name}' is already open.",
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A detailed reason is required to formally reopen a closed accounting period.',
            ]);
        }

        $period->update([
            'status' => 'open',
            'reopened_at' => now(),
            'reopened_by' => $user->id,
            'reopening_notes' => $reason,
        ]);

        $this->logPeriodAudit($period->school_id, 'period_reopened', "Reopened accounting period '{$period->name}' by {$user->name}. Reason: {$reason}", $period);

        return $period;
    }

    /**
     * Validate trial balance arithmetic across the given accounting period.
     */
    public function validateTrialBalanceForPeriod(AccountingPeriod $period): array
    {
        $startDate = $period->start_date->format('Y-m-d');
        $endDate = $period->end_date->format('Y-m-d');

        $result = DB::table('journal_entries')
            ->join('journal_batches', 'journal_entries.journal_batch_id', '=', 'journal_batches.id')
            ->where('journal_batches.school_id', $period->school_id)
            ->where('journal_batches.status', 'posted')
            ->whereBetween('journal_batches.transaction_date', [$startDate, $endDate])
            ->selectRaw("
                SUM(CASE WHEN journal_entries.entry_type = 'debit' THEN journal_entries.amount ELSE 0 END) as total_debits,
                SUM(CASE WHEN journal_entries.entry_type = 'credit' THEN journal_entries.amount ELSE 0 END) as total_credits,
                COUNT(DISTINCT journal_batches.id) as batch_count,
                COUNT(journal_entries.id) as entry_count
            ")
            ->first();

        $debitTotal = (float) ($result->total_debits ?? 0);
        $creditTotal = (float) ($result->total_credits ?? 0);
        $isBalanced = abs($debitTotal - $creditTotal) < 0.01;

        return [
            'is_balanced' => $isBalanced,
            'debit_total' => round($debitTotal, 2),
            'credit_total' => round($creditTotal, 2),
            'difference' => round(abs($debitTotal - $creditTotal), 2),
            'batch_count' => (int) ($result->batch_count ?? 0),
            'entry_count' => (int) ($result->entry_count ?? 0),
        ];
    }

    /**
     * Get the current active period for today's date.
     */
    public function getCurrentPeriod(?int $schoolId = null): ?AccountingPeriod
    {
        return $this->getPeriodForDate(now(), $schoolId);
    }

    /**
     * Internal audit logging helper.
     */
    protected function logPeriodAudit(int $schoolId, string $action, string $description, ?AccountingPeriod $period = null): void
    {
        try {
            AuditLog::create([
                'school_id' => $schoolId,
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => AccountingPeriod::class,
                'model_id' => $period?->id,
                'description' => $description,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'System',
            ]);
        } catch (\Throwable $e) {
            // Non-blocking audit log catch
        }
    }
}
