<?php

namespace Tests\Support;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\FundingSponsor;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\School;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\Finance\BeamMigrationService;
use App\Services\Finance\FinancialAccountResolver;
use App\Services\Finance\FinancialReconciliationService;
use App\Services\Finance\ScholarshipApprovalService;
use App\Services\Finance\ScholarshipBeamReportService;
use App\Services\Finance\SponsorSettlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/** The same synthetic workflow is used by SQLite tests and the guarded MySQL runner. */
class BeamRehearsal
{
    public array $checks = [];

    public array $fixture = [];

    public function check(bool $result, string $name): void
    {
        if (! $result) {
            throw new \RuntimeException('Rehearsal check failed: '.$name);
        }
        $this->checks[] = $name;
    }

    public function fixture(): array
    {
        $tag = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
        $school = School::create(['name' => 'Synthetic BEAM rehearsal '.$tag, 'code' => 'BEAM-TEST-'.$tag]);
        foreach (['bursar', 'headmaster'] as $name) {
            Role::findOrCreate($name, 'web');
        }
        $maker = User::factory()->create(['school_id' => $school->id, 'name' => 'Synthetic Funding Maker', 'email' => 'maker-'.$tag.'@example.test']);
        $maker->assignRole(['bursar', 'headmaster']);
        $checker = User::factory()->create(['school_id' => $school->id, 'name' => 'Synthetic Funding Checker', 'email' => 'checker-'.$tag.'@example.test']);
        $checker->assignRole('headmaster');
        Auth::login($maker);
        $account = fn ($code, $type, $category) => Account::create(['school_id' => $school->id, 'code' => $code, 'name' => 'Synthetic '.$code,
            'type' => $type, 'category' => $category, 'is_active' => true, 'is_postable' => true]);
        $ar = $account('SYN-STUDENT', 'asset', 'receivable');
        $sponsorAr = $account('SYN-BEAM', 'asset', 'receivable');
        $bank = $account('SYN-BANK', 'asset', 'bank');
        $equity = $account('SYN-EQUITY', 'equity', 'retained_earnings');
        $revenue = $account('SYN-TUITION', 'revenue', 'tuition_revenue');
        $remission = $account('SYN-REMISSION', 'expense', 'other_expense');
        $sponsor = FundingSponsor::create(['school_id' => $school->id, 'code' => 'BEAM', 'name' => 'Synthetic Government BEAM', 'category' => 'government', 'is_active' => true]);
        $resolver = app(FinancialAccountResolver::class);
        foreach (['ACCOUNTS_RECEIVABLE' => $ar, 'OPENING_BALANCE_EQUITY' => $equity] as $key => $acc) {
            $resolver->setMapping($school, 'control_account', $key, $acc);
        }
        $resolver->setMapping($school, 'sponsor_receivable', $sponsor->code, $sponsorAr);
        $resolver->setMapping($school, 'fee_vote', 'TUITION', $revenue);
        $resolver->setMapping($school, 'scholarship', 'SCHOOL_REMISSION', $remission);
        $resolver->setMapping($school, 'scholarship', 'SPONSOR_RECEIVABLE', $sponsorAr);
        BankAccount::create(['school_id' => $school->id, 'account_id' => $bank->id, 'bank_name' => 'Synthetic Bank', 'account_name' => 'Synthetic rehearsal only',
            'account_number' => 'TEST-'.$tag, 'opening_balance' => 0, 'is_active' => true]);

        return $this->fixture = compact('school', 'maker', 'checker', 'ar', 'sponsorAr', 'bank', 'equity', 'revenue', 'remission', 'sponsor', 'tag');
    }

    public function student(string $label)
    {
        return \App\Models\Student::create(['school_id' => $this->fixture['school']->id, 'first_name' => 'Synthetic', 'last_name' => $label,
            'admission_number' => $this->fixture['tag'].'-'.$label, 'is_boarding' => false, 'status' => 'active', 'grade' => 'Form 1']);
    }

    public function claimRow($student, string $year, string $reference, float $approved, float $received, string $inclusion, ?int $opening = null, ?string $invoice = null): array
    {
        return ['academic_year' => $year, 'claim_reference' => $reference, 'student_admission_number' => $student->admission_number, 'student_name' => $student->full_name,
            'approved_amount' => $approved, 'recognised_receivable' => $approved, 'amount_already_received' => $received, 'outstanding_balance' => $approved - $received,
            'fee_vote_allocation' => ['TUITION' => $approved], 'claim_status' => 'approved', 'supporting_reference' => 'SYNTHETIC-DOCUMENT',
            'submitted_on' => $year.'-06-01', 'classification' => 'prior_receivable', 'opening_inclusion' => $inclusion,
            'opening_journal_batch_id' => $opening, 'opening_invoice_number' => $invoice, 'recognition_evidence' => 'Synthetic approved prior-year receivable and reviewed opening schedule', 'currency' => 'USD'];
    }

