<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerEntry;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\School;
use App\Services\Finance\InvoiceFeeSelectionService;
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
    public function handle(InvoiceFeeSelectionService $feeSelectionService)
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
                    $dryRun,
                    $feeSelectionService,
                );

                $createdLabel = $dryRun ? 'would be created' : 'created';
                $this->info("  ✓ Success: {$result['created']} invoices {$createdLabel}, {$result['skipped']} skipped, {$result['errors']} errors");
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
    protected function generateInvoicesForSchool(
        School $school,
        string $academicYear,
        string $term,
        bool $dryRun,
        InvoiceFeeSelectionService $feeSelectionService,
    ): array
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
        $enrollments = Enrollment::with(['student', 'class'])
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->get()
            ->unique('student_id')
            ->values();

        if ($enrollments->isEmpty()) {
            $this->warn("  No active enrollments found for {$school->name}");
            return ['created' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $this->info("  Found {$enrollments->count()} active enrollments");

        $feeStructures = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear)
            ->where('term', $term)
            ->get();

        DB::transaction(function () use (
            $enrollments,
            $academicYear,
            $term,
            $generationDate,
            $dueDate,
            $dryRun,
            $feeSelectionService,
            $feeStructures,
            &$created,
            &$skipped,
            &$errors
        ) {
            foreach ($enrollments as $enrollment) {
                try {
                    if (! $enrollment->student) {
                        $skipped++;
                        continue;
                    }

                    // Check if invoice already exists
                    $existingInvoice = Invoice::where('school_id', $enrollment->school_id)
                        ->where('student_id', $enrollment->student_id)
                        ->where('academic_year', $academicYear)
                        ->where('term', $term)
                        ->where('status', '!=', 'cancelled')
                        ->first();

                    if ($existingInvoice) {
                        $skipped++;
                        continue;
                    }

                    $student = $enrollment->student;
                    $applicableFees = $feeSelectionService->select(
                        $feeStructures,
                        $enrollment->grade ?? $student->grade ?? $enrollment->class?->grade,
                        $enrollment->isBoarder() || (bool) $student->is_boarding,
                        (bool) $enrollment->has_transport || (bool) $student->has_transport,
                        false,
                    );

                    if ($applicableFees->isEmpty()) {
                        $errors++;
                        Log::warning("No applicable fee structure found for Grade {$enrollment->grade}, {$academicYear} {$term}");
                        continue;
                    }

                    if ($dryRun) {
                        $created++;
                        continue;
                    }

                    // Generate invoice number
                    $invoiceNumber = Invoice::generateNextNumber($enrollment->school_id, $academicYear);

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
                    foreach ($applicableFees as $feeStructure) {
                        $lineTotal = (float) $feeStructure->amount;
                        $totalAmount += $lineTotal;

                        InvoiceItem::create([
                            'school_id' => $enrollment->school_id,
                            'invoice_id' => $invoice->id,
                            'fee_structure_id' => $feeStructure->id,
                            'vote_code' => $feeStructure->vote_code,
                            'description' => $feeStructure->label,
                            'category' => $feeStructure->category,
                            'quantity' => 1,
                            'unit_amount' => (float) $feeStructure->amount,
                            'line_total' => $lineTotal,
                            'project_id' => $feeStructure->project_id,
                            'cost_center_id' => $feeStructure->cost_center_id,
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
