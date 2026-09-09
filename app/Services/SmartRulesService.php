<?php

namespace App\Services;

use App\Models\School;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Assessment;
use App\Models\Result;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Cache;

class SmartRulesService
{
    protected $school;
    protected $settings;

    public function __construct(School $school)
    {
        $this->school = $school;
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $this->settings = Cache::remember(
            "school_settings_{$this->school->id}",
            3600,
            function () {
                return $this->school->settings()->get()->pluck('typed_value', 'key')->toArray();
            }
        );
    }

    public function canAccessExams(Student $student): array
    {
        $result = [
            'allowed' => true,
            'reason' => null,
            'restrictions' => [],
        ];

        // Check fee payment restrictions
        if ($this->isSettingEnabled('fees.restrict_exam_access')) {
            $outstandingBalance = $this->getStudentOutstandingBalance($student);
            $threshold = $this->getSetting('fees.exam_access_threshold', 0);

            if ($outstandingBalance > $threshold) {
                $result['allowed'] = false;
                $result['reason'] = "Outstanding fee balance of {$outstandingBalance} exceeds threshold of {$threshold}";
                $result['restrictions'][] = 'fee_payment';
            }
        }

        // Check attendance requirements
        if ($this->isSettingEnabled('academic.exam_attendance_requirement')) {
            $requiredAttendance = $this->getSetting('academic.exam_min_attendance', 75);
            $studentAttendance = $this->getStudentAttendance($student);

            if ($studentAttendance < $requiredAttendance) {
                $result['allowed'] = false;
                $result['reason'] = "Attendance rate of {$studentAttendance}% is below required {$requiredAttendance}%";
                $result['restrictions'][] = 'attendance';
            }
        }

        // Check disciplinary restrictions
        if ($this->isSettingEnabled('disciplinary.restrict_exam_access')) {
            if ($this->hasActiveDisciplinaryAction($student, 'exam')) {
                $result['allowed'] = false;
                $result['reason'] = "Active disciplinary restrictions prevent exam access";
                $result['restrictions'][] = 'disciplinary';
            }
        }

        return $result;
    }

    public function canViewResults(Student $student): array
    {
        $result = [
            'allowed' => true,
            'reason' => null,
            'restrictions' => [],
        ];

        // Check fee payment restrictions
        if ($this->isSettingEnabled('fees.restrict_result_access')) {
            $outstandingBalance = $this->getStudentOutstandingBalance($student);
            $threshold = $this->getSetting('fees.result_access_threshold', 0);

            if ($outstandingBalance > $threshold) {
                $result['allowed'] = false;
                $result['reason'] = "Outstanding fee balance of {$outstandingBalance} prevents result access";
                $result['restrictions'][] = 'fee_payment';
            }
        }

        // Check if results are published
        if ($this->isSettingEnabled('academic.result_publishing_control')) {
            $publishDelay = $this->getSetting('academic.result_publish_delay_days', 0);
            $latestResult = $student->results()->latest()->first();

            if ($latestResult && $latestResult->created_at->diffInDays(now()) < $publishDelay) {
                $result['allowed'] = false;
                $result['reason'] = "Results will be available after {$publishDelay} days";
                $result['restrictions'][] = 'publish_delay';
            }
        }

        return $result;
    }

    public function canRegisterNextTerm(Student $student): array
    {
        $result = [
            'allowed' => true,
            'reason' => null,
            'restrictions' => [],
        ];

        // Check fee clearance
        if ($this->isSettingEnabled('fees.restrict_next_term_registration')) {
            $outstandingBalance = $this->getStudentOutstandingBalance($student);
            $threshold = $this->getSetting('fees.registration_threshold', 0);

            if ($outstandingBalance > $threshold) {
                $result['allowed'] = false;
                $result['reason'] = "Clear outstanding balance of {$outstandingBalance} to register for next term";
                $result['restrictions'][] = 'fee_payment';
            }
        }

        // Check academic performance requirements
        if ($this->isSettingEnabled('academic.registration_performance_requirement')) {
            $minGPA = $this->getSetting('academic.registration_min_gpa', 50);
            $studentGPA = $this->getStudentGPA($student);

            if ($studentGPA < $minGPA) {
                $result['allowed'] = false;
                $result['reason'] = "GPA of {$studentGPA} is below required minimum of {$minGPA}";
                $result['restrictions'][] = 'academic_performance';
            }
        }

        // Check disciplinary status
        if ($this->isSettingEnabled('disciplinary.restrict_registration')) {
            if ($this->hasActiveDisciplinaryAction($student, 'registration')) {
                $result['allowed'] = false;
                $result['reason'] = "Active disciplinary restrictions prevent registration";
                $result['restrictions'][] = 'disciplinary';
            }
        }

        return $result;
    }

