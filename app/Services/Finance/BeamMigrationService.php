<?php

namespace App\Services\Finance;

use App\Models\FundingSponsor;
use App\Models\MigrationStagingRow;
use App\Models\School;
use App\Models\SponsorClaimBatch;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class BeamMigrationService
{
    public const HEADERS = ['academic_year', 'claim_reference', 'student_admission_number', 'student_name', 'approved_amount',
        'recognised_receivable', 'amount_already_received', 'outstanding_balance', 'fee_vote_allocation', 'claim_status',
        'supporting_reference', 'submitted_on', 'classification', 'opening_inclusion', 'opening_invoice_number', 'opening_journal_batch_id',
        'recognition_evidence', 'disputed_amount', 'rejected_amount', 'accounting_date', 'currency'];

    public function stage(FundingSponsor $sponsor, string $year, string $reference, array $rows): SponsorClaimBatch
    {
        $actor = SponsorFinanceSupport::actor($sponsor->school_id);
        if ($sponsor->category !== 'government' || ! preg_match('/^20\d{2}$/', $year) || trim($reference) === '' || ! $rows || count($rows) > 5000) {
            SponsorFinanceSupport::fail('Choose a government sponsor, claim year, reference and at most 5,000 rows.');
        }

        return DB::transaction(function () use ($sponsor, $year, $reference, $rows, $actor) {
            School::whereKey($sponsor->school_id)->lockForUpdate()->firstOrFail();
            if (SponsorClaimBatch::where('school_id', $sponsor->school_id)->where('reference', $reference)->exists()) {
                SponsorFinanceSupport::fail('Batch reference already exists.');
            }
            $migration = app(HistoricalMigrationService::class)->createBatch($sponsor->school_id, 'beam_claims', $reference.'.csv', $actor, ['sponsor_id' => $sponsor->id, 'claim_year' => $year]);
            $batch = SponsorClaimBatch::create(['school_id' => $sponsor->school_id, 'funding_sponsor_id' => $sponsor->id,
                'academic_year' => $year, 'reference' => $reference, 'migration_batch_id' => $migration->id, 'created_by' => $actor, 'status' => 'pending_verification',
                'submitted_on' => ! empty($rows[0]['submitted_on']) ? \Carbon\Carbon::parse($rows[0]['submitted_on'])->toDateString() : null]);
            foreach ($rows as $index => $row) {
                MigrationStagingRow::create(['migration_batch_id' => $migration->id, 'school_id' => $sponsor->school_id, 'row_number' => $index + 1,
                    'accounting_date' => '2026-01-01', 'reference_number' => $row['claim_reference'] ?? null, 'description' => 'Unverified BEAM history',
                    'amount' => max(0, (float) ($row['outstanding_balance'] ?? 0)), 'direction' => 'debit', 'classification' => 'unverified',
                    'student_admission_number' => $row['student_admission_number'] ?? null, 'raw_data' => $row, 'status' => 'pending',
                    'idempotency_key' => hash('sha256', json_encode([$sponsor->id, $year, $row['student_admission_number'] ?? '', $row['claim_reference'] ?? '']))]);
            }
            $migration->update(['total_rows' => count($rows)]);
            SponsorFinanceSupport::audit($batch, 'beam_staged', ['rows' => count($rows)]);

            return $batch;
        });
    }

    public function review(SponsorClaimBatch $batch, int $rowId, array $review): MigrationStagingRow
    {
        SponsorFinanceSupport::actor($batch->school_id);

        return DB::transaction(function () use ($batch, $rowId, $review) {
            $batch = SponsorClaimBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($batch->status !== 'pending_verification') {
                SponsorFinanceSupport::fail('Reviewed batch is frozen.');
            }
            $row = $batch->migrationBatch->stagingRows()->where('school_id', $batch->school_id)->lockForUpdate()->findOrFail($rowId);
            $raw = array_merge($row->raw_data, array_intersect_key($review, array_flip(['classification', 'opening_inclusion', 'recognition_evidence',
                'opening_invoice_number', 'opening_journal_batch_id', 'claim_status', 'disputed_amount', 'rejected_amount', 'accounting_date', 'currency'])));
            $classification = $raw['classification'] ?? 'unverified';
            if (! in_array($classification, ['prior_receivable', 'current_receivable', 'pending_claim', 'bank_deposit', 'allocation', 'adjustment', 'rejected', 'disputed'], true)
                || empty($raw['supporting_reference']) || empty($raw['recognition_evidence'])) {
                SponsorFinanceSupport::fail('Explicit classification, supporting reference and verification evidence are required.');
            }
            if ((string) ($raw['academic_year'] ?? '') !== (string) $batch->academic_year) {
                SponsorFinanceSupport::fail('Claim year differs from batch.');
            }
            $nonClaim = in_array($classification, ['bank_deposit', 'allocation', 'adjustment']);
            $student = null;
            if (! $nonClaim || ! empty($raw['student_admission_number'])) {
                $students = Student::where('school_id', $batch->school_id)->where('admission_number', $raw['student_admission_number'] ?? '')->get();
                if ($students->count() !== 1) {
                    SponsorFinanceSupport::fail('Admission number must match exactly one student in this school.');
                }
                $student = $students->first();
                if (mb_strtolower(trim($student->full_name)) !== mb_strtolower(trim($raw['student_name'] ?? ''))) {
                    SponsorFinanceSupport::fail('Student name does not match the admission number.');
                }
            }
            if (! $nonClaim && ($batch->migrationBatch->stagingRows()->where('id', '!=', $row->id)->where('matched_entity_id', $student->id)
                ->whereNotIn('classification', ['bank_deposit', 'allocation', 'adjustment', 'unverified'])->exists()
                || $batch->sponsor->claims()->where('academic_year', $batch->academic_year)->where('student_id', $student->id)->where('status','!=','reversed')->exists())) {
                SponsorFinanceSupport::fail('Duplicate sponsor/year/beneficiary claim. Consolidate or correct the original claim.');
            }
            $votes = $raw['fee_vote_allocation'] ?? [];
            if (is_string($votes)) {
                $votes = json_decode($votes, true);
            }
            $invoiceId = null;
            if (! empty($raw['opening_invoice_number'])) {
                $invoiceId = $student->invoices()->where('school_id', $batch->school_id)->where('number', $raw['opening_invoice_number'])->firstOrFail()->id;
            }
            $parsed = ['student_id' => $student?->id, 'academic_year' => $batch->academic_year, 'reference' => $raw['claim_reference'] ?? '',
                'approved_amount' => $raw['approved_amount'] ?? 0, 'recognised_amount' => $raw['recognised_receivable'] ?? 0,
                'received_before_cutover' => $raw['amount_already_received'] ?? 0, 'outstanding_balance' => $raw['outstanding_balance'] ?? 0,
                'fee_votes' => $votes, 'classification' => $classification, 'opening_inclusion' => $raw['opening_inclusion'] ?? 'unknown',
                'recognition_evidence' => $raw['recognition_evidence'], 'supporting_reference' => $raw['supporting_reference'],
                'opening_journal_batch_id' => empty($raw['opening_journal_batch_id']) ? null : (int) $raw['opening_journal_batch_id'],
                'invoice_id' => $invoiceId, 'disputed_amount' => $raw['disputed_amount'] ?? 0, 'rejected_amount' => $raw['rejected_amount'] ?? 0,
                'accounting_date' => empty($raw['accounting_date']) ? null : $raw['accounting_date'],
                'submitted_on' => empty($raw['submitted_on']) ? null : $raw['submitted_on'], 'currency' => empty($raw['currency']) ? null : strtoupper($raw['currency'])];
            if (! $nonClaim) {
                app(SponsorClaimService::class)->validateTerms($batch, $parsed);
            }
            $row->update(['classification' => $classification, 'parsed_data' => $parsed, 'matched_entity_type' => Student::class,
                'matched_entity_id' => $student?->id, 'status' => 'validated', 'validation_errors' => null]);
            SponsorFinanceSupport::audit($batch, 'beam_row_reviewed', ['row_id' => $row->id, 'classification' => $classification, 'evidence' => $raw['recognition_evidence']]);

            return $row;
        });
    }

    public function approve(SponsorClaimBatch $batch, string $reference): SponsorClaimBatch
    {
        return DB::transaction(function () use ($batch, $reference) {
            School::whereKey($batch->school_id)->lockForUpdate()->firstOrFail();
            $batch = SponsorClaimBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            SponsorFinanceSupport::checker($batch->school_id, $batch->created_by, $reference);
            if ($batch->status === 'posted') {
                return $batch;
            }
            $rows = $batch->migrationBatch->stagingRows()->lockForUpdate()->get();
            if ($rows->contains(fn ($row) => $row->status !== 'validated')) {
                SponsorFinanceSupport::fail('Every row must be verified before approval.');
            }
            foreach ($rows as $row) {
                if (in_array($row->classification, ['bank_deposit', 'allocation', 'adjustment'])) {
                    continue;
                } // Stay staged; no inferred cash or GL.
                app(SponsorClaimService::class)->createClaim($batch, array_merge($row->parsed_data, ['migration_staging_row_id' => $row->id]));
            }
            if ($batch->claims()->exists()) {
                app(SponsorClaimService::class)->approveBatch($batch, $reference);
            } else {
                $batch->update(['status' => 'classified_only', 'approved_by' => auth()->id(), 'approval_reference' => $reference]);
            }
            $claimRows = $rows->filter(fn ($row) => ! in_array($row->classification, ['bank_deposit', 'allocation', 'adjustment']));
            $batch->migrationBatch->update(['status' => $claimRows->count() === $rows->count() ? 'posted' : ($claimRows->isEmpty() ? 'validated' : 'partially_posted'), 'approved_by' => auth()->id(), 'approved_at' => now(),
                'posted_at' => now(), 'posted_rows' => $rows->filter(fn ($row) => ! in_array($row->classification, ['bank_deposit', 'allocation', 'adjustment']))->count()]);
            SponsorFinanceSupport::audit($batch,'beam_review_approved',['reference' => $reference]);

            return $batch->fresh();
        });
    }
}
