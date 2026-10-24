<?php

namespace Tests\Feature\Finance;

use App\Models\FundingSponsor;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalBatch;
use App\Models\SponsorClaim;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\Finance\BeamMigrationService;
use App\Services\Finance\ScholarshipBeamReportService;
use App\Services\Finance\ScholarshipService;
use App\Services\Finance\SponsorSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\Support\BeamRehearsal;
use Tests\TestCase;

class BeamFundingTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_historical_beam_claims_and_multiyear_settlement_workflow(): void
    {
        $result = (new BeamRehearsal)->run();
        $this->assertCount(21, $result['checks']);
        $this->assertSame(450.0, $result['sponsor_outstanding']);
        file_put_contents(storage_path('logs/beam-local-rehearsal.json'), json_encode($result, JSON_PRETTY_PRINT));
    }

    private function fixture(): array
    {
        $runner = new BeamRehearsal;

        return [$runner, $runner->fixture()];
    }

    public function test_combined_internal_and_external_awards_share_eligible_votes_without_excess(): void
    {
        [$r,$f] = $this->fixture();
        $student = $r->student('Combined');
        $invoice = Invoice::create(['school_id' => $f['school']->id, 'student_id' => $student->id, 'number' => 'SYN-COMBINED', 'academic_year' => '2026',
            'term' => 'Term 1', 'issued_at' => '2026-01-05', 'total_amount' => 100, 'balance' => 100, 'status' => 'unpaid']);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'vote_code' => 'TUITION', 'description' => 'Synthetic tuition', 'quantity' => 1, 'unit_amount' => 100, 'line_total' => 100]);
        app(AccountingService::class)->postInvoice($invoice);
        $service = app(ScholarshipService::class);
        foreach ([['INTERNAL', 40, 'school_remission'], ['EXTERNAL', 60, 'sponsor_receivable']] as [$reference,$percent,$treatment]) {
            Auth::login($f['maker']);
            $award = $service->saveAward($student, ['reference' => $reference, 'name' => $reference, 'sponsor' => $treatment === 'sponsor_receivable' ? 'Synthetic government' : null,
                'funding_sponsor_id' => $treatment === 'sponsor_receivable' ? $f['sponsor']->id : null, 'coverage_type' => 'partial', 'coverage_basis' => 'percentage',
                'coverage_value' => $percent, 'covered_votes' => ['TUITION'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31',
                'accounting_treatment' => $treatment, 'recognition_evidence' => 'Synthetic enforceable sponsor obligation']);
            Auth::login($f['checker']);
            $service->approve($award, 'INDEPENDENT');
            $service->apply($award, $invoice, '2026-01-05');
        }
        $this->assertEquals(0, $invoice->fresh()->balance);
        $this->assertEquals(60, $invoice->scholarshipApplications->sum('sponsor_balance'));
        $this->assertEquals(100, $invoice->scholarshipApplications->sum('amount'));
        $this->assertEquals(100, \App\Models\JournalEntry::where('account_id', $f['revenue']->id)->where('entry_type', 'credit')->sum('amount'));
    }

    public function test_external_full_award_requires_recognition_evidence_and_no_self_approval(): void
    {
        [$r,$f] = $this->fixture();
        $student = $r->student('External');
        $service = app(ScholarshipService::class);
        $award = $service->saveAward($student, ['reference' => 'FULL-EXTERNAL', 'name' => 'Full external', 'funding_sponsor_id' => $f['sponsor']->id,
            'coverage_type' => 'full', 'coverage_basis' => 'percentage', 'coverage_value' => 100, 'covered_votes' => ['TUITION'],
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'accounting_treatment' => 'sponsor_receivable']);
        try {
            $service->approve($award, 'SELF');
            $this->fail('Self approval must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approval_reference', $e->errors());
        }
        Auth::login($f['checker']);
        try {
            $service->approve($award, 'CHECK');
            $this->fail('Unenforceable nomination must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('recognition_evidence', $e->errors());
        }
        $this->assertSame('pending', $award->fresh()->status);
        Auth::login($f['maker']);
        $service->saveAward($student, array_merge($award->toArray(), ['recognition_evidence' => 'Synthetic approved enforceable full obligation']), $award);
        Auth::login($f['checker']);
        $service->approve($award, 'CHECKED-ENFORCEABLE');
        $invoice = Invoice::create(['school_id' => $f['school']->id, 'student_id' => $student->id, 'number' => 'EXT-FULL', 'academic_year' => '2026', 'term' => 'Term 1',
            'issued_at' => '2026-01-05', 'total_amount' => 100, 'balance' => 100, 'status' => 'unpaid']);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'vote_code' => 'TUITION', 'description' => 'Tuition', 'quantity' => 1, 'unit_amount' => 100, 'line_total' => 100]);
        app(AccountingService::class)->postInvoice($invoice);
        $application = $service->apply($award->fresh(), $invoice, '2026-01-05');
        $this->assertEquals(0, $invoice->fresh()->balance);
        $this->assertSame(100.0, $application->sponsor_balance);
        $this->assertSame(0, \App\Models\Cashbook::count());
    }

    public function test_unverified_deposit_and_claim_classifications_never_post_automatically(): void
    {
        [$r,$f] = $this->fixture();
        $student = $r->student('Unverified');
        $row = $r->claimRow($student, '2023', 'BEAM-DEPOSIT', 100, 0, 'unknown');
        $batch = app(BeamMigrationService::class)->stage($f['sponsor'], '2023', 'UNVERIFIED', [$row]);
        Auth::login($f['checker']);
        try {
            app(BeamMigrationService::class)->approve($batch, 'CHECK');
            $this->fail('Unverified rows cannot post');
        } catch (ValidationException $e) {
            $this->assertSame(0, JournalBatch::count());
        }
        Auth::login($f['maker']);
        app(BeamMigrationService::class)->review($batch, $batch->migrationBatch->stagingRows->first()->id, ['classification' => 'bank_deposit']);
        Auth::login($f['checker']);
        app(BeamMigrationService::class)->approve($batch, 'CLASSIFIED');
        $this->assertSame(0, JournalBatch::count());
        $this->assertSame(0, SponsorClaim::count());
        $this->assertSame('validated', $batch->migrationBatch->stagingRows->first()->fresh()->status);
    }

    public function test_government_funding_routes_reports_template_and_tenant_isolation(): void
    {
        [$r,$f] = $this->fixture();
        $this->get(route('admin.funding.index'))->assertOk()->assertSee('BEAM');
        foreach (['beneficiaries', 'internal_expenditure', 'external_receivables', 'beam_claims', 'beam_ageing', 'payments', 'coverage', 'controls'] as $report) {
            $this->get(route('admin.funding.reports', ['report' => $report]))->assertOk();
        }
        $this->get(route('admin.funding.template'))->assertOk();
        $other = \App\Models\School::create(['name' => 'Other school', 'code' => 'OTHER-BEAM']);
        $foreign = FundingSponsor::create(['school_id' => $other->id, 'code' => 'BEAM', 'name' => 'Other BEAM', 'category' => 'government']);
        $this->post(route('admin.funding.mapping', $foreign), ['account_id' => $f['sponsorAr']->id, 'approval_reference' => 'Cross-school'])->assertNotFound();
        $clerk = User::factory()->create(['school_id' => $f['school']->id]);
        \Spatie\Permission\Models\Role::findOrCreate('accounts-clerk', 'web');
        $clerk->assignRole('accounts-clerk');
        $this->actingAs($clerk)->get(route('admin.funding.index'))->assertForbidden();
    }

    public function test_current_year_beam_claim_transfers_selected_invoice_debt_without_new_revenue(): void
    {
        [$r,$f] = $this->fixture();
        $student = $r->student('Current');
        $invoice = Invoice::create(['school_id' => $f['school']->id, 'student_id' => $student->id, 'number' => 'CURRENT-CLAIM', 'academic_year' => '2026',
            'term' => 'Term 1', 'issued_at' => '2026-01-05', 'total_amount' => 150, 'balance' => 150, 'status' => 'unpaid']);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'vote_code' => 'TUITION', 'description' => 'Tuition', 'quantity' => 1, 'unit_amount' => 150, 'line_total' => 150]);
        app(AccountingService::class)->postInvoice($invoice);
        $row = $r->claimRow($student, '2026', 'CURRENT-GOV', 80, 0, 'current_invoice', null, $invoice->number);
        $row['classification'] = 'current_receivable';
        $row['accounting_date'] = '2026-01-10';
        $batch = $r->batch('2026', 'CURRENT-CLAIMS', [$row]);
        $this->assertEquals(70, $invoice->fresh()->balance);
        $this->assertEquals(70, $invoice->items()->first()->balance);
        $this->assertSame(80.0, $batch->claims->first()->outstanding);
        $this->assertEquals(150, \App\Models\JournalEntry::where('account_id', $f['revenue']->id)->where('entry_type', 'credit')->sum('amount'));
        $report = app(ScholarshipBeamReportService::class)->build($f['school']->id, '2026-12-31');
        $this->assertTrue($report['student_control']['is_reconciled']);
        $this->assertTrue($report['controls']->every(fn ($row) => $row['difference'] === 0.0));
    }

    public function test_pending_claim_can_be_recognised_later_only_after_independent_check(): void
    {
        [$r,$f] = $this->fixture();
        $student = $r->student('LaterRecognition');
        $row = $r->claimRow($student, '2024', 'LATER', 75, 0, 'unknown');
        $row['classification'] = 'pending_claim';
        $row['recognised_receivable'] = 0;
        $row['outstanding_balance'] = 0;
        $batch = $r->batch('2024', 'LATER-BATCH', [$row]);
        $claim = $batch->claims()->firstOrFail();
        Auth::login($f['maker']);
        $service = app(\App\Services\Finance\SponsorClaimService::class);
        $service->prepareRecognition($claim, ['recognised_amount' => 75, 'received_before_cutover' => 0, 'opening_inclusion' => 'not_in_opening',
            'recognition_evidence' => 'Synthetic later verified obligation, absent from opening balances', 'supporting_reference' => 'SYNTHETIC-LATER', 'currency' => 'USD']);
        $this->assertSame(0, JournalBatch::count());
        $this->assertSame(0.0, $claim->fresh()->outstanding);
        try {
            $service->approveRecognition($claim, 'SELF');
            $this->fail('Self recognition must fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approval_reference', $e->errors());
        }
        Auth::login($f['checker']);
        $service->approveRecognition($claim, 'CHECKED-LATER');
        $service->approveRecognition($claim, 'RETRY');
        $this->assertSame(75.0, $claim->fresh()->outstanding);
        $this->assertSame(1, JournalBatch::count());
    }

    public function test_opening_link_cannot_exceed_the_existing_control_balance(): void
    {
        [$r,$f] = $this->fixture();
        $student = $r->student('Excess');
        $opening = app(AccountingService::class)->createJournalBatch(['school_id' => $f['school']->id, 'transaction_date' => '2026-01-01',
            'reference_number' => 'SMALL-OPENING', 'description' => 'Synthetic opening', 'source_type' => 'opening_balance', 'status' => 'posted', 'entries' => [
                ['account_id' => $f['sponsorAr']->id, 'entry_type' => 'debit', 'amount' => 100], ['account_id' => $f['equity']->id, 'entry_type' => 'credit', 'amount' => 100]]]);
        try {
            $r->batch('2023', 'TOO-LARGE', [$r->claimRow($student, '2023', 'TOO-LARGE-CLAIM', 150, 0, 'sponsor_control', $opening->id)]);
            $this->fail('Opening excess must fail');
        } catch (ValidationException $e) {
            $this->assertSame(1, JournalBatch::count());
            $this->assertSame(0, SponsorClaim::count());
        }
    }

    public function test_closed_period_blocks_settlement_and_posted_entries_are_immutable(): void
    {
        $r = new BeamRehearsal;
        $result = $r->run();
        $f = $r->fixture;
        $journal = JournalBatch::findOrFail(\App\Models\SponsorSettlement::findOrFail($result['settlement_id'])->journal_batch_id);
        try {
            $journal->entries->first()->update(['amount' => 999]);
            $this->fail('Posted entry mutation must fail');
        } catch (\LogicException $e) {
            $this->assertNotEquals(999, $journal->entries->first()->fresh()->amount);
        }
        \App\Models\AccountingPeriod::create(['school_id' => $f['school']->id, 'name' => 'Closed synthetic April', 'period_type' => 'month',
            'start_date' => '2026-04-01', 'end_date' => '2026-04-30', 'status' => 'closed']);
        Auth::login($f['maker']);
        $service = app(SponsorSettlementService::class);
        $pending = $service->prepare($f['sponsor'], ['reference' => 'CLOSED-PERIOD', 'received_on' => '2026-04-05', 'amount' => 10, 'bank_account_id' => $f['bank']->id,
            'cash_received' => 1, 'supporting_reference' => 'SYNTHETIC', 'allocations' => [['target_type' => 'claim', 'target_id' => $result['claim_id'], 'amount' => 10]]]);
        Auth::login($f['checker']);
        $before = JournalBatch::count();
        try {
            $service->approve($pending, 'CHECK');
            $this->fail('Closed period must fail');
        } catch (\App\Exceptions\AccountingPeriodClosedException $e) {
            $this->assertSame($before, JournalBatch::count());
            $this->assertSame('pending', $pending->fresh()->status);
        }
    }

    public function test_checked_opening_link_correction_allows_a_versioned_replacement_without_a_second_asset(): void
    {
        [$r,$f]=$this->fixture();$student=$r->student('Correction');
        $opening=app(AccountingService::class)->createJournalBatch(['school_id'=>$f['school']->id,'transaction_date'=>'2026-01-01',
            'reference_number'=>'REVIEWED-OPEN','description'=>'Synthetic opening','source_type'=>'opening_balance','status'=>'posted','entries'=>[
                ['account_id'=>$f['sponsorAr']->id,'entry_type'=>'debit','amount'=>100],['account_id'=>$f['equity']->id,'entry_type'=>'credit','amount'=>100]]]);
        $batch=$r->batch('2023','ORIGINAL-CLAIM-BATCH',[$r->claimRow($student,'2023','ORIGINAL-CLAIM',100,0,'sponsor_control',$opening->id)]);
        $claim=$batch->claims()->firstOrFail();
        Auth::login($f['maker']);$request=app(\App\Services\Finance\ScholarshipApprovalService::class)->request($claim,'claim_reverse','2026-06-01','Synthetic opening-link correction');
        Auth::login($f['checker']);app(\App\Services\Finance\ScholarshipApprovalService::class)->approve($request,'CHECKED-CORRECTION');
        $replacement=$r->batch('2023','REPLACEMENT-BATCH',[$r->claimRow($student,'2023','REPLACEMENT',100,0,'sponsor_control',$opening->id)])->claims()->firstOrFail();
        $this->assertSame($claim->id,$replacement->supersedes_claim_id);$this->assertSame('reversed',$claim->fresh()->status);
        $this->assertSame('2026-06-01',$replacement->accounting_date->toDateString());$this->assertSame(1,JournalBatch::count());
        foreach(['2026-05-01','2026-07-01'] as $date){$report=app(ScholarshipBeamReportService::class)->build($f['school']->id,$date);
            $this->assertTrue($report['controls']->every(fn($row)=>$row['difference']===0.0));}
    }

    public function test_actual_csv_upload_stays_in_staging_and_preview_requires_explicit_review(): void
    {
        [$r,$f] = $this->fixture();
        $student = $r->student('Csv');
        $row = $r->claimRow($student, '2023', 'CSV-CLAIM', 100, 0, 'unknown');
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, BeamMigrationService::HEADERS);
        fputcsv($stream, array_map(fn ($key) => is_array($row[$key] ?? null) ? json_encode($row[$key]) : ($row[$key] ?? ''), BeamMigrationService::HEADERS));
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $this->post(route('admin.funding.import'), ['funding_sponsor_id' => $f['sponsor']->id, 'academic_year' => '2023', 'reference' => 'CSV-STAGE',
            'file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('beam.csv', $csv)])->assertSessionHasNoErrors()->assertRedirect();
        $batch = \App\Models\SponsorClaimBatch::where('reference','CSV-STAGE')->firstOrFail();
        $this->get(route('admin.funding.batch',$batch))->assertOk()->assertSee('CSV-CLAIM');
        $this->assertSame('pending',$batch->migrationBatch->stagingRows->first()->status);
        $this->assertSame(0,JournalBatch::count());
        $this->assertSame(0,SponsorClaim::count());
    }
}