    public function applyLatePaymentPenalty(Invoice $invoice): bool
    {
        if (!$this->isSettingEnabled('fees.late_payment_penalties')) {
            return false;
        }

        $gracePeriod = $this->getSetting('fees.late_payment_grace_days', 0);
        $penaltyRate = $this->getSetting('fees.late_payment_penalty_rate', 0);
        $maxPenalty = $this->getSetting('fees.max_late_penalty_percentage', 20);

        if ($invoice->due_date->addDays($gracePeriod)->isPast() && $invoice->balance > 0) {
            $daysLate = $invoice->due_date->diffInDays(now());
            $penaltyAmount = ($invoice->amount * $penaltyRate / 100) * min($daysLate, 30);
            $maxPenaltyAmount = $invoice->amount * $maxPenalty / 100;

            $penaltyAmount = min($penaltyAmount, $maxPenaltyAmount);

            if ($penaltyAmount > 0) {
                $invoice->update([
                    'penalty_amount' => $invoice->penalty_amount + $penaltyAmount,
                    'balance' => $invoice->balance + $penaltyAmount,
                ]);

                // Log the penalty application
                $this->logPenaltyApplication($invoice, $penaltyAmount, $daysLate);

                return true;
            }
        }

        return false;
    }

    public function getStudentRestrictions(Student $student): array
    {
        return [
            'exams' => $this->canAccessExams($student),
            'results' => $this->canViewResults($student),
            'registration' => $this->canRegisterNextTerm($student),
            'library' => $this->canAccessLibrary($student),
            'transport' => $this->canAccessTransport($student),
        ];
    }

    protected function canAccessLibrary(Student $student): array
    {
        $result = ['allowed' => true, 'reason' => null, 'restrictions' => []];

        if ($this->isSettingEnabled('library.fee_based_access')) {
            $outstandingBalance = $this->getStudentOutstandingBalance($student);
            $threshold = $this->getSetting('library.access_fee_threshold', 0);

            if ($outstandingBalance > $threshold) {
                $result['allowed'] = false;
                $result['reason'] = "Library access restricted due to outstanding fees";
                $result['restrictions'][] = 'fee_payment';
            }
        }

        return $result;
    }

    protected function canAccessTransport(Student $student): array
    {
        $result = ['allowed' => true, 'reason' => null, 'restrictions' => []];

        if ($this->isSettingEnabled('transport.fee_based_access')) {
            $outstandingBalance = $this->getStudentOutstandingBalance($student);
            $threshold = $this->getSetting('transport.access_fee_threshold', 0);

            if ($outstandingBalance > $threshold) {
                $result['allowed'] = false;
                $result['reason'] = "Transport services restricted due to outstanding fees";
                $result['restrictions'][] = 'fee_payment';
            }
        }

        return $result;
    }

    // Helper methods
    protected function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    protected function isSettingEnabled(string $key): bool
    {
        return (bool) ($this->settings[$key] ?? false);
    }

    protected function getStudentOutstandingBalance(Student $student): float
    {
        return $student->invoices()
            ->where('status', '!=', 'paid')
            ->sum('balance');
    }

    protected function getStudentAttendance(Student $student): float
    {
        // Placeholder implementation - would integrate with attendance system
        return 85.0;
    }

    protected function getStudentGPA(Student $student): float
    {
        return $student->results()->avg('score') ?? 0;
    }

    protected function hasActiveDisciplinaryAction(Student $student, string $type): bool
    {
        // Placeholder implementation - would integrate with disciplinary system
        return false;
    }

    protected function logPenaltyApplication(Invoice $invoice, float $penaltyAmount, int $daysLate): void
    {
        // Log penalty application for audit trail
        \Log::info("Late payment penalty applied", [
            'invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'penalty_amount' => $penaltyAmount,
            'days_late' => $daysLate,
            'school_id' => $this->school->id,
        ]);
    }

    public static function getDefaultSettings(): array
    {
        return [
            // Fee settings
            'fees.restrict_exam_access' => false,
            'fees.exam_access_threshold' => 0,
            'fees.restrict_result_access' => false,
            'fees.result_access_threshold' => 0,
            'fees.restrict_next_term_registration' => true,
            'fees.registration_threshold' => 0,
            'fees.late_payment_penalties' => true,
            'fees.late_payment_grace_days' => 7,
            'fees.late_payment_penalty_rate' => 2,
            'fees.max_late_penalty_percentage' => 20,

            // Academic settings
            'academic.exam_attendance_requirement' => false,
            'academic.exam_min_attendance' => 75,
            'academic.result_publishing_control' => false,
            'academic.result_publish_delay_days' => 0,
            'academic.registration_performance_requirement' => false,
            'academic.registration_min_gpa' => 50,

            // Library settings
            'library.fee_based_access' => false,
            'library.access_fee_threshold' => 0,

            // Transport settings
            'transport.fee_based_access' => false,
            'transport.access_fee_threshold' => 0,

            // Disciplinary settings
            'disciplinary.restrict_exam_access' => false,
            'disciplinary.restrict_registration' => false,
        ];
    }
}
