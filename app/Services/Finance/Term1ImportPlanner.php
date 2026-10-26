<?php

namespace App\Services\Finance;

use InvalidArgumentException;

/** Pure snapshot validation: never calls models, resolvers, or the database. */
class Term1ImportPlanner
{
    public function cents(mixed $value): ?int
    {
        if (! is_scalar($value) || ! preg_match('/^(0|[1-9]\d{0,9})(?:\.(\d{1,2}))?$/D', (string) $value, $m)) {
            return null;
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    public function number(string $reference): string
    {
        return 'T1-2026-'.substr(hash('sha256', $reference), 0, 24);
    }

    public function fingerprint(array $row): string
    {
        $sort = function (array $data) use (&$sort): array {
            if (! array_is_list($data)) {
                ksort($data);
            }
            foreach ($data as &$value) {
                if (is_array($value)) {
                    $value = $sort($value);
                }
            }

            return $data;
        };

        return hash('sha256', json_encode($sort($row), JSON_THROW_ON_ERROR));
    }

    public function validateManifest(array $plan, array $snapshot): void
    {
        if (($plan['format'] ?? '') !== 'hande-term1-import-v1' || ($plan['academic_year'] ?? '') !== '2026'
            || ($plan['term'] ?? '') !== 'Term 1' || ($plan['issued_at'] ?? '') !== '2026-01-01'
            || ($plan['due_date'] ?? '') !== '2026-01-31' || ($plan['school_id'] ?? null) !== ($snapshot['school']['id'] ?? null)
            || ($plan['school_code'] ?? '') !== ($snapshot['school']['code'] ?? null)
            || ($snapshot['format'] ?? '') !== 'hande-term1-reconciliation-v1'
            || ! preg_match('/^[a-f0-9]{64}$/D', $plan['source_sha256'] ?? '')
            || ! is_array($plan['rows'] ?? null) || count($plan['rows']) > 5000 || ! empty($plan['payments'])) {
            throw new InvalidArgumentException('Invalid tenant/Term 1 manifest. Payment execution is disabled pending its separate policy approval.');
        }
        $refs = [];
        foreach ($plan['rows'] as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Every Term 1 manifest row must be an object.');
            }
            $ref = $row['charge_reference'] ?? '';
            if (! is_string($ref) || ! preg_match('/^[A-Za-z0-9_\/-]{1,100}$/D', $ref) || isset($refs[$ref])) {
                throw new InvalidArgumentException('Historical charge references must be present and unique.');
            }
            $refs[$ref] = true;
        }
        foreach ($snapshot['data'] ?? [] as $records) {
            foreach ($records as $record) {
                if (isset($record['school_id']) && (int) $record['school_id'] !== (int) $plan['school_id']) {
                    throw new InvalidArgumentException('Cross-school snapshot data rejected.');
                }
            }
        }
    }

    public function preview(array $plan, array $snapshot): array
    {
        $this->validateManifest($plan, $snapshot);
        $data = $snapshot['data'];
        $students = array_column($data['students'], null, 'id');
        $accounts = array_column($data['accounts'], null, 'id');
        $mapping = [];
        foreach ($data['account_mappings'] as $m) {
            if ($m['is_active']) {
                $mapping[$m['mapping_type'].'|'.strtoupper($m['source_key'])][] = $m['account_id'];
            }
        }
        $account = function (string $key, string $type) use ($mapping, $accounts, $plan): ?int {
            $ids = $mapping[$key] ?? [];
            $a = count($ids) === 1 ? ($accounts[$ids[0]] ?? null) : null;

            return $a && (int) $a['school_id'] === (int) $plan['school_id'] && $a['type'] === $type && $a['is_active']
                && ($a['is_postable'] ?? false) && ($a['currency'] ?? null) === 'USD' ? (int) $a['id'] : null;
        };
        $ar = $account('control_account|ACCOUNTS_RECEIVABLE', 'asset');
        $result = [];
        $studentUses = array_count_values(array_map('strval', array_filter(array_column($plan['rows'], 'student_id'))));
        $newIdentities = [];
        foreach ($plan['rows'] as $row) {
            $errors = [];
            $amount = $this->cents($row['expected_amount'] ?? null);
            $scholarship = ($row['source_amount_blank'] ?? false) === true;
            $id = $row['student_id'] ?? null;
            $student = $students[$id] ?? null;
            if ($scholarship) {
                $errors[] = 'Scholarship-related blank charge: funding source pending; no zero-value invoice.';
            }
            if ($amount === null || $amount <= 0) {
                $errors[] = 'A positive original source charge is required.';
            }
            if (($row['identity_verified'] ?? false) !== true || empty($row['identity_evidence'])) {
                $errors[] = 'Identity verification/evidence missing.';
            }
            if ($id) {
                if (! $student || (int) $student['school_id'] !== (int) $plan['school_id']) {
                    $errors[] = 'Stable student ID is not in this tenant.';
                } elseif (($student['funding_status'] ?? 'regular') !== 'regular') {
                    $errors[] = 'Existing scholarship funding requires separate review.';
                }
                foreach ($row['student_before'] ?? [] as $field => $before) {
                    if (! in_array($field, ['id', 'first_name', 'last_name', 'grade', 'class_name', 'is_boarding', 'funding_status'], true)
                        || ! array_key_exists($field, $student ?? []) || $student[$field] !== $before) {
                        $errors[] = 'Student before-values changed or invalid.';
                    }
                }
                if (empty($row['student_before']) || ! isset($row['student_before']['id']) || $row['student_before']['id'] !== $id) {
                    $errors[] = 'An approved stable-ID before-snapshot is required.';
                }
                if (($studentUses[(string) $id] ?? 0) > 1 && empty($row['distinct_charge_evidence'])) {
                    $errors[] = 'Repeated student requires evidence of separate historical charges.';
                }
            } else {
                $new = $row['new_student'] ?? [];
                $admissionKey = mb_strtolower(trim((string) ($new['admission_number'] ?? '')));
                if ($admissionKey !== '' && isset($newIdentities[$admissionKey])) {
                    $errors[] = 'Duplicate proposed new-student admission identity in this manifest.';
                }
                if ($admissionKey !== '') {
                    $newIdentities[$admissionKey] = true;
                }
                if (($new['confirmed_absent'] ?? false) !== true || empty($new['absence_evidence'])
                    || empty($new['first_name']) || empty($new['last_name']) || empty($new['admission_number'])
                    || ! is_bool($new['is_boarding'] ?? null)) {
                    $errors[] = 'New student absence and genuine required fields not verified.';
                }
                foreach ($students as $s) {
                    $full = fn ($first, $last) => preg_replace('/\s+/', ' ', mb_strtolower(trim($first.' '.$last)));
                    if (($s['admission_number'] && $s['admission_number'] === ($new['admission_number'] ?? null))
                        || $full($s['first_name'], $s['last_name']) === $full($new['first_name'] ?? '', $new['last_name'] ?? '')
                        || $full($s['first_name'], $s['last_name']) === $full($new['last_name'] ?? '', $new['first_name'] ?? '')) {
                        $errors[] = 'Possible existing student; new creation prohibited.';
                        break;
                    }
                }
            }
            if (($row['charges_verified'] ?? false) !== true || empty($row['charge_evidence'])) {
                $errors[] = 'Historical charges/votes require supporting approval.';
            }
            if (! is_string($row['original_category'] ?? null) || trim($row['original_category']) === '') {
                $errors[] = 'Original fee category is missing.';
            }
            $sum = 0;
            $votes = [];
            $credits = [];
            foreach ($row['lines'] ?? [] as $line) {
                $vote = $line['vote_code'] ?? '';
                $cents = $this->cents($line['amount'] ?? null);
                $revenue = $account('fee_vote|'.$vote, 'revenue');
                if (! preg_match('/^[A-Z][A-Z0-9_]{0,49}$/D', $vote) || isset($votes[$vote]) || ! $cents || ! $revenue
                    || ($line['revenue_account_id'] ?? null) !== $revenue || empty($line['source_evidence']) || empty($line['description'])) {
                    $errors[] = 'Invalid, repeated, unsupported or unmapped historical vote.';
                }
                $votes[$vote] = true;
                $sum += $cents ?? 0;
                if ($revenue) {
                    $credits[$revenue] = ($credits[$revenue] ?? 0) + ($cents ?? 0);
                }
            }
            if (! $ar || $sum !== $amount || empty($row['lines'])) {
                $errors[] = 'Verified debtor/revenue mappings and exact source-to-vote totals required.';
            }
            $periods = array_filter($data['accounting_periods'] ?? [], fn ($p) => substr((string) $p['start_date'], 0, 10) <= '2026-01-01' && substr((string) $p['end_date'], 0, 10) >= '2026-01-01');
            if (! $periods || count(array_filter($periods, fn ($p) => $p['status'] !== 'open'))) {
                $errors[] = 'An explicitly open accounting period covering 1 January 2026 is required.';
            }
            foreach ($data['invoices'] as $invoice) {
                if ($invoice['number'] === $this->number($row['charge_reference'])
                    || ($id && (int) $invoice['student_id'] === (int) $id && $invoice['academic_year'] === '2026'
                        && in_array($invoice['term'], ['Term 1', 'First Term', '1'], true) && $invoice['status'] !== 'cancelled'
                        && empty($row['distinct_charge_evidence']))) {
                    $errors[] = 'Existing historical invoice requires recorded idempotency or explicit separate-charge review.';
                }
            }
            $result[] = ['reference' => $row['charge_reference'], 'student_id' => $id, 'scholarship_related' => $scholarship,
                'status' => $errors ? 'HOLD' : 'READY', 'amount_cents' => $amount, 'errors' => array_values(array_unique($errors)),
                'debit_account_id' => $ar, 'credits_cents' => $credits, 'payload_hash' => $this->fingerprint($row)];
        }
        $ready = array_values(array_filter($result, fn ($r) => $r['status'] === 'READY'));

        return ['rows' => $result, 'cleared_students' => count(array_unique(array_map(fn ($r) => $r['student_id'] ?: $r['reference'], $ready))),
            'cleared_invoices' => count($ready), 'cleared_amount_cents' => array_sum(array_column($ready, 'amount_cents')),
            'cleared_payments' => 0, 'cleared_payment_cents' => 0,
            'scholarship_holds' => count(array_filter($result, fn ($r) => $r['scholarship_related']))];
    }
}
