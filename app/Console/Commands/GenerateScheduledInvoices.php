<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerEntry;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateScheduledInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-scheduled 
                            {--school_id= : Generate for a specific school only}
                            {--academic_year= : Target academic year (e.g., 2025-2026)}
                            {--term= : Target term (e.g., Term 1, Term 2, Term 3)}
                            {--dry-run : Run without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate invoices for active students based on school settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schoolId = $this->option('school_id');
        $academicYear = $this->option('academic_year');
        $term = $this->option('term');
        $dryRun = $this->option('dry-run');

        // Get schools to process
        $schools = $schoolId 
            ? School::where('id', $schoolId)->get() 
            : School::where('status', 'active')->get();

        if ($schools->isEmpty()) {
            $this->error('No schools found to process.');
            return 1;
        }

        $this->info('Starting scheduled invoice generation...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No invoices will be created');
        }

        foreach ($schools as $school) {
            $this->info("Processing school: {$school->name} (ID: {$school->id})");
            
            // Check if school has invoice generation settings
            $settings = json_decode($school->settings ?? '{}', true);
            $autoGenerateEnabled = $settings['auto_generate_invoices'] ?? false;
            
            if (!$autoGenerateEnabled && !$academicYear) {
                $this->warn("  Auto-generation not enabled for {$school->name}. Skipping...");
                continue;
            }

            // Determine target academic year and term
            $targetYear = $academicYear ?? $this->determineAcademicYear();
            $targetTerm = $term ?? ($settings['default_term'] ?? 'Term 1');
            
            $this->info("  Target: {$targetYear} - {$targetTerm}");

            try {
                $result = $this->generateInvoicesForSchool(
                    $school, 
                    $targetYear, 
                    $targetTerm, 
                    $dryRun
                );

                $this->info("  ✓ Success: {$result['created']} invoices created, {$result['skipped']} skipped, {$result['errors']} errors");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
                Log::error("Invoice generation failed for school {$school->id}: {$e->getMessage()}");
            }
        }

        $this->info('Scheduled invoice generation completed.');
        return 0;
    }

    /**
     * Generate invoices for a specific school
     */
    protected function generateInvoicesForSchool(School $school, string $academicYear, string $term, bool $dryRun = false): array
    {
        $created = 0;
        $skipped = 0;
        $errors = 0;
        $generationDate = now()->toDateString();

        // Get settings for due date calculation
        $settings = json_decode($school->settings ?? '{}', true);
        $dueDateDays = $settings['invoice_due_days'] ?? 30;
        $dueDate = now()->addDays($dueDateDays)->toDateString();

        // Get active enrollments
        $enrollments = Enrollment::with(['student', 'guardian', 'class'])
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->get();

        if ($enrollments->isEmpty()) {
            $this->warn("  No active enrollments found for {$school->name}");
            return ['created' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $this->info("  Found {$enrollments->count()} active enrollments");

        if ($dryRun) {
            return [
                'created' => $enrollments->count(), 
                'skipped' => 0, 
                'errors' => 0
            ];
        }

        DB::transaction(function () use ($enrollments, $academicYear, $term, $generationDate, $dueDate, &$created, &$skipped, &$errors) {
            foreach ($enrollments as $enrollment) {
                try {
                    // Check if invoice already exists
                    $existingInvoice = Invoice::where('school_id', $enrollment->school_id)
                        ->where('student_id', $enrollment->student_id)
                        ->where('academic_year', $academicYear)
                        ->where('term', $term)
                        ->first();

                    if ($existingInvoice) {
                        $skipped++;
                        continue;
                    }

                    // Get fee structures
                    $feeStructures = FeeStructure::where('school_id', $enrollment->school_id)
                        ->where('academic_year', $academicYear)
                        ->where('term', $term)
                        ->where('grade', $enrollment->grade)
                        ->where('is_optional', false)
                        ->get();

                    if ($feeStructures->isEmpty()) {
                        $errors++;
                        Log::warning("No fee structure found for Grade {$enrollment->grade}, {$academicYear} {$term}");
                        continue;
                    }

                    // Generate invoice number
                    $invoiceNumber = 'INV-' . str_replace('-', '', $academicYear) . '-' . 
                                   str_pad(Invoice::where('school_id', $enrollment->school_id)->count() + 1, 6, '0', STR_PAD_LEFT);

                    // Create invoice
                    $invoice = Invoice::create([
                        'school_id' => $enrollment->school_id,
                        'student_id' => $enrollment->student_id,
                        'guardian_id' => $enrollment->guardian_id,
                        'enrollment_id' => $enrollment->id,
                        'number' => $invoiceNumber,
                        'type' => 'fees',
                        'academic_year' => $academicYear,
                        'term' => $term,
                        'total_amount' => 0,
                        'balance' => 0,
                        'issued_at' => $generationDate,
                        'due_date' => $dueDate,
                        'status' => 'unpaid',
                    ]);

                    // Create invoice items
                    $totalAmount = 0;
                    foreach ($feeStructures as $feeStructure) {
                        $lineTotal = (float) $feeStructure->amount;
                        $totalAmount += $lineTotal;

                        InvoiceItem::create([
                            'school_id' => $enrollment->school_id,
                            'invoice_id' => $invoice->id,
                            'fee_structure_id' => $feeStructure->id,
                            'description' => $feeStructure->label,
                            'category' => $feeStructure->category,
                            'quantity' => 1,
                            'unit_amount' => (float) $feeStructure->amount,
                            'line_total' => $lineTotal,
                        ]);
                    }

                    // Update invoice totals
                    $invoice->update([
                        'total_amount' => $totalAmount,
                        'balance' => $totalAmount,
                    ]);

                    // Create ledger entries
                    LedgerEntry::create([
                        'school_id' => $enrollment->school_id,
                        'student_id' => $enrollment->student_id,
                        'invoice_id' => $invoice->id,
                        'entry_date' => $generationDate,
                        'account_code' => 'FEES_RECEIVABLE',
                        'type' => 'debit',
                        'amount' => $totalAmount,
                        'description' => "Auto-generated invoice {$invoiceNumber} for {$enrollment->student->first_name} {$enrollment->student->last_name}",
                    ]);

                    LedgerEntry::create([
                        'school_id' => $enrollment->school_id,
                        'student_id' => $enrollment->student_id,
                        'invoice_id' => $invoice->id,
                        'entry_date' => $generationDate,
                        'account_code' => 'FEES_REVENUE',
                        'type' => 'credit',
                        'amount' => $totalAmount,
                        'description' => "Auto-generated invoice {$invoiceNumber} for {$enrollment->student->first_name} {$enrollment->student->last_name}",
                    ]);

                    $created++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Failed to create invoice for enrollment {$enrollment->id}: {$e->getMessage()}");
                }
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Determine current academic year based on date
     */
    protected function determineAcademicYear(): string
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Assuming academic year starts in September
        if ($currentMonth >= 9) {
            return "{$currentYear}-" . ($currentYear + 1);
        } else {
            return ($currentYear - 1) . "-{$currentYear}";
        }
    }
}
