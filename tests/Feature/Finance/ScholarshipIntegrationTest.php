<?php

namespace Tests\Feature\Finance;

use App\Imports\EnrollmentsImport;
use App\Models\{Account, BankAccount, Cashbook, FeeStructure, Invoice, InvoiceItem, JournalBatch, JournalEntry, Payment, Receipt, School, Student, StudentScholarship, User};
use App\Services\AccountingService;
use App\Services\Finance\{FinancialAccountResolver, FinancialReconciliationService, FinanceReportingService, HandeFeeVotesService, InvoiceFeeSelectionService, ScholarshipService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScholarshipIntegrationTest extends TestCase
{
    use RefreshDatabase;
    private School $school;
    private Student $student;
    private ScholarshipService $service;
    private Account $ar;
    private Account $revenue;
    private Account $remission;
    private Account $sponsorAr;

    protected function setUp(): void
    {
        parent::setUp();
        (new \Database\Seeders\RoleSeeder)->run();
        (new \Database\Seeders\RolePermissionSeeder)->run();
        $this->school = School::create(['name' => 'Scholarship Test School', 'code' => 'SCH']);
        $user = User::factory()->create(['school_id' => $this->school->id]);
        $user->assignRole('super-admin');
        $this->actingAs($user);
        $this->student = Student::create(['school_id' => $this->school->id, 'first_name' => 'Synthetic', 'last_name' => 'Scholar', 'gender' => 'male', 'status' => 'active', 'is_boarding' => true, 'grade' => 'Form 1']);
        $this->ar = $this->account('1201', 'asset', 'receivable');
        $this->revenue = $this->account('5100', 'revenue', 'tuition_revenue');
        $this->remission = $this->account('SCH-EXP', 'expense', 'other_expense');
        $this->sponsorAr = $this->account('SCH-AR', 'asset', 'receivable');
        $resolver = app(FinancialAccountResolver::class);
        $resolver->setMapping($this->school, 'control_account', 'ACCOUNTS_RECEIVABLE', $this->ar);
        $resolver->setMapping($this->school, 'scholarship', 'SCHOOL_REMISSION', $this->remission);
        $resolver->setMapping($this->school, 'scholarship', 'SPONSOR_RECEIVABLE', $this->sponsorAr);
        foreach (array_keys(HandeFeeVotesService::getStandardVotes()) as $vote) {
            $resolver->setMapping($this->school, 'fee_vote', $vote, $this->revenue);
        }
        $this->service = app(ScholarshipService::class);
    }

    private function account(string $code, string $type, string $category): Account
    {
        return Account::create(['school_id' => $this->school->id, 'code' => $code, 'name' => $code,
            'type' => $type, 'category' => $category, 'is_active' => true, 'is_postable' => true]);
    }

    private function award(array $overrides = [], bool $approve = true): StudentScholarship
    {
        $award = $this->service->saveAward($this->student, array_merge([
            'reference' => 'TEST-AWARD', 'name' => 'Synthetic Scholarship', 'coverage_type' => 'partial',
            'coverage_basis' => 'percentage', 'coverage_value' => 50, 'covered_votes' => ['TUITION'],
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'accounting_treatment' => 'school_remission',
            'supporting_reference' => 'SYNTHETIC-DOC',
            'recognition_evidence'=>'Synthetic approved enforceable undertaking',
        ], $overrides));
        if ($approve) { $this->differentChecker(); }
        return $approve ? $this->service->approve($award, 'MANAGEMENT-TEST-APPROVAL') : $award;
    }

    private function differentChecker(): void
    {
        $checker=User::factory()->create(['school_id'=>$this->school->id]); $checker->assignRole('super-admin'); $this->actingAs($checker);
    }

    private function invoice(): Invoice
    {
        $invoice = Invoice::create(['school_id' => $this->school->id, 'student_id' => $this->student->id,
            'number' => 'SCH-INV-' . (Invoice::count() + 1), 'issued_at' => '2026-02-01', 'academic_year' => '2026',
            'term' => 'Term 1', 'status' => 'unpaid', 'total_amount' => 150, 'balance' => 150]);
        foreach (['TUITION' => 100, 'SCIENCE_LEVY' => 50] as $vote => $amount) {
            InvoiceItem::create(['invoice_id' => $invoice->id, 'vote_code' => $vote, 'description' => $vote,
                'quantity' => 1, 'unit_amount' => $amount, 'line_total' => $amount]);
        }
        app(AccountingService::class)->postInvoice($invoice);
        return $invoice;
    }

    public static function categories(): array
    {
        return [
            'regular boarding' => ['boarding', null, true, 'regular'],
            'regular day' => ['day', null, false, 'regular'],
            'scholarship boarding' => ['scholarship', 'boarding', true, 'scholarship'],
            'scholarship day' => ['scholarship', 'day', false, 'scholarship'],
        ];
    }

    #[DataProvider('categories')]
    public function test_registration_keeps_residency_separate(string $category, ?string $residency, bool $boarding, string $funding): void
    {
        $response = $this->post(route('admin.students.store'), ['first_name' => 'New', 'last_name' => 'Synthetic',
            'student_category' => $category, 'scholarship_residency' => $residency]);
        $response->assertSessionHasNoErrors()->assertRedirect();
        $student = Student::latest('id')->first();
        $this->assertSame($boarding, $student->is_boarding);
        $this->assertSame($funding, $student->funding_status);
        $this->assertSame($boarding, $student->isBoardingOn());
        $this->get(route('admin.students.edit', $student))->assertOk()->assertSee('Scholarship');
    }

    public function test_full_selected_vote_coverage_preserves_uncovered_debt_and_reconciles(): void
    {
        $award = $this->award(['coverage_type' => 'full', 'coverage_value' => 100]);
        $invoice = $this->invoice();
        $application = $this->service->apply($award, $invoice, '2026-02-01');
        $this->assertEquals(100, $application->amount);
        $this->assertEquals(50, $invoice->fresh()->balance);
        $this->assertEquals(0, $invoice->items->first()->balance);
        $this->assertEquals(50, $invoice->items->last()->balance);
        $this->assertTrue(app(FinancialReconciliationService::class)->reconcileDebtorsControl($this->school)['is_reconciled']);
        $this->assertEquals(50, app(FinanceReportingService::class)->getStudentStatement($this->school, $this->student)['closing_balance']);
        $this->assertEquals(0, Payment::count() + Receipt::count() + Cashbook::count());
        $this->assertEquals(150, JournalEntry::where('account_id', $this->revenue->id)->where('entry_type', 'credit')->sum('amount'));
        $this->assertSame($application->id, $this->service->apply($award, $invoice, '2026-02-01')->id);
        $this->assertEquals(1, JournalBatch::where('source_type', 'scholarship')->count());
        $this->post(route('admin.students.statement.show', $this->student), ['academic_year' => '2026', 'term' => 'Term 1'])
            ->assertOk()->assertSee('Scholarship coverage')->assertViewHas('closingBalance', 50);
        $this->get(route('admin.invoices.show', $invoice))->assertOk()->assertSee('Scholarship coverage (non-cash)');
        $this->get(route('admin.invoices.print', $invoice))->assertOk()->assertSee('Scholarship coverage (non-cash)');
    }

    public function test_partial_percentage_and_amount_cap_across_invoices(): void
    {
        $award = $this->award(['coverage_basis' => 'amount', 'coverage_value' => 125]);
        $first = $this->invoice();
        $second = $this->invoice();
        $this->assertEquals(100, $this->service->apply($award, $first, '2026-02-01')->amount);
        $this->assertEquals(25, $this->service->apply($award, $second, '2026-02-01')->amount);
        $this->assertEquals(125, $second->fresh()->balance);
    }

    public function test_partial_percentage_does_not_cover_other_votes(): void
    {
        $award = $this->award();
        $invoice = $this->invoice();
        $this->service->apply($award, $invoice, '2026-02-01');
        $this->assertEquals(100, $invoice->fresh()->balance);
        $this->assertEquals(50, $invoice->items->first()->balance);
        $this->assertEquals(50, $invoice->items->last()->balance);
    }

    public function test_full_all_vote_coverage_can_settle_invoice_without_cash(): void
    {
        $award = $this->award(['coverage_type' => 'full', 'coverage_value' => 100, 'covered_votes' => ['TUITION','SCIENCE_LEVY']]);
        $invoice = $this->invoice();
        $this->service->apply($award, $invoice, '2026-02-01');
        $this->assertEquals(0, $invoice->fresh()->balance);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertEquals(0, Payment::count() + Cashbook::count());
    }

    public function test_sponsor_transfer_is_receivable_not_cash_or_revenue(): void
    {
        $award = $this->award(['accounting_treatment' => 'sponsor_receivable', 'sponsor' => 'Synthetic Foundation']);
        $invoice = $this->invoice();
        $application = $this->service->apply($award, $invoice, '2026-02-01');
        $this->assertEquals(50, $application->journalBatch->entries->where('account_id', $this->sponsorAr->id)->where('entry_type', 'debit')->sum('amount'));
        $this->assertEquals(0, Payment::count() + Receipt::count() + Cashbook::count());
        $this->assertEquals(150, JournalEntry::where('account_id', $this->revenue->id)->where('entry_type','credit')->sum('amount'));
    }

    public function test_reversal_is_dated_balanced_idempotent_and_preserves_history(): void
    {
        $award = $this->award(); $invoice = $this->invoice();
        $application = $this->service->apply($award, $invoice, '2026-02-01');
        $snapshot = $application->award_snapshot;
        $this->service->close($award, 'Future award terms changing');
        $this->assertEquals(100, $invoice->fresh()->balance);
        $request=app(\App\Services\Finance\ScholarshipApprovalService::class)->request($application,'scholarship_reverse','2026-03-01','Synthetic correction');
        $this->differentChecker();
        app(\App\Services\Finance\ScholarshipApprovalService::class)->approve($request,'INDEPENDENT-TEST-CHECK');
        $reversed=$application->fresh();
        $this->service->reverse($reversed, '2026-03-01', 'Retry');
        $this->assertEquals(150, $invoice->fresh()->balance);
        $this->assertSame($snapshot, $application->fresh()->award_snapshot);
        $this->assertEquals(1, JournalBatch::where('source_type', 'scholarship_reversal')->count());
        $this->assertTrue(app(FinancialReconciliationService::class)->reconcileGeneralLedgerBalance($this->school)['is_balanced']);
        $this->assertEquals(100, app(FinanceReportingService::class)->getStudentStatement($this->school, $this->student, ['end_date' => '2026-02-28'])['closing_balance']);
        $this->assertEquals(150, app(FinanceReportingService::class)->getStudentStatement($this->school, $this->student)['closing_balance']);
    }

    public function test_overlapping_active_vote_allocations_are_rejected(): void
    {
        $this->award();
        $other = $this->award(['reference' => 'OVERLAP','coverage_value'=>60], false);
        $this->differentChecker();
        $this->expectException(ValidationException::class);
        $this->service->approve($other, 'Approval');
    }

    public function test_approved_edits_do_not_rewrite_posted_invoices(): void
    {
        $award = $this->award(); $invoice = $this->invoice();
        $this->service->apply($award, $invoice, '2026-02-01');
        $this->put(route('admin.students.scholarships.update', [$this->student,$award]), $award->toArray())
            ->assertSessionHasErrors();
        $this->assertEquals(150, $invoice->fresh()->total_amount);
        $this->assertEquals(100, $invoice->fresh()->balance);
        $this->delete(route('admin.invoices.destroy', $invoice))->assertSessionHasErrors('invoice');
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_policy_must_be_explicit_and_configured_before_approval(): void
    {
        $award = $this->award(['accounting_treatment' => 'unconfigured'], false);
        $this->differentChecker();
        $this->expectException(ValidationException::class);
        $this->service->approve($award, 'Approval');
    }

    public function test_existing_and_scholarship_imports_are_compatible_and_tenant_scoped(): void
    {
        $import = new EnrollmentsImport($this->school->id);
        $row = ['student_name' => 'Import Synthetic', 'email' => 'synthetic@example.test', 'class_name' => 'A', 'grade' => 'Form 1',
            'academic_year' => '2026', 'term' => 'Term 1', 'is_boarding' => 'yes'];
        $enrollment = $import->model($row); $enrollment->save();
        $student = $enrollment->student;
        $this->assertSame('regular', $student->funding_status);
        $this->assertTrue($student->isBoardingOn());
        $row['student_category'] = 'scholarship'; $row['funding_status'] = 'scholarship'; $row['residency'] = 'boarding';
        $import->model($row)->save();
        $this->assertSame('scholarship', $student->fresh()->funding_status);
        unset($row['student_category'], $row['funding_status'], $row['residency']);
        $import->model($row)->save();
        $this->assertSame('scholarship', $student->fresh()->funding_status);
    }

    public function test_scholarship_fee_selection_uses_normal_boarding_and_day_packages(): void
    {
        HandeFeeVotesService::syncSchoolVotes($this->school, '2026', 'Term 1');
        $fees = FeeStructure::where('school_id', $this->school->id)->get();
        foreach ([true => 470, false => 135] as $boarding => $expected) {
            $selection = app(InvoiceFeeSelectionService::class)->select($fees, 'Form 1', (bool)$boarding, false, false);
            $this->assertEquals($expected, $selection->sum('amount'));
        }
    }

    public function test_scholarship_management_enforces_tenant_and_roles(): void
    {
        $foreignSchool = School::create(['name' => 'Other School','code' => 'OTHER-SCH']);
        $foreignStudent = Student::create(['school_id' => $foreignSchool->id,'first_name' => 'Other','last_name' => 'Synthetic']);
        $this->get(route('admin.students.scholarships', $foreignStudent))->assertNotFound();
        $clerk = User::factory()->create(['school_id' => $this->school->id]); $clerk->assignRole('accounts-clerk');
        $this->actingAs($clerk)->get(route('admin.students.scholarships', $this->student))->assertForbidden();
    }

    public function test_scholarship_page_and_filter_render(): void
    {
        $this->award();
        $this->get(route('admin.students.scholarships', $this->student))->assertOk()->assertSee('Synthetic Scholarship');
        $this->get(route('admin.students.index', ['category' => 'scholarship', 'boarding' => 'yes']))->assertOk()->assertSee('Synthetic');
    }

    public function test_actual_sponsor_receipt_settles_sponsor_only_and_can_be_reversed(): void
    {
        $award = $this->award(['accounting_treatment' => 'sponsor_receivable', 'sponsor' => 'Synthetic Foundation']);
        $invoice = $this->invoice();
        $application = $this->service->apply($award, $invoice, '2026-02-01');
        $bank = $this->account('SCH-BANK', 'asset', 'bank');
        BankAccount::create(['school_id' => $this->school->id, 'account_id' => $bank->id, 'account_name' => 'Synthetic Bank',
            'account_number' => 'SYNTHETIC-ONLY', 'bank_name' => 'Synthetic Bank', 'opening_balance' => 0, 'is_active' => true]);
        $data = ['reference' => 'SPONSOR-REAL-TEST', 'received_on' => '2026-02-05', 'amount' => 30, 'account_id' => $bank->id, 'cash_received' => 1];
        $receipt = $this->service->receiveSponsor($application, $data);
        $this->assertEquals(20, $application->fresh()->sponsor_balance);
        $this->assertEquals(100, $invoice->fresh()->balance);
        $this->assertEquals(1, Cashbook::count());
        $this->assertSame($receipt->id, $this->service->receiveSponsor($application, $data)->id);
        $this->get(route('admin.students.scholarships', $this->student))->assertOk()->assertSee('SPONSOR-REAL-TEST');
        $this->assertTrue(app(FinancialReconciliationService::class)->reconcileBankAndCashbook($this->school)[0]['is_reconciled']);
        $this->post(route('admin.students.scholarships.sponsor-receipt.reverse', [$this->student, $receipt]), [
            'accounting_date' => '2026-02-06', 'reason' => 'Synthetic correction',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $request=\App\Models\ScholarshipApproval::latest('id')->first();
        $this->differentChecker();
        $this->post(route('admin.funding.correction.approve',$request),['approval_reference'=>'INDEPENDENT-CHECK'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(50, $application->fresh()->sponsor_balance);
        $this->assertEquals(2, Cashbook::count());
        $this->assertTrue(app(FinancialReconciliationService::class)->reconcileGeneralLedgerBalance($this->school)['is_balanced']);
    }

    public function test_sponsor_receipt_requires_actual_cash_confirmation(): void
    {
        $award = $this->award(['accounting_treatment' => 'sponsor_receivable', 'sponsor' => 'Synthetic Foundation']);
        $application = $this->service->apply($award, $this->invoice(), '2026-02-01');
        $this->expectException(ValidationException::class);
        $this->service->receiveSponsor($application, ['reference' => 'NO-CASH', 'received_on' => '2026-02-05', 'amount' => 30, 'account_id' => $this->ar->id]);
    }

    public function test_scholarship_boarder_can_use_hostel_and_day_scholar_cannot(): void
    {
        $this->student->update(['funding_status' => 'scholarship']);
        $dorm = \App\Models\Dormitory::create(['school_id' => $this->school->id, 'name' => 'Synthetic Hostel', 'gender' => 'male', 'capacity' => 2, 'is_active' => true]);
        $bed = \App\Models\Bed::create(['dormitory_id' => $dorm->id, 'bed_number' => 'SCH-1', 'is_available' => true]);
        $service = app(\App\Services\Residency\BedAllocationService::class);
        $assignment = $service->allocateBed($this->student, $bed, '2026', 'Term 1');
        $this->assertSame($this->student->id, $assignment->student_id);
        $day = Student::create(['school_id' => $this->school->id, 'first_name' => 'Synthetic', 'last_name' => 'Day',
            'gender' => 'male', 'is_boarding' => false, 'funding_status' => 'scholarship']);
        $this->expectException(\InvalidArgumentException::class);
        $service->allocateBed($day, $bed, '2026', 'Term 1');
    }

    public function test_bulk_import_award_details_remain_pending_and_do_not_post(): void
    {
        $row = ['student_name' => 'Import Award', 'email' => 'award@example.test', 'class_name' => 'A', 'grade' => 'Form 1',
            'academic_year' => '2026', 'term' => 'Term 1', 'student_category' => 'scholarship', 'residency' => 'day',
            'scholarship_reference' => 'IMPORT-SCH', 'scholarship_name' => 'Imported Award',
            'scholarship_coverage_value' => 40, 'scholarship_covered_votes' => 'TUITION|SCIENCE_LEVY',
            'scholarship_starts_on' => '2026-01-01', 'scholarship_ends_on' => '2026-12-31'];
        $enrollment = (new EnrollmentsImport($this->school->id))->model($row); $enrollment->save();
        $this->assertFalse($enrollment->student->isBoardingOn());
        $this->assertSame('pending', $enrollment->student->scholarships->first()->status);
        $this->assertEquals(0, JournalBatch::count());
    }

    public function test_new_invoice_route_applies_approved_coverage_automatically(): void
    {
        $this->award();
        $this->post(route('admin.invoices.store'), ['student_id' => $this->student->id, 'academic_year' => '2026', 'term' => 'Term 1',
            'issued_at' => '2026-02-01', 'items' => [
                ['vote_code' => 'TUITION','description' => 'Tuition','quantity' => 1,'unit_amount' => 100],
                ['vote_code' => 'SCIENCE_LEVY','description' => 'Science','quantity' => 1,'unit_amount' => 50],
            ]])->assertSessionHasNoErrors()->assertRedirect();
        $invoice = $this->student->invoices->first();
        $this->assertEquals(150, $invoice->total_amount);
        $this->assertEquals(100, $invoice->balance);
        $this->assertEquals(1, $invoice->scholarshipApplications->count());
    }

    public function test_scholarship_payment_allocations_only_use_uncovered_votes(): void
    {
        $award = $this->award(['coverage_type' => 'full', 'coverage_value' => 100]);
        $invoice = $this->invoice();
        $this->service->apply($award, $invoice, '2026-02-01');
        $allocations = \Illuminate\Support\Facades\DB::transaction(fn () => $this->service->paymentAllocations($invoice->fresh(), [], 50));
        $this->assertSame([$invoice->items->last()->id => 50], $allocations);
        $this->expectException(ValidationException::class);
        $this->service->paymentAllocations($invoice->fresh(), [$invoice->items->first()->id => 50], 50);
    }

    public function test_accountant_cannot_approve_or_change_management_mappings(): void
    {
        $award = $this->award([], false);
        $accountant = User::factory()->create(['school_id' => $this->school->id]); $accountant->assignRole('accountant');
        $this->actingAs($accountant)->post(route('admin.students.scholarships.approve', [$this->student,$award]), ['approval_reference' => 'Not authorised'])->assertForbidden();
        $this->post(route('admin.students.scholarships.mapping', $this->student), ['source_key' => 'SCHOOL_REMISSION','account_id' => $this->remission->id,'approval_reference' => 'Not authorised'])->assertForbidden();
        $this->assertSame('pending', $award->fresh()->status);
    }

    public function test_received_student_payment_after_scholarship_preserves_invoice_journal(): void
    {
        $award = $this->award(['coverage_type' => 'full','coverage_value' => 100]);
        $invoice = $this->invoice();
        $original = JournalBatch::where('source_type','invoice')->where('source_id',$invoice->id)->first();
        $entryIds = $original->entries->pluck('id')->all();
        $this->service->apply($award, $invoice, '2026-02-01');
        $this->assertSame($entryIds, $original->fresh()->entries->pluck('id')->all());
        $bank = $this->account('CASH-SCH','asset','cash');
        app(FinancialAccountResolver::class)->setMapping($this->school,'payment_method','cash',$bank);
        $this->post(route('admin.payments.store',$invoice), ['amount' => 50,'paid_at' => '2026-02-10','method' => 'cash',
            'account_id' => $bank->id,'reference' => 'SCH-CASH-TEST',
            'allocations' => [$invoice->items->last()->id => 50],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(0, $invoice->fresh()->balance);
        $this->assertSame($entryIds, $original->fresh()->entries->pluck('id')->all());
        $this->assertEquals(150, JournalEntry::where('account_id',$this->revenue->id)->where('entry_type','credit')->sum('amount'));
        $this->assertTrue(app(FinancialReconciliationService::class)->reconcileDebtorsControl($this->school)['is_reconciled']);
    }

    public function test_reporting_exports_coverage_without_changing_existing_students(): void
    {
        $existing = $this->student->getAttributes();
        $other = Student::create(['school_id' => $this->school->id,'first_name' => 'Other','last_name' => 'Regular','is_boarding' => false]);
        $this->award();
        $report = app(\App\Services\Academic\AcademicReportingService::class)->getStudentRegister($this->school, ['funding_status' => 'scholarship','is_boarding' => 'yes'], 0);
        $this->assertCount(1, $report['rows']);
        $row = $report['rows']->first();
        $this->assertSame('Scholarship — Boarding', $row['student_category']);
        $this->assertStringContainsString('TEST-AWARD', $row['scholarship_details']);
        $export = new \App\Exports\StudentRegisterExport($report);
        $this->assertCount(count($export->headings()), $export->array()[0]);
        $this->assertSame('regular', $other->fresh()->funding_status);
        $this->assertSame($existing['first_name'], $this->student->fresh()->first_name);
    }

    public function test_legacy_import_validation_failure_rolls_back_student_changes(): void
    {
        $row = ['student_name' => 'Rollback Synthetic', 'email' => 'rollback@example.test', 'class_name' => 'A', 'grade' => 'Form 1',
            'academic_year' => '2026', 'term' => 'Term 1', 'student_category' => 'scholarship', 'residency' => 'day',
            'scholarship_reference' => 'BAD-AWARD', 'scholarship_name' => 'Bad Award', 'scholarship_coverage_value' => 150,
            'scholarship_covered_votes' => 'TUITION', 'scholarship_starts_on' => '2026-01-01','scholarship_ends_on' => '2026-12-31'];
        try { (new EnrollmentsImport($this->school->id))->model($row); $this->fail('Invalid coverage must be rejected.'); }
        catch (ValidationException $e) { $this->assertDatabaseMissing('students', ['email' => 'rollback@example.test']); }
    }
}
