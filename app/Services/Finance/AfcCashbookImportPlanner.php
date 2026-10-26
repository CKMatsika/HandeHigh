<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Student;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/** Classifies January-April AFC lines and refuses rows without source-backed posting fields. */
class AfcCashbookImportPlanner
{
    private const TYPES = ['student_receipt', 'expense', 'transfer', 'bank_movement', 'other_income'];

    public function preview(array $manifest, int $schoolId): array
    {
        if (($manifest['scope'] ?? null) !== 'afc_cashbook_2026_jan_apr' || ! is_array($manifest['rows'] ?? null)) {
            throw new InvalidArgumentException('AFC manifest must be explicitly scoped to January-April 2026.');
        }
        $seen = [];
        $seenReferences = [];
        $rows = [];
        $totals = ['all_cents' => 0, 'ready_cents' => 0, 'held_cents' => 0, 'by_type_cents' => [], 'by_month_cents' => []];
        foreach ($manifest['rows'] as $index => $source) {
            $errors = [];
            $date = $source['transaction_date'] ?? null;
            try {
                $parsed = CarbonImmutable::parse($date);
                if ($parsed->year !== 2026 || $parsed->month < 1 || $parsed->month > 4 || $parsed->toDateString() !== $date) {
                    $errors[] = 'Date must be an original, explicit January-April 2026 date.';
                }
            } catch (\Throwable) {
                $errors[] = 'Original transaction date is missing or invalid.';
                $parsed = null;
            }
            $type = $source['transaction_type'] ?? null;
            if (! in_array($type, self::TYPES, true)) {
                $errors[] = 'Transaction must be explicitly classified from source evidence.';
            }
            $amountCents = $this->cents($source['amount'] ?? null);
            $totals['all_cents'] += $amountCents;
            $typeKey = in_array((string) $type, self::TYPES, true) ? $type : 'unclassified';
            $totals['by_type_cents'][$typeKey] = ($totals['by_type_cents'][$typeKey] ?? 0) + $amountCents;
            if ($parsed) {
                $monthKey = $parsed->format('Y-m');
                $totals['by_month_cents'][$monthKey] = ($totals['by_month_cents'][$monthKey] ?? 0) + $amountCents;
            }
            if ($amountCents <= 0) {
                $errors[] = 'Amount must be a supported positive value.';
            }
            $reference = trim((string) ($source['source_reference'] ?? ''));
            if ($reference === '') {
                $errors[] = 'Original receipt, voucher or bank reference is required.';
            } elseif (isset($seenReferences[strtoupper($reference)])) {
                $errors[] = 'Source reference must be unique in the approved batch.';
            } else {
                $seenReferences[strtoupper($reference)] = true;
            }
            if (trim((string) ($source['description'] ?? '')) === '') {
                $errors[] = 'Original description is required.';
            }
            if (empty($source['source_evidence'])) {
                $errors[] = 'Source evidence is required.';
            }
            $key = $parsed ? hash('sha256', implode('|', [$schoolId, $parsed->toDateString(), strtoupper($reference), $amountCents, (string) $type])) : null;
            if ($key && isset($seen[$key])) {
                $errors[] = 'Duplicate AFC row in this manifest.';
            }
            if ($key) {
                $seen[$key] = true;
            }

            $student = null;
            $invoice = null;
            if ($type === 'student_receipt') {
                $student = Student::where('school_id', $schoolId)->where('admission_number', $source['student_admission_number'] ?? '')->first();
                if (! $student) {
                    $errors[] = 'Receipt student does not match a stable ERP admission number.';
                }
                $invoice = $student ? Invoice::where('school_id', $schoolId)->where('student_id', $student->id)
                    ->where('academic_year', '2026')->where('term', 'Term 1')->where('number', $source['invoice_number'] ?? '')->first() : null;
                if (! $invoice) {
                    $errors[] = 'Receipt is not linked to an existing Term 1 2026 invoice.';
                } elseif ($amountCents > $this->cents($invoice->balance)) {
                    $errors[] = 'Receipt exceeds the locked invoice balance; approved allocation is required.';
                }
                if (empty($source['receipt_number'])) {
                    $errors[] = 'Original receipt number is required for student receipts.';
                }
            }
            if (in_array($type, ['student_receipt', 'expense', 'transfer', 'bank_movement', 'other_income'], true)) {
                $cash = Account::where('school_id', $schoolId)->whereKey($source['account_id'] ?? 0)->where('is_active', true)->where('is_postable', true)->first();
                if (! $cash || ! in_array($cash->category, ['cash', 'bank'], true)) {
                    $errors[] = 'A verified, active, postable same-school cash or bank account is required.';
                }
            }
            if (in_array($type, ['expense', 'other_income'], true)) {
                if (empty($source['category']) || empty($source['offset_account_id'])) {
                    $errors[] = 'A source-supported category and offset account are required.';
                }
                $offset = Account::where('school_id', $schoolId)->whereKey($source['offset_account_id'] ?? 0)->where('is_active', true)->where('is_postable', true)->first();
                if (! $offset) {
                    $errors[] = 'A verified active same-school offset account is required.';
                } elseif ($type === 'expense' && ! in_array($offset->type, ['expense', 'liability'], true)) {
                    $errors[] = 'Expense rows require an approved expense or liability offset account.';
                } elseif ($type === 'other_income' && $offset->type !== 'revenue') {
                    $errors[] = 'Other income rows require an approved revenue offset account.';
                }
            }
            if ($type === 'transfer') {
                $to = Account::where('school_id', $schoolId)->whereKey($source['to_account_id'] ?? 0)->where('is_active', true)->where('is_postable', true)->first();
                if (! $to || (int) $to->id === (int) ($source['account_id'] ?? 0) || ! ($source['paired_transfer_reference'] ?? null)) {
                    $errors[] = 'A linked transfer reference and a distinct verified destination account are required.';
                }
            }
            if ($type === 'student_receipt' && empty($source['payment_method'])) {
                $errors[] = 'Source-supported payment method is required.';
            }
            if ($type === 'bank_movement') {
                $errors[] = 'Unreconciled bank-statement movements are evidence only and cannot be posted as a new cashbook event.';
            }
            $rows[] = ['row_number' => $index + 1, 'source_reference' => $reference, 'transaction_type' => $type,
                'amount_cents' => $amountCents, 'idempotency_key' => $key, 'student_id' => $student?->id,
                'invoice_id' => $invoice?->id, 'status' => $errors ? 'HOLD' : 'READY', 'errors' => $errors];
            $bucket = $errors ? 'held_cents' : 'ready_cents';
            $totals[$bucket] += $amountCents;
        }

        return ['scope' => $manifest['scope'], 'rows' => $rows,
            'ready_count' => count(array_filter($rows, fn ($row) => $row['status'] === 'READY')),
            'held_count' => count(array_filter($rows, fn ($row) => $row['status'] === 'HOLD')),
            'amount_totals_cents' => $totals];
    }

    private function cents(mixed $value): int
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            return 0;
        }

        return (int) round((float) $value * 100, 0, PHP_ROUND_HALF_UP);
    }
}
