<?php

namespace Tests\Feature\Finance;

use App\Models\Account;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\CurrencyWordsService;
use App\Services\Finance\HandeFeeVotesService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandeFeeVotesAndReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $clerkUser;
    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
            'is_active' => true,
        ]);

        $this->seed(ChartOfAccountsSeeder::class);

        $this->adminUser = User::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'School Admin',
        ]);
        $this->adminUser->assignRole('school-admin');

        $this->clerkUser = User::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Accounts Clerk',
        ]);
        $this->clerkUser->assignRole('accounts-clerk');

        // Locate or create cash account
        $this->cashAccount = Account::where('school_id', $this->school->id)
            ->where(function ($q) {
                $q->where('category', 'cash')->orWhere('code', '1100');
            })
            ->first() ?? Account::create([
                'school_id' => $this->school->id,
                'code' => '1100',
                'name' => 'Main Cash on Hand',
                'type' => 'asset',
                'category' => 'cash',
                'is_active' => true,
                'is_postable' => true,
            ]);
    }

    protected function createStudent(bool $isBoarding = true): Student
    {
        static $seq = 100;
        $seq++;

        return Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'HHS-' . $seq,
            'first_name' => 'Tatenda',
            'last_name' => 'Moyo',
            'grade' => 'Form 3',
            'class_name' => 'East',
            'gender' => 'Male',
            'dob' => '2010-05-15',
            'enrollment_date' => '2026-01-10',
            'status' => 'active',
            'is_boarding' => $isBoarding,
        ]);
    }

    /**
     * 1. Test HandeFeeVotesService syncs Boarding ($470) and Day ($135) vote hierarchy
     */
    public function test_hande_fee_votes_service_syncs_boarding_and_day_packages_with_sub_votes(): void
    {
        $result = HandeFeeVotesService::syncSchoolVotes($this->school, '2026', 'Term 3');

        $this->assertArrayHasKey('boarding_parent', $result);
        $this->assertArrayHasKey('day_parent', $result);

        $boardingParent = $result['boarding_parent'];
        $this->assertEquals(470.00, (float) $boardingParent->amount);
        $this->assertEquals('boarding', $boardingParent->residency_type);

        $boardingSubVotes = $boardingParent->subVotes;
        $this->assertCount(7, $boardingSubVotes);
        $this->assertEquals(470.00, (float) $boardingSubVotes->sum('amount'));

        // Verify sub-votes breakdown
        $tuitionVote = $boardingSubVotes->firstWhere('vote_code', 'TUITION');
        $this->assertNotNull($tuitionVote);
        $this->assertEquals(20.00, (float) $tuitionVote->amount);

        $boardingFeeVote = $boardingSubVotes->firstWhere('vote_code', 'BOARDING_FEES');
        $this->assertNotNull($boardingFeeVote);
        $this->assertEquals(380.00, (float) $boardingFeeVote->amount);

        // Day package ($135.00)
        $dayParent = $result['day_parent'];
        $this->assertEquals(135.00, (float) $dayParent->amount);
        $this->assertEquals('day', $dayParent->residency_type);

        $daySubVotes = $dayParent->subVotes;
        $this->assertCount(7, $daySubVotes);
        $this->assertEquals(135.00, (float) $daySubVotes->sum('amount'));

        $foodVote = $daySubVotes->firstWhere('vote_code', 'FOOD');
        $this->assertNotNull($foodVote);
        $this->assertEquals(45.00, (float) $foodVote->amount);

        // Verify 9 authorized committee signatories
        $signatories = HandeFeeVotesService::getAuthorisedSignatories();
        $this->assertCount(9, $signatories);
        $this->assertEquals('MACHAKAIRE N.B', $signatories[0]['name']);
        $this->assertEquals('HEAD', $signatories[0]['role']);
    }

    /**
     * 2. Test CurrencyWordsService conversion to English words
     */
    public function test_currency_words_service_converts_amounts_accurately(): void
    {
        $words135 = CurrencyWordsService::toWords(135.00);
        $this->assertStringContainsStringIgnoringCase('one hundred', $words135);
        $this->assertStringContainsStringIgnoringCase('thirty-five dollars', $words135);

        $words470 = CurrencyWordsService::toWords(470.00);
        $this->assertStringContainsStringIgnoringCase('four hundred', $words470);
        $this->assertStringContainsStringIgnoringCase('seventy dollars', $words470);

        $wordsWithCents = CurrencyWordsService::toWords(250.75);
        $this->assertStringContainsStringIgnoringCase('two hundred', $wordsWithCents);
        $this->assertStringContainsStringIgnoringCase('seventy-five cents', $wordsWithCents);
    }

    /**
     * 3. Test receiving payment with MOPSE serialized receipt and FIFO sub-account allocation
     */
    public function test_receive_payment_with_mopse_serial_and_vote_allocations(): void
    {
        $sync = HandeFeeVotesService::syncSchoolVotes($this->school, '2026', 'Term 3');
        $boardingParent = $sync['boarding_parent'];

        $student = $this->createStudent(true);

        // Create an invoice with the parent fee ($470.00)
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 3',
            'number' => 'INV-2026-000001',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 470.00,
            'balance' => 470.00,
            'status' => 'issued',
        ]);

        $invoiceItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'fee_structure_id' => $boardingParent->id,
            'description' => $boardingParent->label,
            'quantity' => 1,
            'unit_amount' => 470.00,
            'line_total' => 470.00,
        ]);

        // Partial payment of $200.00 with MOPSE Book Voucher #134201
        $response = $this->actingAs($this->clerkUser)
            ->post(route('admin.payments.store', $invoice), [
                'amount' => 200.00,
                'paid_at' => now()->toDateString(),
                'method' => 'cash',
                'account_id' => $this->cashAccount->id,
                'mopse_receipt_number' => '134201',
                'reference' => 'Cash received from parent',
                'allocations' => [
                    $invoiceItem->id => 200.00,
                ],
            ]);

        $response->assertRedirect();

        // Verify payment record
        $payment = Payment::where('student_id', $student->id)
            ->where('mopse_receipt_number', '134201')
            ->first();

        $this->assertNotNull($payment);
        $this->assertEquals(200.00, (float) $payment->amount);
        $this->assertEquals('134201', $payment->mopse_receipt_number);
        $this->assertEquals($this->clerkUser->id, $payment->created_by);

        // Verify invoice balance updated
        $invoice->refresh();
        $this->assertEquals(270.00, (float) $invoice->balance);
        $this->assertEquals('partial', $invoice->status);

        // Verify PaymentAllocations created
        $paymentAllocs = PaymentAllocation::where('payment_id', $payment->id)->get();
        $this->assertGreaterThan(0, $paymentAllocs->count());
        $this->assertEquals(200.00, (float) $paymentAllocs->first()->amount);

        // Test MOPSE receipt endpoint
        $receiptResponse = $this->actingAs($this->clerkUser)
            ->get(route('admin.payments.receipt-mopse', $payment));

        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('134201');
        $receiptResponse->assertSee('BUHERA RURAL DISTRICT COUNCIL');
        $receiptResponse->assertSee('HANDE SECONDARY SCHOOL');
        $receiptResponse->assertSee('Two hundred dollars only');
    }

    public function test_manual_invoice_item_vote_code_flows_into_payment_allocation_and_mopse_voucher(): void
    {
        $student = $this->createStudent(true);
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2026',
            'term' => 'Term 3',
            'number' => 'INV-2026-000099',
            'issued_at' => now()->toDateString(),
            'total_amount' => 40.00,
            'balance' => 40.00,
            'status' => 'unpaid',
        ]);
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'vote_code' => 'SCIENCE_LEVY',
            'description' => 'Extra Science Subject',
            'category' => 'subject',
            'quantity' => 1,
            'unit_amount' => 20.00,
            'line_total' => 20.00,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'vote_code' => 'TUITION',
            'description' => 'Tuition',
            'category' => 'tuition',
            'quantity' => 1,
            'unit_amount' => 20.00,
            'line_total' => 20.00,
        ]);

        $paymentForm = $this->actingAs($this->clerkUser)
            ->get(route('admin.payments.create', $invoice));
        $paymentForm->assertOk();
        $tuitionPosition = strpos($paymentForm->getContent(), '[TUITION]');
        $sciencePosition = strpos($paymentForm->getContent(), '[SCIENCE_LEVY]');
        $this->assertNotFalse($tuitionPosition);
        $this->assertNotFalse($sciencePosition);
        $this->assertLessThan($sciencePosition, $tuitionPosition);

        $response = $this->actingAs($this->clerkUser)
            ->post(route('admin.payments.store', $invoice), [
                'amount' => 20.00,
                'paid_at' => now()->toDateString(),
                'method' => 'cash',
                'account_id' => $this->cashAccount->id,
                'mopse_receipt_number' => '134299',
                'allocations' => [$item->id => 20.00],
            ]);
        $response->assertRedirect();

        $payment = Payment::where('mopse_receipt_number', '134299')->firstOrFail();
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_item_id' => $item->id,
            'vote_code' => 'SCIENCE_LEVY',
            'amount' => 20.00,
        ]);

        $receiptResponse = $this->actingAs($this->clerkUser)
            ->get(route('admin.payments.receipt-mopse', $payment));
        $receiptResponse->assertOk();
        $this->assertMatchesRegularExpression(
            '/<td class="col-desc">SCIENCE LEVY<\/td>\s*<td class="col-dollars">20<\/td>/',
            $receiptResponse->getContent()
        );
    }

    /**
     * 4. Test advance fee receipting and subsequent application to future invoice
     */
    public function test_advance_payment_receipting_and_subsequent_allocation_to_invoice(): void
    {
        $student = $this->createStudent(false);

        // 1. Parent pays $300 in advance before next term invoice is generated
        $advanceResponse = $this->actingAs($this->clerkUser)
            ->post(route('admin.payments.advance.store'), [
                'student_id' => $student->id,
                'amount' => 300.00,
                'paid_at' => now()->toDateString(),
                'method' => 'cash',
                'account_id' => $this->cashAccount->id,
                'mopse_receipt_number' => '134202',
                'term' => 'Term 1 2027 Prepayment',
                'notes' => 'Early fee payment for 2027',
            ]);

        $advanceResponse->assertRedirect();

        $advancePayment = Payment::where('student_id', $student->id)
            ->where('is_advance', true)
            ->first();

        $this->assertNotNull($advancePayment);
        $this->assertEquals(300.00, (float) $advancePayment->amount);
        $this->assertEquals(300.00, (float) $advancePayment->advance_balance);
        $this->assertEquals('134202', $advancePayment->mopse_receipt_number);

        // 2. Later, create term invoice ($470.00)
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2027',
            'term' => 'Term 1',
            'number' => 'INV-2027-000001',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => 470.00,
            'balance' => 470.00,
            'status' => 'issued',
        ]);

        // 3. Apply the $300 advance credit to the invoice
        $applyResponse = $this->actingAs($this->clerkUser)
            ->post(route('admin.invoices.apply-advance', $invoice), [
                'payment_id' => $advancePayment->id,
                'amount' => 300.00,
            ]);

        $applyResponse->assertRedirect();

        // 4. Verify advance balance is exhausted and invoice reflects payment
        $advancePayment->refresh();
        $this->assertEquals(0.00, (float) $advancePayment->advance_balance);

        $invoice->refresh();
        $this->assertEquals(170.00, (float) $invoice->balance);
        $this->assertEquals('partial', $invoice->status);
    }

    /**
     * 5. Test Daily Banking Handover Report generation and CSV export
     */
    public function test_daily_banking_handover_report_summarizes_daily_cash_and_mopse_receipts(): void
    {
        $today = now()->toDateString();
        $student = $this->createStudent(true);

        // Create a cash payment with MOPSE number for today
        Payment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'amount' => 470.00,
            'paid_at' => $today,
            'method' => 'cash',
            'account_id' => $this->cashAccount->id,
            'mopse_receipt_number' => '134203',
            'created_by' => $this->clerkUser->id,
            'created_at' => now(),
        ]);

        // Hit the handover report route
        $reportResponse = $this->actingAs($this->clerkUser)
            ->get(route('admin.reports.daily-banking-handover', [
                'date' => $today,
                'method' => 'cash',
                'banking_ref' => 'DEPOSIT-CBZ-991',
            ]));

        $reportResponse->assertStatus(200);
        $reportResponse->assertSee('Hande Secondary School');
        $reportResponse->assertSee('Official Daily Cash Banking & Handover Report', false);
        $reportResponse->assertSee('134203');
        $reportResponse->assertSee('DEPOSIT-CBZ-991');
        $reportResponse->assertSee('Handed Over By');
        $reportResponse->assertSee('Received & Verified By', false);

        // Hit CSV export route
        $csvResponse = $this->actingAs($this->clerkUser)
            ->get(route('admin.reports.daily-banking-handover.export', [
                'date' => $today,
                'method' => 'cash',
            ]));

        $csvResponse->assertStatus(200);
        $this->assertTrue(str_contains($csvResponse->headers->get('content-disposition'), 'daily-banking-handover'));
    }

    public function test_admin_invoices_index_loads_successfully_and_renders_advance_button(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.invoices.index'));

        $response->assertStatus(200);
        $response->assertSee('Invoices');
        $response->assertSee('Receive Advance Payment');
        $response->assertSee(route('admin.payments.advance.create'));
    }

    public function test_super_admin_without_school_id_can_access_invoices_index(): void
    {
        $superAdmin = User::factory()->create([
            'school_id' => null,
            'name' => 'Global Super Admin',
            'email' => 'superadmin@handehigh.test',
        ]);
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.invoices.index'));

        $response->assertStatus(200);
        $response->assertSee('Invoices');
        $response->assertSee('Receive Advance Payment');
    }

    public function test_advance_payment_create_form_loads_successfully(): void
    {
        $response = $this->actingAs($this->clerkUser)
            ->get(route('admin.payments.advance.create'));

        $response->assertStatus(200);
        $response->assertSee('Receive Advance Fee Payment');
        $response->assertSee(route('admin.payments.advance.store'));
    }
}
