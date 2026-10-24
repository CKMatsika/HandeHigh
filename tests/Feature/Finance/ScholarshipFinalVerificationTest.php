<?php

namespace Tests\Feature\Finance;

use App\Models\{Account, Bed, Cashbook, Dormitory, FeeStructure, Invoice, JournalBatch, JournalEntry, Payment, Receipt, School, Student, StudentScholarship, User};
use App\Services\Finance\{FinancialAccountResolver, FinancialReconciliationService, HandeFeeVotesService, InvoiceFeeSelectionService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class ScholarshipFinalVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): School
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (new \Database\Seeders\RoleSeeder)->run();
        (new \Database\Seeders\RolePermissionSeeder)->run();
        $school = School::create(['name' => 'Synthetic Functional Verification', 'code' => 'SCH-VERIFY']);
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole('super-admin');
        $this->actingAs($user);
        return $school;
    }

    public function test_five_fee_scenarios_through_student_award_and_invoice_routes(): void
    {
        $school = $this->fixture();
        HandeFeeVotesService::syncSchoolVotes($school, '2026', 'Term 1');
        $account = fn ($code, $type, $category) => Account::create(['school_id' => $school->id, 'code' => $code,
            'name' => $code, 'type' => $type, 'category' => $category, 'is_active' => true, 'is_postable' => true]);
        $ar = $account('1201', 'asset', 'receivable');
        $remission = $account('SCH-EXP', 'expense', 'other_expense');
        $sponsorAr = $account('SCH-AR', 'asset', 'receivable');
        $resolver = app(FinancialAccountResolver::class);
        $resolver->setMapping($school, 'control_account', 'ACCOUNTS_RECEIVABLE', $ar);
        $resolver->setMapping($school, 'scholarship', 'SCHOOL_REMISSION', $remission);
        $resolver->setMapping($school, 'scholarship', 'SPONSOR_RECEIVABLE', $sponsorAr);
        foreach (['TUITION'=>'5100','BUILDING_FUND'=>'5120','SPORTS'=>'5130','PRACTICALS_ICT'=>'5140',
            'GPF_ADMIN'=>'5170','SCIENCE_LEVY'=>'5160','BOARDING_FEES'=>'5200','FOOD'=>'5803'] as $vote => $code) {
            $resolver->setMapping($school, 'fee_vote', $vote, $account($code, 'revenue', 'tuition_revenue'));
        }

        $create = $this->get(route('admin.students.create'))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($create->getContent());
        $xpath = new \DOMXPath($dom);
        $options = [];
        foreach ($xpath->query('//select[@name="student_category"]/option') as $option) { $options[$option->getAttribute('value')] = trim($option->textContent); }
        $this->assertSame(['boarding'=>'Boarding','day'=>'Day','scholarship'=>'Scholarship'], $options);
        $this->assertSame(3, $xpath->query('//select[@name="scholarship_residency"]/option')->length);
        $handler = $xpath->query('//select[@name="student_category"]')->item(0)->getAttribute('onchange');
        $evidence = ['environment'=>'testing / SQLite :memory:', 'category_options'=>$options, 'category_handler'=>$handler, 'scenarios'=>[]];
        $students = [];
        $fees = FeeStructure::where('school_id', $school->id)->get();
        $scenarios = [
            ['regular_boarding','boarding',null,470,0,470],
            ['regular_day','day',null,135,0,135],
            ['full_boarding','scholarship','boarding',470,470,0],
            ['amount_boarding','scholarship','boarding',470,300,170],
            ['selected_day','scholarship','day',135,20,115],
        ];
        foreach ($scenarios as [$label,$category,$residency,$expectedGross,$expectedCoverage,$expectedBalance]) {
            $data = ['first_name'=>'Synthetic','last_name'=>$label,'gender'=>'male','grade'=>'Form 1',
                'student_category'=>$category,'scholarship_residency'=>$residency];
            $this->post(route('admin.students.store'),$data)->assertSessionHasNoErrors()->assertRedirect();
            $student = Student::where('school_id',$school->id)->where('last_name',$label)->firstOrFail();
            $students[$label] = $student;
            $this->get(route('admin.students.edit',$student))->assertOk()->assertSee('Scholarship');
            $this->put(route('admin.students.update',$student),$data)->assertSessionHasNoErrors()->assertRedirect();
            $boarding = $student->fresh()->isBoardingOn('2026','Term 1');
            $selected = app(InvoiceFeeSelectionService::class)->select($fees,'Form 1',$boarding,false,false);
            $this->assertEquals($expectedGross, $selected->sum('amount')); // Expectations, not application calculations.
            $items = $selected->map(fn ($fee) => ['fee_structure_id'=>$fee->id,'vote_code'=>$fee->vote_code,
                'description'=>$fee->label,'category'=>$fee->category,'quantity'=>1,'unit_amount'=>(float)$fee->amount])->all();
            $this->post(route('admin.invoices.store'),['student_id'=>$student->id,'academic_year'=>'2026','term'=>'Term 1',
                'issued_at'=>'2026-01-10','due_date'=>'2026-01-24','items'=>$items])->assertSessionHasNoErrors()->assertRedirect();
            $invoice = $student->invoices()->firstOrFail();
            $this->assertEquals($expectedGross,$invoice->balance);
            $original = JournalBatch::where('school_id',$school->id)->where('source_type','invoice')->where('source_id',$invoice->id)->firstOrFail();
            $originalEntries = $original->entries->pluck('id')->all();
            $award = null;
            if ($category === 'scholarship') {
                $detail = $this->get(route('admin.students.scholarships',$student))->assertOk();
                foreach (['reference','name','sponsor','coverage_type','coverage_basis','coverage_value','covered_votes[]','starts_on','ends_on','accounting_treatment','supporting_reference'] as $field) {
                    $detail->assertSee('name="'.$field.'"',false);
                }
                $votes = $label === 'selected_day' ? ['TUITION','SCIENCE_LEVY'] : $selected->pluck('vote_code')->all();
                $awardData = ['reference'=>'VERIFY-'.$label,'name'=>'Synthetic '.$label,'sponsor'=>$label === 'selected_day' ? 'Synthetic Foundation' : null,
                    'coverage_type'=>$label === 'full_boarding' ? 'full' : 'partial','coverage_basis'=>$label === 'amount_boarding' ? 'amount' : 'percentage',
                    'coverage_value'=>$label === 'full_boarding' ? 100 : ($label === 'amount_boarding' ? 300 : 50),
                    'covered_votes'=>$votes,'starts_on'=>'2026-01-01','ends_on'=>'2026-12-31',
                    'accounting_treatment'=>$label === 'selected_day' ? 'sponsor_receivable' : 'school_remission','supporting_reference'=>'SYNTHETIC-VERIFICATION',
                    'recognition_evidence'=>'Synthetic approved enforceable sponsor obligation'];
                $this->post(route('admin.students.scholarships.store',$student),$awardData)->assertSessionHasNoErrors()->assertRedirect();
                $award = $student->scholarships()->firstOrFail();
                $this->assertSame('pending',$award->status);
                $this->assertEquals($expectedGross,$invoice->fresh()->balance);
                $checker=User::factory()->create(['school_id'=>$school->id]); $checker->assignRole('super-admin'); $this->actingAs($checker);
                $this->post(route('admin.students.scholarships.approve',[$student,$award]),['approval_reference'=>'SYNTHETIC-POLICY-ONLY'])->assertSessionHasNoErrors()->assertRedirect();
                $this->assertSame('approved',$award->fresh()->status);
                $this->assertEquals($expectedGross,$invoice->fresh()->balance);
                $this->post(route('admin.students.scholarships.apply',[$student,$award]),['invoice_id'=>$invoice->id,'accounting_date'=>'2026-01-10'])->assertSessionHasNoErrors()->assertRedirect();
                $this->post(route('admin.students.scholarships.apply',[$student,$award]),['invoice_id'=>$invoice->id,'accounting_date'=>'2026-01-10'])->assertSessionHasNoErrors()->assertRedirect();
                $this->assertEquals($expectedCoverage,$invoice->scholarshipApplications()->sum('amount'));
                $this->assertSame($originalEntries,$original->fresh()->entries->pluck('id')->all());
            }
            $this->assertEquals($expectedBalance,$invoice->fresh()->balance);
            $this->get(route('admin.students.show',$student))->assertOk()->assertSee($student->fresh()->category_label);
            $this->get(route('admin.invoices.show',$invoice))->assertOk();
            $this->post(route('admin.students.statement.show',$student),['academic_year'=>'2026','term'=>'Term 1'])->assertOk()->assertViewHas('closingBalance',(float)$expectedBalance);
            $evidence['scenarios'][$label] = ['student_id'=>$student->id,'category'=>$student->fresh()->category_label,'invoice'=>$invoice->number,
                'gross'=>(float)$invoice->total_amount,'coverage'=>(float)$invoice->scholarshipApplications()->sum('amount'),
                'student_balance'=>(float)$invoice->fresh()->balance,'award_status'=>$award?->fresh()->status,
                'sponsor_receivable'=>(float)$invoice->scholarshipApplications->sum('sponsor_balance'),
                'votes'=>$invoice->items->map(fn ($item) => ['vote'=>$item->effective_vote_code,'assessed'=>(float)$item->line_total,
                    'covered'=>$item->scholarship_amount,'balance'=>$item->balance])->all()];
        }
        $listing = $this->get(route('admin.students.index',['category'=>'scholarship','boarding'=>'yes']))->assertOk();
        $this->assertEqualsCanonicalizing([$students['full_boarding']->id,$students['amount_boarding']->id],$listing->viewData('students')->pluck('id')->all());
        $dorm = Dormitory::create(['school_id'=>$school->id,'name'=>'Synthetic Verification Hostel','gender'=>'male','capacity'=>2,'is_active'=>true]);
        foreach (['full_boarding','selected_day'] as $label) {
            $bed = Bed::create(['dormitory_id'=>$dorm->id,'bed_number'=>$label,'is_available'=>true]);
            $response = $this->post(route('admin.students.assign-bed',$students[$label]),['bed_id'=>$bed->id,'academic_year'=>'2026','term'=>'Term 1']);
            if ($label === 'full_boarding') { $response->assertSessionHasNoErrors(); $this->assertTrue($students[$label]->currentBedAssignment()->exists()); }
            else { $response->assertSessionHasErrors('bed_id'); $this->assertFalse($students[$label]->currentBedAssignment()->exists()); }
        }
        $evidence['hostel'] = ['scholarship_boarder'=>'allocated','scholarship_day'=>'rejected: Day Scholar'];
        $reconciliation = app(FinancialReconciliationService::class);
        $evidence['trial_balance'] = $reconciliation->reconcileGeneralLedgerBalance($school);
        $evidence['student_debtors'] = $reconciliation->reconcileDebtorsControl($school);
        $this->assertTrue($evidence['trial_balance']['is_balanced']);
        $this->assertTrue($evidence['student_debtors']['is_reconciled']);
        $this->assertEquals(890,$evidence['student_debtors']['gl_ar_balance']);
        $this->assertEquals(0,Payment::count()+Receipt::count()+Cashbook::count());
        $this->assertEquals(3,JournalBatch::where('source_type','scholarship')->count());
        $this->assertEquals(1680,JournalEntry::whereHas('account',fn ($q) => $q->where('type','revenue'))->where('entry_type','credit')->sum('amount'));
        $evidence['cash_records'] = ['payments'=>Payment::count(),'receipts'=>Receipt::count(),'cashbook'=>Cashbook::count()];
        $evidence['journals'] = JournalBatch::with('entries.account')->where('school_id',$school->id)->orderBy('id')->get()->map(fn ($batch) => [
            'id'=>$batch->id,'reference'=>$batch->reference_number,'source'=>$batch->source_type,'date'=>$batch->transaction_date->toDateString(),
            'entries'=>$batch->entries->map(fn ($entry) => ['account'=>$entry->account->code,'direction'=>$entry->entry_type,'amount'=>(float)$entry->amount])->all(),
        ])->all();
        file_put_contents(storage_path('logs/scholarship-functional-evidence.json'),json_encode($evidence,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    public function test_additive_migration_preserves_955_synthetic_legacy_students(): void
    {
        $school = $this->fixture();
        // Strictly SQLite :memory:, asserted by fixture; reconstruct the pre-feature schema only in this disposable test.
        Schema::drop('scholarship_sponsor_receipts'); Schema::drop('scholarship_applications'); Schema::drop('student_scholarships');
        Schema::table('students',function ($table) { $table->dropIndex(['funding_status']); $table->dropColumn('funding_status'); });
        $rows = [];
        for ($i=1;$i<=955;$i++) { $rows[] = ['school_id'=>$school->id,'first_name'=>'Synthetic','last_name'=>'Legacy-'.$i,
            'admission_number'=>'VERIFY-LEGACY-'.$i,'is_boarding'=>$i%2,'status'=>'active','grade'=>'Form 1']; }
        foreach (array_chunk($rows,100) as $chunk) { DB::table('students')->insert($chunk); }
        $before = DB::table('students')->orderBy('id')->get()->map(fn ($row)=>(array)$row)->all();
        $migration = require database_path('migrations/2026_10_24_000002_add_student_scholarships.php');
        $migration->up();
        $after = DB::table('students')->orderBy('id')->get()->map(function ($row) { $data=(array)$row; unset($data['funding_status']); return $data; })->all();
        $this->assertCount(955,$after);
        $this->assertSame($before,$after);
        $this->assertSame(955,Student::where('funding_status','regular')->count());
        $this->assertSame(0,StudentScholarship::count());
        file_put_contents(storage_path('logs/scholarship-legacy-preservation.json'),json_encode(['records'=>955,'all_original_fields_preserved'=>true,
            'before_sha256'=>hash('sha256',json_encode($before)),'after_sha256'=>hash('sha256',json_encode($after)),
            'funding_default'=>'regular','production_accessed'=>false],JSON_PRETTY_PRINT));
    }
}
