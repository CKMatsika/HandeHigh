<?php

namespace Tests\Feature\Branding;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Branding\SchoolDocumentBrandingService;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolProfileAndDocumentIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected User $adminUser;
    protected User $otherAdminUser;
    protected SchoolDocumentBrandingService $brandingService;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        Storage::fake('public');

        $this->school = School::create([
            'name' => 'Hande High School',
            'display_name' => 'Hande High School (Main Campus)',
            'code' => 'HHS',
            'motto' => 'Strive for Excellence',
            'email' => 'admin@handehigh.ac.zw',
            'phone' => '+263 242 123456',
            'telephone' => '+263 242 123456',
            'mobile' => '+263 771 234567',
            'whatsapp' => '+263 771 234567',
            'address' => '123 Education Way, Harare',
            'postal_address' => 'P.O. Box 456, Harare',
            'registration_number' => 'REG-2026-9876',
            'zimsec_center_number' => 'ZC-654321',
            'principal_name' => 'Dr. A. Ndlovu',
            'bursar_name' => 'Mrs. T. Moyo',
            'administrator_name' => 'Mr. C. Mutasa',
            'primary_color' => '#1e3a8a',
            'secondary_color' => '#d97706',
            'footer_text' => 'Official Computer-Generated Document',
            'bank_name' => 'CBZ Bank',
            'bank_account_name' => 'Hande High School Operations',
            'bank_account_number' => '1029384756',
            'bank_branch' => 'Kwame Nkrumah Branch',
            'payment_instructions' => 'Quote student admission number as payment reference.',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->otherSchool = School::create([
            'name' => 'Other Academy',
            'code' => 'OTH',
            'email' => 'admin@other.ac.zw',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->seed(ChartOfAccountsSeeder::class);

        $this->adminUser = User::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'School Administrator',
            'email' => 'admin@handehigh.ac.zw',
        ]);
        $this->adminUser->assignRole('school-admin');

        $this->otherAdminUser = User::factory()->create([
            'school_id' => $this->otherSchool->id,
            'name' => 'Other Administrator',
            'email' => 'admin@other.ac.zw',
        ]);
        $this->otherAdminUser->assignRole('school-admin');

        app(TenantContext::class)->set($this->school);
        $this->brandingService = app(SchoolDocumentBrandingService::class);
    }

    public function test_admin_can_view_school_profile_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.school.profile'));

        $response->assertOk();
        $response->assertSee('Hande High School');
        $response->assertSee('Strive for Excellence');
        $response->assertSee('ZC-654321');
        $response->assertSee('CBZ Bank');
    }

    public function test_admin_can_update_school_profile_and_branding(): void
    {
        $payload = [
            'name' => 'Hande High School Enhanced',
            'display_name' => 'Hande High Senior Academy',
            'motto' => 'Excellence and Integrity',
            'email' => 'info@handehigh.ac.zw',
            'website' => 'https://handehigh.ac.zw',
            'phone' => '+263 242 777888',
            'telephone' => '+263 242 777888',
            'mobile' => '+263 772 999000',
            'whatsapp' => '+263 772 999000',
            'address' => '789 Highfield Road, Harare',
            'postal_address' => 'P.O. Box 999, Harare',
            'registration_number' => 'REG-9999-ZW',
            'zimsec_center_number' => 'ZC-999999',
            'principal_name' => 'Prof. E. Chikwanha',
            'bursar_name' => 'Mr. P. Sibanda',
            'administrator_name' => 'Ms. R. Mutasa',
            'primary_color' => '#0f172a',
            'secondary_color' => '#10b981',
            'footer_text' => 'Empowering Leaders of Tomorrow',
            'bank_name' => 'Stanbic Bank Zimbabwe',
            'bank_account_name' => 'Hande High School Main Account',
            'bank_account_number' => '9080706050',
            'bank_branch' => 'Samora Machel Branch',
            'payment_instructions' => 'Use student ID as reference.',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.school.profile.update'), $payload);

        $response->assertRedirect(route('admin.school.profile'));
        $response->assertSessionHas('success');

        $this->school->refresh();
        $this->assertEquals('Hande High School Enhanced', $this->school->name);
        $this->assertEquals('Hande High Senior Academy', $this->school->display_name);
        $this->assertEquals('Excellence and Integrity', $this->school->motto);
        $this->assertEquals('ZC-999999', $this->school->zimsec_center_number);
        $this->assertEquals('Stanbic Bank Zimbabwe', $this->school->bank_name);
        $this->assertEquals('9080706050', $this->school->bank_account_number);
    }

    public function test_admin_can_upload_and_replace_school_logo(): void
    {
        $file = UploadedFile::fake()->image('school_crest.png', 300, 300);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.school.profile.update'), [
                'name' => $this->school->name,
                'email' => $this->school->email,
                'logo' => $file,
            ]);

        $response->assertRedirect(route('admin.school.profile'));
        $this->school->refresh();

        $this->assertNotNull($this->school->logo);
        $this->assertTrue(str_starts_with($this->school->logo, 'schools/' . $this->school->id . '/branding/'));
        Storage::disk('public')->assertExists($this->school->logo);

        $firstLogoPath = $this->school->logo;

        // Upload replacement logo
        $newFile = UploadedFile::fake()->image('new_crest.jpg', 400, 400);
        $this->actingAs($this->adminUser)
            ->put(route('admin.school.profile.update'), [
                'name' => $this->school->name,
                'email' => $this->school->email,
                'logo' => $newFile,
            ]);

        $this->school->refresh();
        $this->assertNotEquals($firstLogoPath, $this->school->logo);
        Storage::disk('public')->assertMissing($firstLogoPath);
        Storage::disk('public')->assertExists($this->school->logo);
    }

    public function test_svg_upload_is_rejected_for_security(): void
    {
        $svgFile = UploadedFile::fake()->create('malicious.svg', 100, 'image/svg+xml');

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.school.profile.update'), [
                'name' => $this->school->name,
                'email' => $this->school->email,
                'logo' => $svgFile,
            ]);

        $response->assertSessionHasErrors('logo');
    }

    public function test_admin_can_remove_school_logo(): void
    {
        $file = UploadedFile::fake()->image('crest.png', 200, 200);
        $path = $this->brandingService->storeLogo($this->school, $file);
        $this->school->refresh();
        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.school.profile.update'), [
                'name' => $this->school->name,
                'email' => $this->school->email,
                'remove_logo' => '1',
            ]);

        $response->assertRedirect(route('admin.school.profile'));
        $this->school->refresh();
        $this->assertNull($this->school->logo);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_multi_tenant_isolation_prevents_cross_tenant_branding_updates(): void
    {
        // otherAdminUser belongs to otherSchool
        $response = $this->actingAs($this->otherAdminUser)
            ->put(route('admin.school.profile.update'), [
                'name' => 'Hacked Name',
                'email' => 'hacked@other.ac.zw',
            ]);

        $response->assertRedirect();
        $this->otherSchool->refresh();
        $this->assertEquals('Hacked Name', $this->otherSchool->name);

        // Verify primary school was untouched
        $this->school->refresh();
        $this->assertEquals('Hande High School', $this->school->name);
    }

    public function test_invoice_creation_captures_frozen_school_snapshot_and_preserves_historical_integrity(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Tatenda',
            'last_name' => 'Chiwenga',
            'gender' => 'Male',
            'grade' => 'Form 1',
            'class_name' => '1A',
            'status' => 'active',
            'is_boarding' => false,
            'enrollment_date' => '2026-01-10',
        ]);

        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'number' => 'INV-2026-0001',
            'type' => 'fees',
            'issued_at' => '2026-01-15',
            'due_date' => '2026-02-15',
            'total_amount' => 500.00,
            'balance' => 500.00,
            'status' => 'issued',
            'academic_year' => '2026',
            'term' => '1',
        ]);

        $this->assertNotNull($invoice->school_snapshot);
        $this->assertEquals('Hande High School', $invoice->school_snapshot['name']);
        $this->assertEquals('ZC-654321', $invoice->school_snapshot['zimsec_center_number']);
        $this->assertEquals('CBZ Bank', $invoice->school_snapshot['bank_name']);

        // Now modify the school profile in the database
        $this->school->update([
            'name' => 'New School Name After Legal Restructure',
            'zimsec_center_number' => 'ZC-NEW-999',
            'bank_name' => 'Standard Chartered Bank',
        ]);

        // Verify that the historical invoice retains its original frozen branding
        $invoice->refresh();
        $branding = $invoice->school_branding;
        $this->assertEquals('Hande High School', $branding['name']);
        $this->assertEquals('ZC-654321', $branding['zimsec_center_number']);
        $this->assertEquals('CBZ Bank', $branding['bank_name']);
        $this->assertNotEquals('New School Name After Legal Restructure', $branding['name']);
    }

    public function test_receipt_creation_captures_frozen_school_snapshot_and_preserves_historical_integrity(): void
    {
        $receipt = Receipt::create([
            'school_id' => $this->school->id,
            'receipt_number' => 'REC-2026-0001',
            'receipt_date' => '2026-01-20',
            'type' => 'sale',
            'customer_name' => 'Ruvimbo Katsande',
            'total_amount' => 300.00,
            'tax_amount' => 0.00,
            'grand_total' => 300.00,
            'payment_method' => 'bank_transfer',
            'reference' => 'TXN-998877',
        ]);

        $this->assertNotNull($receipt->school_snapshot);
        $this->assertEquals('Hande High School', $receipt->school_snapshot['name']);
        $this->assertEquals('CBZ Bank', $receipt->school_snapshot['bank_name']);

        // Update school branding
        $this->school->update([
            'name' => 'Future Academy Name',
            'bank_name' => 'FBC Bank',
        ]);

        $receipt->refresh();
        $this->assertEquals('Hande High School', $receipt->school_branding['name']);
        $this->assertEquals('CBZ Bank', $receipt->school_branding['bank_name']);
    }

    public function test_document_header_blade_component_renders_correctly(): void
    {
        $html = Blade::render(
            '<x-documents.school-header :school="$school" title="Test Document" subtitle="Academic Subtitle" reference="DOC-001" />',
            ['school' => $this->school]
        );

        $this->assertStringContainsString('Hande High School', $html);
        $this->assertStringContainsString('Strive for Excellence', $html);
        $this->assertStringContainsString('ZC-654321', $html);
        $this->assertStringContainsString('Test Document', $html);
        $this->assertStringContainsString('Academic Subtitle', $html);
        $this->assertStringContainsString('DOC-001', $html);
    }

    public function test_document_footer_blade_component_renders_correctly(): void
    {
        $html = Blade::render(
            '<x-documents.school-footer :school="$school" :showBanking="true" />',
            ['school' => $this->school]
        );

        $this->assertStringContainsString('CBZ Bank', $html);
        $this->assertStringContainsString('1029384756', $html);
        $this->assertStringContainsString('Kwame Nkrumah Branch', $html);
        $this->assertStringContainsString('Official Computer-Generated Document', $html);
    }
}
