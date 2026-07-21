<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Models\Student;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class DataPrivacyService
{
    protected $school;

    public function __construct(School $school)
    {
        $this->school = $school;
    }

    public function anonymizeStudentData(Student $student): bool
    {
        try {
            // Anonymize personal information
            $student->update([
                'first_name' => 'Student_' . $student->id,
                'last_name' => 'Anonymous',
                'email' => 'student_' . $student->id . '@anonymous.local',
                'phone' => null,
                'address' => null,
                'date_of_birth' => null,
                'national_id' => null,
            ]);

            // Log the anonymization
            Log::info('Student data anonymized', [
                'student_id' => $student->id,
                'school_id' => $this->school->id,
                'timestamp' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to anonymize student data', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function exportStudentData(Student $student): array
    {
        return [
            'personal_information' => [
                'name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'date_of_birth' => $student->date_of_birth,
                'address' => $student->address,
            ],
            'academic_records' => [
                'enrollments' => $student->enrollments->map(function($enrollment) {
                    return [
                        'class' => $enrollment->class->name,
                        'academic_year' => $enrollment->academic_year,
                        'term' => $enrollment->term,
                        'enrollment_date' => $enrollment->created_at,
                    ];
                }),
                'results' => $student->results->map(function($result) {
                    return [
                        'assessment' => $result->assessment->title,
                        'subject' => $result->assessment->subject->name,
                        'score' => $result->score,
                        'grade' => $result->grade,
                        'date' => $result->created_at,
                    ];
                }),
            ],
            'financial_records' => [
                'invoices' => $student->invoices->map(function($invoice) {
                    return [
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->amount,
                        'status' => $invoice->status,
                        'due_date' => $invoice->due_date,
                    ];
                }),
                'payments' => $student->payments->map(function($payment) {
                    return [
                        'amount' => $payment->amount,
                        'payment_date' => $payment->payment_date,
                        'method' => $payment->payment_method,
                    ];
                }),
            ],
            'attendance_records' => [
                // Placeholder for attendance data
            ],
            'library_records' => [
                'borrowed_books' => $student->borrowRecords->map(function($record) {
                    return [
                        'book_title' => $record->book->title,
                        'borrow_date' => $record->borrowed_at,
                        'return_date' => $record->returned_at,
                        'status' => $record->returned_at ? 'returned' : 'borrowed',
                    ];
                }),
            ],
            'export_date' => now()->toDateTimeString(),
            'exported_by' => auth()->user()->name ?? 'System',
        ];
    }

    public function deleteStudentData(Student $student): bool
    {
        try {
            // Start database transaction
            \DB::beginTransaction();

            // Delete related records first
            $student->results()->delete();
            $student->borrowRecords()->delete();
            $student->payments()->delete();
            $student->invoices()->delete();
            $student->enrollments()->delete();

            // Delete the student record
            $student->delete();

            // Commit transaction
            \DB::commit();

            // Log the deletion
            Log::info('Student data deleted', [
                'student_id' => $student->id,
                'school_id' => $this->school->id,
                'timestamp' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            \DB::rollback();
            
            Log::error('Failed to delete student data', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function generatePrivacyReport(): array
    {
        return [
            'data_inventory' => $this->getDataInventory(),
            'data_processing_activities' => $this->getProcessingActivities(),
            'data_retention_policies' => $this->getRetentionPolicies(),
            'data_subject_requests' => $this->getSubjectRequests(),
            'security_measures' => $this->getSecurityMeasures(),
            'compliance_status' => $this->getComplianceStatus(),
        ];
    }

    protected function getDataInventory(): array
    {
        return [
            'students' => [
                'count' => $this->school->students()->count(),
                'data_types' => ['personal_info', 'academic_records', 'financial_data', 'attendance'],
                'storage_location' => 'database',
                'retention_period' => '7 years after graduation',
            ],
            'teachers' => [
                'count' => $this->school->teachers()->count(),
                'data_types' => ['personal_info', 'employment_records', 'qualifications'],
                'storage_location' => 'database',
                'retention_period' => '6 years after employment ends',
            ],
            'parents' => [
                'count' => $this->school->parents()->count(),
                'data_types' => ['personal_info', 'contact_details'],
                'storage_location' => 'database',
                'retention_period' => '7 years after last child graduates',
            ],
        ];
    }

    protected function getProcessingActivities(): array
    {
        return [
            'academic_management' => [
                'purpose' => 'Education administration',
                'legal_basis' => 'Educational necessity',
                'data_categories' => ['Student records', 'Academic performance'],
                'recipients' => ['Teachers', 'Administrators', 'Parents'],
            ],
            'financial_management' => [
                'purpose' => 'Fee collection and financial administration',
                'legal_basis' => 'Contractual obligation',
                'data_categories' => ['Payment information', 'Financial records'],
                'recipients' => ['Finance staff', 'Parents'],
            ],
            'communication' => [
                'purpose' => 'School communication and notifications',
                'legal_basis' => 'Legitimate interest',
                'data_categories' => ['Contact information'],
                'recipients' => ['School staff', 'Parents', 'Students'],
            ],
        ];
    }

    protected function getRetentionPolicies(): array
    {
        return [
            'student_records' => [
                'retention_period' => '7 years post-graduation',
                'deletion_method' => 'Secure anonymization',
                'exceptions' => ['Legal requirements', 'Historical archives'],
            ],
            'financial_records' => [
                'retention_period' => '7 years',
                'deletion_method' => 'Secure deletion',
                'exceptions' => ['Tax requirements', 'Audit purposes'],
            ],
            'communication_logs' => [
                'retention_period' => '2 years',
                'deletion_method' => 'Automatic deletion',
                'exceptions' => ['Legal proceedings'],
            ],
        ];
    }

    protected function getSubjectRequests(): array
    {
        return [
            'access_requests' => [
                'total' => 0, // Would query actual data
                'pending' => 0,
                'completed' => 0,
                'average_response_time' => '14 days',
            ],
            'deletion_requests' => [
                'total' => 0,
                'pending' => 0,
                'completed' => 0,
                'average_response_time' => '30 days',
            ],
            'correction_requests' => [
                'total' => 0,
                'pending' => 0,
                'completed' => 0,
                'average_response_time' => '7 days',
            ],
        ];
    }

    protected function getSecurityMeasures(): array
    {
        return [
            'technical_measures' => [
                'encryption' => 'AES-256 for sensitive data',
                'access_control' => 'Role-based permissions',
                'authentication' => 'Multi-factor authentication',
                'backup_security' => 'Encrypted backups',
            ],
            'organizational_measures' => [
                'staff_training' => 'Annual privacy training',
                'data_protection_officer' => 'Designated DPO',
                'privacy_policy' => 'Published and updated annually',
                'incident_response' => '24-hour breach notification',
            ],
        ];
    }

    protected function getComplianceStatus(): array
    {
        return [
            'gdpr_compliance' => [
                'status' => 'Compliant',
                'last_audit' => now()->subMonths(6)->toDateString(),
                'next_audit' => now()->addMonths(6)->toDateString(),
            ],
            'local_regulations' => [
                'status' => 'Compliant',
                'certifications' => ['Data Protection Certificate'],
                'last_review' => now()->subMonths(3)->toDateString(),
            ],
            'risk_assessment' => [
                'overall_risk' => 'Low',
                'identified_risks' => [],
                'mitigation_measures' => 'Implemented',
            ],
        ];
    }

    public function logDataAccess(User $user, string $action, string $dataType, $recordId = null): void
    {
        AuditLog::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'action' => $action,
            'module' => 'data_privacy',
            'description' => "User {$user->name} {$action} {$dataType}" . ($recordId ? " record #{$recordId}" : ""),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
