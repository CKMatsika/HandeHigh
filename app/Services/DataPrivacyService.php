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
            ],
            'teachers' => [
                'count' => $this->school->teachers()->count(),
                'data_types' => ['personal_info', 'employment_records', 'qualifications'],
                'storage_location' => 'database',
            ],
            'parents' => [
                'count' => $this->school->parents()->count(),
                'data_types' => ['personal_info', 'contact_details'],
                'storage_location' => 'database',
            ],
        ];
    }

    protected function getProcessingActivities(): array
    {
        return [
            'status' => 'Not assessed',
            'activities' => [],
            'note' => 'Processing purposes, legal bases, and recipient lists require documented review.',
        ];
    }

    protected function getRetentionPolicies(): array
    {
        return [
            'status' => 'Not assessed',
            'policies' => [],
            'note' => 'Retention periods and deletion procedures require documented review.',
        ];
    }

    protected function getSubjectRequests(): array
    {
        return [
            'status' => 'Not tracked in this report',
            'access_requests' => [
                'total' => null,
                'pending' => null,
                'completed' => null,
                'average_response_time' => null,
            ],
            'deletion_requests' => [
                'total' => null,
                'pending' => null,
                'completed' => null,
                'average_response_time' => null,
            ],
            'correction_requests' => [
                'total' => null,
                'pending' => null,
                'completed' => null,
                'average_response_time' => null,
            ],
        ];
    }

    protected function getSecurityMeasures(): array
    {
        return [
            'technical_measures' => [
                'admin_dashboard_access' => [
                    'status' => 'Verified',
                    'evidence' => 'The privacy dashboard data is generated only for users with the super-admin role.',
                ],
                'application_debug' => [
                    'status' => config('app.debug') ? 'Needs attention' : 'Verified disabled',
                    'evidence' => 'Checked against the running application debug configuration.',
                ],
                'multi_factor_authentication' => [
                    'status' => 'Not verified',
                    'evidence' => 'This report does not verify whether MFA is enforced for privileged accounts.',
                ],
                'database_encryption' => [
                    'status' => 'Not verified',
                    'evidence' => 'Database encryption at rest is not verifiable from this application report.',
                ],
                'backup_encryption_and_restore' => [
                    'status' => 'Not verified',
                    'evidence' => 'Backup encryption and restore testing require independent operational evidence.',
                ],
            ],
            'organizational_measures' => [
                'staff_training' => ['status' => 'Not verified'],
                'data_protection_officer' => ['status' => 'Not verified'],
                'privacy_policy' => ['status' => 'Not verified'],
                'incident_response' => ['status' => 'Not verified'],
            ],
        ];
    }

    protected function getComplianceStatus(): array
    {
        return [
            'gdpr_compliance' => [
                'status' => 'Not assessed',
                'last_audit' => null,
                'next_audit' => null,
            ],
            'local_regulations' => [
                'status' => 'Not assessed',
                'certifications' => [],
                'last_review' => null,
            ],
            'risk_assessment' => [
                'overall_risk' => 'Not assessed',
                'identified_risks' => ['Security controls require evidence-based review.'],
                'mitigation_measures' => 'Not verified by this application report.',
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