    public function batch(string $year, string $reference, array $rows, bool $approve = true)
    {
        Auth::login($this->fixture['maker']);
        $service = app(BeamMigrationService::class);
        $batch = $service->stage($this->fixture['sponsor'], $year, $reference, $rows);
        foreach ($batch->migrationBatch->stagingRows as $row) {
            $service->review($batch, $row->id, []);
        }
        if ($approve) {
            Auth::login($this->fixture['checker']);
            $service->approve($batch, 'SYNTHETIC-INDEPENDENT-APPROVAL');
        }

        return $batch->fresh();
    }

    public function run(): array
    {
        $f = $this->fixture();
        $a = $this->student('Claim-A');
        $b = $this->student('Claim-B');
        $c = $this->student('Claim-C');
        $opening = app(AccountingService::class)->createJournalBatch(['school_id' => $f['school']->id, 'transaction_date' => '2026-01-01',
            'reference_number' => 'SYN-OPEN-'.$f['tag'], 'description' => 'Synthetic reviewed opening TB', 'source_type' => 'opening_balance',
            'source_id' => $f['school']->id, 'status' => 'posted', 'created_by' => $f['maker']->id, 'entries' => [
                ['account_id' => $f['sponsorAr']->id, 'entry_type' => 'debit', 'amount' => 400], ['account_id' => $f['ar']->id, 'entry_type' => 'debit', 'amount' => 200],
                ['account_id' => $f['equity']->id, 'entry_type' => 'credit', 'amount' => 600]]]);
        $invoice = Invoice::create(['school_id' => $f['school']->id, 'student_id' => $c->id, 'number' => 'SYN-OPEN-INV-'.$f['tag'],
            'academic_year' => '2025', 'term' => 'Opening', 'issued_at' => '2025-12-31', 'total_amount' => 200, 'balance' => 200, 'status' => 'unpaid']);
        $batch23 = $this->batch('2023', 'BEAM23-'.$f['tag'], [
            $this->claimRow($a, '2023', 'BEAM23-A-'.$f['tag'], 350, 50, 'sponsor_control', $opening->id),
            $this->claimRow($b, '2023', 'BEAM23-B-'.$f['tag'], 100, 0, 'sponsor_control', $opening->id)]);
        $this->check(JournalBatch::where('school_id', $f['school']->id)->count() === 1, 'Existing sponsor opening is linked, not posted twice');
        $batch24 = $this->batch('2024', 'BEAM24-'.$f['tag'], [$this->claimRow($c, '2024', 'BEAM24-C-'.$f['tag'], 200, 0, 'student_control', $opening->id, $invoice->number)]);
        $this->check((float) $invoice->fresh()->balance === 0.0, 'Opening Student Debtors transferred once to sponsor');
        $statement = app(\App\Services\Finance\FinanceReportingService::class)->getStudentStatement($f['school'], $c, ['academic_year' => '2026']);
        $this->check($statement['closing_balance'] === 0.0, '2026 student statement carries prior-year debt and its reclassification correctly');
        $claimA = $batch23->claims()->where('student_id', $a->id)->firstOrFail();
        $claimB = $batch23->claims()->where('student_id', $b->id)->firstOrFail();
        $claimC = $batch24->claims()->firstOrFail();
        $this->check($claimA->outstanding === 300.0 && $claimB->outstanding === 100.0 && $claimC->outstanding === 200.0, 'BEAM 2023 and 2024 opening balances reconcile');
        Auth::login($f['maker']);
        $pendingStudent = $this->student('Pending');
        $pending = $this->claimRow($pendingStudent, '2025', 'PENDING-'.$f['tag'], 50, 0, 'unknown');
        $pending['recognised_receivable'] = 0;
        $pending['outstanding_balance'] = 0;
        $pending['classification'] = 'pending_claim';
        $pendingBatch = $this->batch('2025', 'PENDING-BATCH-'.$f['tag'], [$pending], false);
        $before = JournalBatch::where('school_id', $f['school']->id)->count();
        Auth::login($f['checker']);
        app(BeamMigrationService::class)->approve($pendingBatch, 'PENDING-CHECK');
        $this->check($pendingBatch->claims()->first()->fresh()->status === 'pending_claim' && JournalBatch::where('school_id', $f['school']->id)->count() === $before, 'Pending claims create no receivable or cash');
        foreach (['rejected', 'disputed'] as $classification) {
            Auth::login($f['maker']);
            $student = $this->student($classification);
            $row = $this->claimRow($student, '2025', strtoupper($classification).'-'.$f['tag'], 75, 0, 'unknown');
            $row['classification'] = $classification;
            $row['recognised_receivable'] = 0;
            $row['outstanding_balance'] = 0;
            $row[$classification.'_amount'] = 75;
            $batch = $this->batch('2025', strtoupper($classification).'-BATCH-'.$f['tag'], [$row]);
            $this->check($batch->claims()->first()->status === $classification && $batch->claims()->first()->outstanding === 0.0, $classification.' claims are separate from recognised assets');
        }
        Auth::login($f['maker']);
        $missing = $this->student('MissingOpening');
        $missingBatch = $this->batch('2025', 'MISSING-'.$f['tag'], [$this->claimRow($missing, '2025', 'MISSING-CLAIM-'.$f['tag'], 100, 0, 'not_in_opening')]);
        $this->check($missingBatch->claims()->first()->outstanding === 100.0, 'Verified missing opening correction uses equity, not revenue');
        Auth::login($f['maker']);
        $duplicate = app(BeamMigrationService::class)->stage($f['sponsor'], '2023', 'DUP-'.$f['tag'], [$this->claimRow($a, '2023', 'DUP-CLAIM-'.$f['tag'], 300, 0, 'not_in_opening')]);
        try {
            app(BeamMigrationService::class)->review($duplicate, $duplicate->migrationBatch->stagingRows->first()->id, []);
            throw new \RuntimeException('Duplicate claim accepted');
        } catch (ValidationException $e) {
            $this->checks[] = 'Duplicate sponsor/year/student opening claim rejected';
        }
        Auth::login($f['maker']);
        $settlements = app(SponsorSettlementService::class);
        $data = ['reference' => 'GOV-SET-'.$f['tag'], 'received_on' => '2026-02-01', 'amount' => 250, 'bank_account_id' => $f['bank']->id,
            'cash_received' => 1, 'supporting_reference' => 'SYNTHETIC-BANK-EVIDENCE', 'allocations' => [
                ['target_type' => 'claim', 'target_id' => $claimA->id, 'amount' => 150], ['target_type' => 'claim', 'target_id' => $claimC->id, 'amount' => 100]]];
        $settlement = $settlements->prepare($f['sponsor'], $data);
        try {
            $settlements->approve($settlement, 'SELF');
            throw new \RuntimeException('Self approval accepted');
        } catch (ValidationException $e) {
            $this->checks[] = 'Self-approval rejected';
        }
        Auth::login($f['checker']);
        $settlements->approve($settlement, 'CHECK-GOV-SET');
        $firstJournal = $settlement->fresh()->journal_batch_id;
        $settlements->approve($settlement, 'RETRY-CHECK');
        $this->check($settlement->fresh()->journal_batch_id === $firstJournal, 'Duplicate settlement approval returns the original posting');
        $this->check($claimA->fresh()->outstanding === 150.0 && $claimC->fresh()->outstanding === 100.0, 'One government payment partially settles multiple students and years');
        Auth::login($f['maker']);
        $again = $settlements->prepare($f['sponsor'], $data);
        $this->check($again->id === $settlement->id, 'Duplicate bank reference and allocation terms are idempotent');
        $second = $settlements->prepare($f['sponsor'], ['reference' => 'GOV-SECOND-'.$f['tag'], 'received_on' => '2026-03-01', 'amount' => 50, 'bank_account_id' => $f['bank']->id,
            'cash_received' => 1, 'supporting_reference' => 'SYNTHETIC-BANK-2', 'allocations' => [['target_type' => 'claim', 'target_id' => $claimA->id, 'amount' => 25], ['target_type' => 'claim', 'target_id' => $claimB->id, 'amount' => 25]]]);
        Auth::login($f['checker']);
        $settlements->approve($second, 'CHECK-SECOND');
        Auth::login($f['maker']);
        $correction = app(ScholarshipApprovalService::class)->request($second, 'settlement_reverse', '2026-03-02', 'Synthetic bank correction');
        $this->check($second->fresh()->status === 'posted', 'Requesting correction does not alter posted cash');
        Auth::login($f['checker']);
        app(ScholarshipApprovalService::class)->approve($correction, 'CHECK-CORRECTION');
        $this->check($second->fresh()->status === 'reversed' && $claimB->fresh()->outstanding === 100.0, 'Checked reversal restores sponsor allocations and cash exactly once');
        $report = app(ScholarshipBeamReportService::class)->build($f['school']->id, '2026-12-31');
        $this->check($report['controls']->every(fn ($row) => $row['difference'] === 0.0), 'Sponsor GL and claims subledger reconcile');
        $this->check($report['student_control']['is_reconciled'], 'Student Debtors GL and invoices reconcile');
        $this->check($report['trial_balance']['is_balanced'], 'Trial Balance remains balanced');
        $bankReport = app(FinancialReconciliationService::class)->reconcileBankAndCashbook($f['school']);
        $this->check(collect($bankReport)->every(fn ($row) => $row['is_reconciled']), 'Bank, Cashbook and GL reconcile');
        $revenue = (float) JournalEntry::where('account_id', $f['revenue']->id)->sum('amount');
        $this->check($revenue === 0.0,'Historical recognition and 2026 settlements create no fee revenue');
        $this->check($report['student_vs_sponsor']['sponsor_outstanding'] === 450.0,'Consolidated sponsor outstanding is 450 after controlled correction');

        return ['school_id' => $f['school']->id, 'checks' => $this->checks, 'controls' => $report['controls']->all(), 'student_control' => $report['student_control'],
            'trial_balance' => $report['trial_balance'], 'bank' => $bankReport, 'sponsor_outstanding' => $report['student_vs_sponsor']['sponsor_outstanding'],
            'settlement_id' => $settlement->id, 'checker_id' => $f['checker']->id, 'claim_id' => $claimA->id, 'bank_id' => $f['bank']->id, 'sponsor_id' => $f['sponsor']->id, 'maker_id' => $f['maker']->id];
    }
}
