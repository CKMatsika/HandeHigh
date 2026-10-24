<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class Term1ReconciliationExportService
{
    public function snapshot(int $schoolId, string $year): array
    {
        if ($schoolId < 1 || ! preg_match('/^20\d{2}$/D', $year)) {
            throw new RuntimeException('An explicit school and academic year are required.');
        }

        return DB::transaction(function () use ($schoolId, $year) {
            // PostgreSQL supplies a coherent snapshot and enforces read-only SQL.
            // No migration, seeder, Eloquent observer or posting service is invoked.
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
            }
            $schools = DB::table('schools')->where('id', $schoolId)->get(['id', 'code', 'name', 'currency']);
            if ($schools->count() !== 1) {
                throw new RuntimeException('School code must resolve to exactly one tenant.');
            }
            $school = $schools->first();
            $schoolId = $school->id;
            $missing = [];
            $read = function (string $table, string $fields, ?string $parent = null, ?string $foreignKey = null) use ($schoolId, &$missing): array {
                if (! Schema::hasTable($table)) {
                    $missing[$table] = ['table_missing'];

                    return [];
                }
                $columns = Schema::getColumnListing($table);
                $wanted = explode(' ', $fields);
                $absent = array_values(array_diff($wanted, $columns));
                if ($absent) {
                    $missing[$table] = $absent;
                }
                $selected = array_values(array_intersect($wanted, $columns));
                if (! $selected) {
                    return [];
                }
                $query = DB::table($table);
                if ($parent) {
                    $query->whereIn($foreignKey, DB::table($parent)->where('school_id', $schoolId)->select('id'));
                    if (in_array('school_id', $columns, true)) {
                        $query->where(fn ($q) => $q->where('school_id', $schoolId)->orWhereNull('school_id'));
                    }
                    // Scope both ends of pivots, including malformed legacy links.
                    $otherParent = match ($table) {
                        'student_subjects' => ['subjects', 'subject_id'],
                        'guardian_student' => ['guardians', 'guardian_id'],
                        'payment_allocations' => ['invoices', 'invoice_id'],
                        'journal_entries' => ['accounts', 'account_id'],
                        default => null,
                    };
                    if ($otherParent) {
                        $query->whereIn($otherParent[1], DB::table($otherParent[0])->where('school_id', $schoolId)->select('id'));
                    }
                } else {
                    if (! in_array('school_id', $columns, true)) {
                        throw new RuntimeException('Tenant ownership column is unavailable.');
                    }
                    $query->where('school_id', $schoolId);
                }

                return $query->orderBy(in_array('id', $columns, true) ? 'id' : $foreignKey)->get($selected)->all();
            };
            // Explicit field lists exclude passwords, tokens, connection settings,
            // bank account numbers, birth dates, contacts and document payloads.
            $tables = [
                'students' => $read('students', 'id school_id first_name last_name other_names admission_number registration_number grade class_name is_boarding funding_status has_transport status exit_type exit_date updated_at'),
                'enrollments' => $read('enrollments', 'id school_id student_id class_id academic_year term grade class_name is_boarding student_type has_transport enrollment_date status updated_at'),
                'classes' => $read('classes', 'id school_id name grade academic_year'),
                'subjects' => $read('subjects', 'id school_id name code'),
                'student_subjects' => $read('student_subjects', 'id student_id subject_id academic_year term is_active', 'students', 'student_id'),
                'guardian_student' => $read('guardian_student', 'student_id guardian_id relationship is_primary', 'students', 'student_id'),
                'fee_structures' => $read('fee_structures', 'id school_id academic_year term grade parent_id residency_type vote_code allocation_priority category code label amount is_optional subject_name service_type revenue_account_id project_id cost_center_id'),
                'invoices' => $read('invoices', 'id school_id student_id enrollment_id number type academic_year term issued_at due_date total_amount balance status updated_at'),
                'invoice_items' => $read('invoice_items', 'id school_id invoice_id fee_structure_id vote_code description category quantity unit_amount line_total', 'invoices', 'invoice_id'),
                'payments' => $read('payments', 'id school_id student_id reference mopse_receipt_number amount paid_at status is_advance advance_balance'),
                'payment_allocations' => $read('payment_allocations', 'id payment_id invoice_id invoice_item_id vote_code amount', 'payments', 'payment_id'),
                'credit_notes' => $read('credit_notes', 'id school_id student_id invoice_id credit_note_number credit_note_date type total_amount applied_amount balance status'),
                'student_scholarships' => $read('student_scholarships', 'id school_id student_id reference name academic_year funding_source funding_sponsor_id coverage_type coverage_basis coverage_value covered_votes starts_on ends_on accounting_treatment status approved_at'),
                'scholarship_applications' => $read('scholarship_applications', 'id school_id student_scholarship_id invoice_id amount lines accounting_date status journal_batch_id reversal_batch_id reversed_on'),
                'sponsor_claims' => $read('sponsor_claims', 'id school_id funding_sponsor_id sponsor_claim_batch_id student_id invoice_id academic_year reference approved_amount recognised_amount received_before_cutover opening_outstanding disputed_amount rejected_amount classification opening_inclusion status accounting_date allocated_lines journal_batch_id opening_journal_batch_id reversed_on'),
                'accounts' => $read('accounts', 'id school_id code name type currency is_active'),
                'account_mappings' => $read('account_mappings', 'id school_id mapping_type source_key account_id is_active'),
                'journal_batches' => $read('journal_batches', 'id school_id batch_number transaction_date source_type source_id status'),
                'journal_entries' => $read('journal_entries', 'id journal_batch_id account_id entry_type amount reference', 'journal_batches', 'journal_batch_id'),
            ];
            foreach (['students', 'enrollments', 'fee_structures', 'invoices'] as $required) {
                if (isset($missing[$required]) && in_array('table_missing', $missing[$required], true)) {
                    throw new RuntimeException('Required reconciliation schema is unavailable.');
                }
            }

            return ['format' => 'hande-term1-reconciliation-v1', 'exported_at' => now()->toIso8601String(),
                'database_driver' => DB::getDriverName(), 'school' => $school, 'academic_year' => $year,
                'scope' => 'All tenant students including inactive; all available enrolment and invoice history. Filter target year/Term 1 during reconciliation.',
                'missing_schema_fields' => $missing, 'counts' => array_map('count', $tables), 'data' => $tables];
        });
    }
}
