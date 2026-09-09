<?php

namespace App\Services\YearEnd;

use App\Models\BedAssignment;
use App\Models\BorrowRecord;
use App\Models\CreditNote;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAsset;
use App\Models\StudentClearance;
use App\Models\User;
use App\Models\YearEndProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class YearEndProcessingService
{
    /**
     * Standard grade progression order.
     */
    public const GRADE_ORDER = [
        'Grade 1' => 'Grade 2',
        'Grade 2' => 'Grade 3',
        'Grade 3' => 'Grade 4',
        'Grade 4' => 'Grade 5',
        'Grade 5' => 'Grade 6',
        'Grade 6' => 'Grade 7',
        'Grade 7' => 'Graduated', // Primary exit
        'Form 1'  => 'Form 2',
        'Form 2'  => 'Form 3',
        'Form 3'  => 'Form 4',
        'Form 4'  => 'Graduated', // O-Level exit
        'Form 5'  => 'Form 6',
        'Form 6'  => 'Graduated', // A-Level exit
    ];

    /**
     * Grades considered graduating exit streams.
     */
    public const GRADUATING_GRADES = ['Grade 7', 'Form 4', 'Form 6'];

    /**
     * Generate a new Year-End Transition Draft for a school.
     */
    public function generateTransitionDraft(School $school, string $sourceYear, string $targetYear, ?User $creator = null): YearEndProcess
    {
        // Fetch all active students in the school
        $students = Student::where('school_id', $school->id)
            ->whereIn('status', ['active', 'pending_clearance'])
            ->with(['enrollments' => fn($q) => $q->where('academic_year', $sourceYear)])
            ->orderBy('grade')
            ->orderBy('class_name')
            ->orderBy('last_name')
            ->get();

        $draftItems = [];
        $promotionsCount = 0;
        $clearanceCount = 0;
        $retentionsCount = 0;

        foreach ($students as $student) {
            $currentGrade = $student->grade ?? 'Form 1';
            $currentClass = $student->class_name ?? '';
            $isGraduating = in_array($currentGrade, self::GRADUATING_GRADES);

            $nextGrade = self::GRADE_ORDER[$currentGrade] ?? null;
            $action = 'promote';
            $nextClassName = $currentClass;

            if ($isGraduating || $nextGrade === 'Graduated') {
                $action = 'move_to_clearance';
                $nextGrade = null;
                $nextClassName = null;
                $clearanceCount++;
            } else {
                $action = 'promote';
                $promotionsCount++;

                // If class name contains grade, transform (e.g., "1A" -> "2A", "Form 1 North" -> "Form 2 North")
                if ($nextGrade && $currentClass) {
                    $nextClassName = $this->deriveNextClassName($currentGrade, $nextGrade, $currentClass);
                }
            }

            // Evaluate clearance metrics
            $clearanceEvaluation = $this->evaluateStudentClearance($student);

            $draftItems[] = [
                'student_id' => $student->id,
                'admission_number' => $student->admission_number ?? '—',
                'name' => $student->full_name,
                'gender' => $student->gender,
                'current_grade' => $currentGrade,
                'current_class_name' => $currentClass,
                'proposed_action' => $action, // promote, repeat, move_to_clearance, transfer_out
                'target_grade' => $nextGrade,
                'target_class_name' => $nextClassName,
                'is_boarding' => (bool) $student->is_boarding,
                'clearance_preview' => $clearanceEvaluation,
            ];
        }

        $summary = [
            'total_students' => count($students),
            'promotions_count' => $promotionsCount,
            'clearance_count' => $clearanceCount,
            'retentions_count' => $retentionsCount,
            'transfers_count' => 0,
        ];

        // Check if a draft already exists for this source & target year
        $existingDraft = YearEndProcess::where('school_id', $school->id)
            ->where('source_academic_year', $sourceYear)
            ->where('target_academic_year', $targetYear)
            ->where('status', 'draft')
            ->first();

        if ($existingDraft) {
            $existingDraft->update([
                'summary' => $summary,
                'draft_payload' => $draftItems,
                'created_by' => $creator?->id ?? $existingDraft->created_by,
            ]);
            return $existingDraft;
        }

        return YearEndProcess::create([
            'school_id' => $school->id,
            'source_academic_year' => $sourceYear,
            'target_academic_year' => $targetYear,
            'status' => 'draft',
            'summary' => $summary,
            'draft_payload' => $draftItems,
            'created_by' => $creator?->id,
        ]);
    }

    /**
     * Derive next logical class name based on grade progression.
     */
    private function deriveNextClassName(string $currentGrade, string $nextGrade, string $currentClass): string
    {
        // Example: If current grade is "Form 1" and next is "Form 2", replace "Form 1" with "Form 2" or "1" with "2"
        $currentNumber = preg_replace('/[^0-9]/', '', $currentGrade);
        $nextNumber = preg_replace('/[^0-9]/', '', $nextGrade);

        if ($currentNumber && $nextNumber) {
            if (str_contains($currentClass, $currentGrade)) {
                return str_replace($currentGrade, $nextGrade, $currentClass);
            }
            if (str_starts_with($currentClass, $currentNumber)) {
                return $nextNumber . substr($currentClass, strlen($currentNumber));
            }
        }

        return $currentClass;
    }

    /**
     * Evaluate live student clearance requirements across departments.
     */
    public function evaluateStudentClearance(Student $student): array
    {
        // 1. Finance: Check Invoices vs Paid/Credit notes
        $totalInvoiced = (float) Invoice::where('student_id', $student->id)->where('status', '!=', 'cancelled')->sum('total_amount');
        
        $totalPaid = (float) DB::table('payment_allocations')
            ->join('invoices', 'payment_allocations.invoice_id', '=', 'invoices.id')
            ->where('invoices.student_id', $student->id)
            ->sum('payment_allocations.amount');

        if ($totalPaid == 0) {
            $totalPaid = (float) Payment::where('student_id', $student->id)->where('status', 'completed')->sum('amount');
        }

        $totalCredits = (float) CreditNote::where('student_id', $student->id)->where('status', 'applied')->sum('applied_amount');
        if ($totalCredits == 0) {
            $totalCredits = (float) CreditNote::where('student_id', $student->id)->where('status', 'applied')->sum('total_amount');
        }

        $financeBalance = max(0, round($totalInvoiced - $totalPaid - $totalCredits, 2));
        $financeStatus = $financeBalance <= 0.009 ? 'cleared' : 'pending';

        // 2. Library: Unreturned books
        $unreturnedBooks = BorrowRecord::where('student_id', $student->id)
            ->whereNull('returned_at')
            ->count();
        $libraryStatus = $unreturnedBooks === 0 ? 'cleared' : 'pending';

        // 3. Assets: School assets allocated
        $unreturnedAssets = StudentAsset::where('student_id', $student->id)
            ->where('status', 'allocated')
            ->count();
        $assetsStatus = $unreturnedAssets === 0 ? 'cleared' : 'pending';

        // 4. Boarding / Hostel:
        $hasActiveBed = BedAssignment::where('student_id', $student->id)
            ->where('is_current', true)
            ->exists();
        $boardingStatus = !$student->is_boarding ? 'not_applicable' : ($hasActiveBed ? 'pending' : 'cleared');

        $isFullyCleared = ($financeStatus === 'cleared') &&
                          ($libraryStatus === 'cleared') &&
                          ($assetsStatus === 'cleared') &&
                          in_array($boardingStatus, ['cleared', 'not_applicable']);

        return [
            'finance_status' => $financeStatus,
            'finance_balance' => $financeBalance,
            'library_status' => $libraryStatus,
            'unreturned_books_count' => $unreturnedBooks,
            'assets_status' => $assetsStatus,
            'unreturned_assets_count' => $unreturnedAssets,
            'boarding_status' => $boardingStatus,
            'is_fully_cleared' => $isFullyCleared,
        ];
    }

    /**
     * Execute and apply an approved Year-End Transition batch.
     */
    public function executeTransitionBatch(YearEndProcess $process, User $adminUser): void
    {
        DB::transaction(function () use ($process, $adminUser) {
            $school = $process->school;
            $targetYear = $process->target_academic_year;
            $items = $process->draft_payload ?? [];

            foreach ($items as $item) {
                $student = Student::where('id', $item['student_id'])
                    ->where('school_id', $school->id)
                    ->first();

                if (!$student) {
                    continue;
                }

                $action = $item['proposed_action'] ?? 'promote';

                switch ($action) {
                    case 'promote':
                        $targetGrade = $item['target_grade'] ?? $student->grade;
                        $targetClassName = $item['target_class_name'] ?? $student->class_name;

                        // Find or create class for new academic year
                        $class = SchoolClass::firstOrCreate(
                            [
                                'school_id' => $school->id,
                                'grade' => $targetGrade,
                                'name' => $targetClassName,
                                'academic_year' => $targetYear,
                            ],
                            [
                                'term' => 'Term 1',
                            ]
                        );

                        // Update student record
                        $student->update([
                            'grade' => $targetGrade,
                            'class_name' => $targetClassName,
                            'status' => 'active',
                        ]);

                        // Create enrollment for target academic year
                        Enrollment::updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'student_id' => $student->id,
                                'academic_year' => $targetYear,
                            ],
                            [
                                'class_id' => $class->id,
                                'term' => 'Term 1',
                                'grade' => $targetGrade,
                                'class_name' => $targetClassName,
                                'is_boarding' => (bool) $student->is_boarding,
                                'has_transport' => (bool) $student->has_transport,
                                'student_type' => $student->is_boarding ? 'boarding' : 'day',
                                'enrollment_date' => now(),
                                'status' => 'active',
                            ]
                        );
                        break;

                    case 'repeat':
                    case 'retain':
                        // Retain student in current grade & class
                        $class = SchoolClass::firstOrCreate(
                            [
                                'school_id' => $school->id,
                                'grade' => $student->grade,
                                'name' => $student->class_name,
                                'academic_year' => $targetYear,
                            ],
                            [
                                'term' => 'Term 1',
                            ]
                        );

                        Enrollment::updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'student_id' => $student->id,
                                'academic_year' => $targetYear,
                            ],
                            [
                                'class_id' => $class->id,
                                'term' => 'Term 1',
                                'grade' => $student->grade,
                                'class_name' => $student->class_name,
                                'is_boarding' => (bool) $student->is_boarding,
                                'has_transport' => (bool) $student->has_transport,
                                'student_type' => $student->is_boarding ? 'boarding' : 'day',
                                'enrollment_date' => now(),
                                'status' => 'active',
                            ]
                        );
                        break;

                    case 'move_to_clearance':
                    case 'graduate':
                        // Move to pending clearance status
                        $student->update([
                            'status' => 'pending_clearance',
                            'exit_type' => 'graduated',
                        ]);

                        // Evaluate live checkpoints
                        $clearanceData = $this->evaluateStudentClearance($student);

                        StudentClearance::updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'student_id' => $student->id,
                                'academic_year' => $process->source_academic_year,
                            ],
                            [
                                'year_end_process_id' => $process->id,
                                'graduation_grade' => $student->grade,
                                'exit_type' => 'graduated',
                                'finance_status' => $clearanceData['finance_status'],
                                'finance_balance' => $clearanceData['finance_balance'],
                                'library_status' => $clearanceData['library_status'],
                                'unreturned_books_count' => $clearanceData['unreturned_books_count'],
                                'assets_status' => $clearanceData['assets_status'],
                                'unreturned_assets_count' => $clearanceData['unreturned_assets_count'],
                                'boarding_status' => $clearanceData['boarding_status'],
                                'status' => $clearanceData['is_fully_cleared'] ? 'fully_cleared' : 'pending_clearance',
                            ]
                        );
                        break;

                    case 'transfer_out':
                        $student->update([
                            'status' => 'transferred',
                            'exit_type' => 'transferred',
                            'exit_date' => now(),
                        ]);
                        break;

                    case 'exit_now':
                        $student->update([
                            'status' => 'graduated',
                            'exit_type' => 'graduated',
                            'exit_date' => now(),
                        ]);
                        break;
                }
            }

            // Mark process as executed
            $process->update([
                'status' => 'executed',
                'approved_by' => $adminUser->id,
                'executed_at' => now(),
            ]);
        });
    }

    /**
     * Finalize permanent exit and issue clearance certificate.
     */
    public function finalizeStudentExit(StudentClearance $clearance, User $adminUser, array $params = []): void
    {
        DB::transaction(function () use ($clearance, $adminUser, $params) {
            $student = $clearance->student;
            $school = $clearance->school;

            // Generate official certificate number if missing
            $certNumber = $clearance->certificate_number;
            if (!$certNumber) {
                $certNumber = 'CLR-' . strtoupper(substr($school->code ?? 'HHS', 0, 3)) . '-' . date('Y') . '-' . str_pad($clearance->id, 5, '0', STR_PAD_LEFT);
            }

            // Release any active bed assignments
            BedAssignment::where('student_id', $student->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'released_date' => now(),
                ]);

            // Update clearance record
            $clearance->update([
                'status' => 'permanently_exited',
                'certificate_number' => $certNumber,
                'exited_at' => now(),
                'exited_by' => $adminUser->id,
                'general_remarks' => $params['remarks'] ?? $clearance->general_remarks ?? 'Cleared all obligations and permanently exited.',
            ]);

            // Update student record
            $student->update([
                'status' => 'graduated',
                'exit_type' => $clearance->exit_type ?? 'graduated',
                'exit_date' => now()->toDateString(),
                'exit_remarks' => "Graduated and cleared under Certificate #{$certNumber}.",
            ]);
        });
    }
}
