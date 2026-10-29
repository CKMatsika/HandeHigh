<?php

namespace App\Services\Academic;

use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentReconciliationBatch;
use App\Models\StudentReconciliationFinding;
use App\Models\StudentReconciliationRow;
use App\Models\StudentReconciliationSource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StudentReconciliationService
{
    private array $pendingFindings = [];

    public function __construct(private StudentReconciliationWorkbookParser $parser) {}

    /** Compare uploaded evidence against one school and a selected academic period. Never changes ERP student or financial records. */
    public function createBatch(int $schoolId, int $userId, array $options, array $files): StudentReconciliationBatch
    {
        $this->pendingFindings = [];
        if (count($files) < 1 || count($files) > 8) throw new InvalidArgumentException('Upload between one and eight source files in a reconciliation batch.');

        $parsed = [];
        $totalRows = 0;
        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) throw new InvalidArgumentException('One of the selected source files is invalid.');
            $sourceType = $options['source_types'][$index] ?? null;
            if (! in_array($sourceType, ['student_register', 'class_register', 'invoice_register'], true)) throw new InvalidArgumentException('Select a valid source type for each uploaded file.');
            $workbook = $this->parser->parse($file, $sourceType);
            $totalRows += count($workbook['rows']);
            if ($totalRows > 10000) throw new InvalidArgumentException('A reconciliation batch may contain at most 10,000 student rows. Split larger sets into separate batches.');
            $parsed[] = ['file' => $file, 'type' => $sourceType, ...$workbook];
        }

        return DB::transaction(function () use ($schoolId, $userId, $options, $parsed, $totalRows) {
            $batch = StudentReconciliationBatch::create([
                'school_id' => $schoolId, 'created_by' => $userId,
                'academic_year' => (string) $options['academic_year'], 'term' => (string) $options['term'],
                'complete_register' => (bool) ($options['complete_register'] ?? false),
                'complete_invoice_register' => (bool) ($options['complete_invoice_register'] ?? false),
                'status' => 'processing', 'source_count' => count($parsed), 'source_rows_count' => $totalRows,
            ]);

            $sources = [];
            foreach ($parsed as $sourceData) {
                $source = StudentReconciliationSource::create([
                    'batch_id' => $batch->id, 'source_type' => $sourceData['type'],
                    'original_filename' => Str::limit(basename($sourceData['file']->getClientOriginalName()), 255, ''),
                    'header_row' => $sourceData['header_row'], 'rows_read' => count($sourceData['rows']),
                    'recognized_columns' => $sourceData['recognized_columns'],
                ]);
                $sources[] = ['record' => $source, 'type' => $sourceData['type'], 'rows' => $sourceData['rows']];
            }

            $students = Student::where('school_id', $schoolId)->get();
            $byId = $students->keyBy('id');
            $byAdmission = $this->indexBy($students, 'admission_number');
            $byRegistration = $this->indexBy($students, 'registration_number');
            $byName = [];
            foreach ($students as $student) {
                $key = StudentReconciliationWorkbookParser::nameKey($this->studentName($student));
                if ($key !== '') $byName[$key][] = $student;
            }

            $enrollments = Enrollment::where('school_id', $schoolId)
                ->where('academic_year', (string) $options['academic_year'])->with('class')->get()
                ->filter(fn ($enrollment) => StudentReconciliationWorkbookParser::normalizeTerm($enrollment->term) === StudentReconciliationWorkbookParser::normalizeTerm($options['term']))
                ->groupBy('student_id');
            $invoices = Invoice::where('school_id', $schoolId)->where('academic_year', (string) $options['academic_year'])
                ->whereNotIn(DB::raw('LOWER(status)'), ['cancelled', 'canceled', 'void', 'reversed'])->get()
                ->filter(fn ($invoice) => StudentReconciliationWorkbookParser::normalizeTerm($invoice->term) === StudentReconciliationWorkbookParser::normalizeTerm($options['term']))
                ->groupBy('student_id');

            $sourceStudentIds = [];
            $invoiceSourceStudentIds = [];
            $invoiceSourceReferences = [];
            $rowGroups = [];
            $matchedRows = 0;
            $rowsToPersist = [];

            foreach ($sources as $source) {
                $seenInFile = [];
                foreach ($source['rows'] as $data) {
                    $match = $this->match($data, $byId, $byAdmission, $byRegistration, $byName);
                    $studentId = $match['student']?->id;
                    $row = new StudentReconciliationRow([
                        'batch_id' => $batch->id, 'source_id' => $source['record']->id,
                        'source_row_number' => $data['source_row_number'], 'normalized_data' => $data,
                        'identity_key' => $data['identity_name_key'] !== '' ? hash('sha256', $data['identity_name_key']) : null,
                        'match_status' => $match['status'], 'match_confidence' => $match['confidence'],
                        'matched_student_id' => $studentId, 'candidate_student_ids' => $match['candidate_ids'] ?: null,
                    ]);
                    $rowsToPersist[] = $row;

                    if ($match['status'] === 'matched' && $studentId) {
                        $matchedRows++;
                        $sourceStudentIds[$studentId] = true;
                        $rowGroups[$studentId][] = ['row' => $row, 'data' => $data, 'source_type' => $source['type']];
                        if ($source['type'] === 'invoice_register') {
                            if (! empty($data['invoice_number'])) $invoiceSourceReferences[$studentId.':'.StudentReconciliationWorkbookParser::normalizeText($data['invoice_number'])] = true;
                            else $invoiceSourceStudentIds[$studentId] = true;
                        }
                        foreach ([
                            'erp_student_id' => [(string) $studentId, 'erp_id_mismatch', 'ERP student ID'],
                            'admission_number' => [(string) ($match['student']->admission_number ?? ''), 'admission_number_mismatch', 'admission number'],
                            'registration_number' => [(string) ($match['student']->registration_number ?? ''), 'registration_number_mismatch', 'registration number'],
                        ] as $field => [$erpValue, $code, $label]) {
                            if (! empty($data[$field]) && $this->identifier((string) $data[$field]) !== $this->identifier($erpValue)) {
                                $this->finding($batch, $row, $studentId, null, $code, 'critical', $label, $data[$field], $erpValue, 'The source identifier differs from the matched ERP student record. Verify the identity before relying on other comparisons.');
                            }
                        }
                    } elseif ($match['status'] === 'needs_verification') {
                        if (count($match['candidate_ids']) === 1) $sourceStudentIds[$match['candidate_ids'][0]] = true;
                        $this->finding($batch, $row, $match['student']?->id, null, 'identity_needs_verification', 'warning', 'student_identity', $data['full_name'] ?? null, $match['student'] ? $this->studentName($match['student']) : null,
                            'Possible match based on names or supporting details only. Verify the student identity before relying on the comparisons.');
                    } else {
                        $this->finding($batch, $row, null, null, $match['status'] === 'ambiguous' ? 'ambiguous_student_identity' : 'student_not_found', 'critical', 'student_identity', $data['full_name'] ?? $data['admission_number'] ?? null, null,
                            $match['status'] === 'ambiguous' ? 'The supplied identifiers point to multiple ERP students. Manual identity verification is required.' : 'No matching student was found in this school. This report does not create a student record.');
                    }

                    if ($studentId && $match['status'] === 'matched') {
                        $duplicateKey = $studentId.':'.$source['record']->id;
                        if (isset($seenInFile[$duplicateKey])) {
                            $this->finding($batch, $row, $studentId, null, 'duplicate_student_row_in_source', 'warning', 'student', $data['full_name'] ?? null, $this->studentName($match['student']), 'This source file lists the same ERP student more than once.');
                        }
                        $seenInFile[$duplicateKey] = true;
                        $this->compareEnrollment($batch, $row, $data, $match['student'], $enrollments->get($studentId, collect())->values());
                        $this->compareInvoices($batch, $row, $data, $source['type'], $match['student'], $invoices->get($studentId, collect())->values(), (string) $options['academic_year'], (string) $options['term']);
                    }

                    if (! empty($data['academic_year']) && (string) $data['academic_year'] !== (string) $options['academic_year']) {
                        $this->finding($batch, $row, $studentId, null, 'source_academic_year_differs', 'warning', 'academic_year', $data['academic_year'], $options['academic_year'], 'The source row names a different academic year from this reconciliation batch.');
                    }
                    if (! empty($data['term']) && StudentReconciliationWorkbookParser::normalizeTerm($data['term']) !== StudentReconciliationWorkbookParser::normalizeTerm($options['term'])) {
                        $this->finding($batch, $row, $studentId, null, 'source_term_differs', 'warning', 'term', $data['term'], $options['term'], 'The source row names a different term from this reconciliation batch.');
                    }
                }
            }

            $this->compareCrossSource($batch, $rowGroups);
            if ($batch->complete_register) $this->reportMissingStudents($batch, $schoolId, $enrollments, $sourceStudentIds);
            if ($batch->complete_invoice_register && collect($sources)->contains(fn ($source) => $source['type'] === 'invoice_register')) {
                $this->reportInvoicesMissingFromSource($batch, $invoices, $invoiceSourceStudentIds, $invoiceSourceReferences);
            }

            $this->persistRowsAndFindings($batch, $rowsToPersist);
            $findings = collect($this->pendingFindings);
            $summary = [
                'critical' => $findings->where('severity', 'critical')->count(),
                'warning' => $findings->where('severity', 'warning')->count(),
                'info' => $findings->where('severity', 'info')->count(),
                'open' => $findings->where('status', 'open')->count(),
                'invoice_findings' => $findings->filter(fn ($f) => str_starts_with($f['code'], 'invoice_') || str_starts_with($f['code'], 'erp_invoice_'))->count(),
                'identity_findings' => $findings->filter(fn ($f) => str_contains($f['code'], 'identity') || $f['code'] === 'student_not_found')->count(),
                'placement_findings' => $findings->filter(fn ($f) => in_array($f['code'], ['grade_mismatch', 'class_mismatch', 'residency_mismatch', 'enrollment_missing', 'duplicate_erp_enrollment', 'cross_source_conflict'], true))->count(),
            ];
            $batch->update(['status' => 'completed', 'matched_rows_count' => $matchedRows, 'open_findings_count' => $summary['open'], 'summary' => $summary]);

            AuditLog::create([
                'school_id' => $schoolId, 'user_id' => $userId, 'action' => 'student_reconciliation_created',
                'module' => 'academic', 'model_type' => StudentReconciliationBatch::class, 'model_id' => $batch->id,
                'new_values' => ['academic_year' => $batch->academic_year, 'term' => $batch->term, 'source_count' => $batch->source_count, 'source_rows' => $totalRows, 'summary' => $summary],
                'description' => 'Read-only student, enrolment and invoice reconciliation completed. No ERP student or financial records were modified.',
            ]);

            return $batch->fresh();
        });
    }

    private function match(array $data, $byId, array $byAdmission, array $byRegistration, array $byName): array
    {
        $stable = [];
        if (! empty($data['erp_student_id']) && isset($byId[(int) $data['erp_student_id']])) $stable[] = $byId[(int) $data['erp_student_id']];
        if (! empty($data['admission_number']) && isset($byAdmission[$this->identifier($data['admission_number'])])) $stable[] = $byAdmission[$this->identifier($data['admission_number'])];
        if (! empty($data['registration_number']) && isset($byRegistration[$this->identifier($data['registration_number'])])) $stable[] = $byRegistration[$this->identifier($data['registration_number'])];
        $stableIds = collect($stable)->pluck('id')->unique()->values();
        if ($stableIds->count() === 1) return ['student' => $byId[$stableIds[0]], 'status' => 'matched', 'confidence' => 'stable_identifier', 'candidate_ids' => []];
        if ($stableIds->count() > 1) return ['student' => null, 'status' => 'ambiguous', 'confidence' => 'conflicting_identifiers', 'candidate_ids' => $stableIds->all()];

        $nameKey = $data['identity_name_key'] ?? '';
        $candidates = $nameKey !== '' ? collect($byName[$nameKey] ?? []) : collect();
        if (! empty($data['date_of_birth'])) {
            $candidates = $candidates->filter(fn ($student) => $student->date_of_birth?->format('Y-m-d') === $data['date_of_birth']);
        }
        if ($candidates->count() === 1) {
            $candidate = $candidates->first();
            if (! empty($data['date_of_birth'])) return ['student' => $candidate, 'status' => 'needs_verification', 'confidence' => 'name_and_birth_date', 'candidate_ids' => [$candidate->id]];
            return ['student' => null, 'status' => 'needs_verification', 'confidence' => 'unique_name_only', 'candidate_ids' => [$candidate->id]];
        }
        if ($candidates->count() > 1) return ['student' => null, 'status' => 'ambiguous', 'confidence' => 'duplicate_name', 'candidate_ids' => $candidates->pluck('id')->all()];
        return ['student' => null, 'status' => 'unmatched', 'confidence' => 'none', 'candidate_ids' => []];
    }

    private function compareEnrollment(StudentReconciliationBatch $batch, StudentReconciliationRow $row, array $data, Student $student, $enrollments): void
    {
        if ($enrollments->count() > 1) {
            $this->finding($batch, $row, $student->id, null, 'duplicate_erp_enrollment', 'critical', 'enrollment', (string) $enrollments->count(), '1 expected', 'More than one ERP enrolment exists for this student in the selected academic year and term.');
        }
        $enrollment = $enrollments->sortByDesc('id')->first();
        if (! $enrollment) {
            if (! empty($data['grade']) || ! empty($data['class_name'])) $this->finding($batch, $row, $student->id, null, 'enrollment_missing', 'warning', 'enrollment', trim(($data['grade'] ?? '').' / '.($data['class_name'] ?? '')), null, 'The source contains class placement but the ERP has no matching enrolment for the selected period.');
            return;
        }

        $erpGrade = trim((string) ($enrollment->grade ?? $enrollment->class?->grade ?? ''));
        $erpClass = trim((string) ($enrollment->class?->name ?? $enrollment->class_name ?? ''));
        $classGrade = trim((string) ($enrollment->class?->grade ?? ''));
        if ($classGrade !== '' && $enrollment->grade && $this->gradeKey((string) $enrollment->grade) !== $this->gradeKey($classGrade)) {
            $this->finding($batch, $row, $student->id, null, 'erp_class_grade_mapping_mismatch', 'critical', 'ERP grade/class mapping', (string) $enrollment->grade, $classGrade, 'The ERP enrolment grade differs from the grade configured on its linked class record.');
        }
        if (! empty($data['grade']) && $this->gradeKey($data['grade']) !== $this->gradeKey($erpGrade)) {
            $this->finding($batch, $row, $student->id, null, 'grade_mismatch', 'critical', 'grade', $data['grade'], $erpGrade, 'The source grade/form differs from the ERP enrolment for the selected period.');
        }
        if (! empty($data['class_name']) && StudentReconciliationWorkbookParser::normalizeText($data['class_name']) !== StudentReconciliationWorkbookParser::normalizeText($erpClass)) {
            $this->finding($batch, $row, $student->id, null, 'class_mismatch', 'critical', 'class/stream', $data['class_name'], $erpClass, 'The source class/stream differs from the ERP enrolment for the selected period.');
        }
        if (! empty($data['residency_normalized'])) {
            $erpResidency = $enrollment->isBoarder() ? 'boarding' : 'day';
            if ($data['residency_normalized'] !== $erpResidency) $this->finding($batch, $row, $student->id, null, 'residency_mismatch', 'warning', 'residency', $data['residency'], $erpResidency, 'The source residency differs from the ERP enrolment for the selected period.');
        }
    }

    private function compareInvoices(StudentReconciliationBatch $batch, StudentReconciliationRow $row, array $data, string $sourceType, Student $student, $invoices, string $academicYear, string $term): void
    {
        $claimPresent = array_key_exists('invoice_claimed', $data) && $data['invoice_claimed'] !== null;
        $hasInvoiceEvidence = $sourceType === 'invoice_register' || $claimPresent || ! empty($data['invoice_number']);
        if (! $hasInvoiceEvidence) return;
        if ((! empty($data['academic_year']) && (string) $data['academic_year'] !== $academicYear)
            || (! empty($data['term']) && StudentReconciliationWorkbookParser::normalizeTerm($data['term']) !== StudentReconciliationWorkbookParser::normalizeTerm($term))) return;

        $eligible = $invoices->filter(fn ($invoice) => ! in_array(Str::lower((string) $invoice->status), ['cancelled', 'canceled', 'void', 'reversed'], true));
        if (! empty($data['invoice_number'])) $eligible = $eligible->filter(fn ($invoice) => StudentReconciliationWorkbookParser::normalizeText($invoice->number) === StudentReconciliationWorkbookParser::normalizeText($data['invoice_number']));
        $invoiceTotal = (float) $eligible->sum('total_amount');
        $invoiceIds = $eligible->pluck('id')->values()->all();
        $row->erp_invoice_total = $invoiceTotal;
        $row->erp_invoice_ids = $invoiceIds;

        $invoiceExpected = ($claimPresent && $data['invoice_claimed'] === true)
            || (! $claimPresent && $sourceType === 'invoice_register')
            || ! empty($data['invoice_number']);
        if ($eligible->isEmpty() && $invoiceExpected) {
            $this->finding($batch, $row, $student->id, null, 'invoice_missing_in_erp', 'critical', 'invoice', $data['invoice_number'] ?? ($data['invoice_amount'] ?? 'Source claims invoiced'), 'No matching invoice', 'The source indicates this student was invoiced, but no matching ERP invoice exists for the selected academic year and term.');
        } elseif ($eligible->isNotEmpty() && $claimPresent && $data['invoice_claimed'] === false) {
            $this->finding($batch, $row, $student->id, $eligible->first()->id, 'invoice_exists_but_source_says_not_invoiced', 'warning', 'invoice status', 'Not invoiced', $eligible->pluck('number')->join(', '), 'An ERP invoice exists for this student although the source marks them as not invoiced.');
        }

        if (! empty($data['invoice_number']) && $eligible->isEmpty()) {
            $this->finding($batch, $row, $student->id, null, 'invoice_reference_not_found', 'critical', 'invoice number', $data['invoice_number'], null, 'The source invoice reference does not match an invoice in the selected ERP period.');
        }
        if (is_numeric($data['invoice_amount'] ?? null) && $eligible->isNotEmpty() && abs($invoiceTotal - (float) $data['invoice_amount']) > 0.01) {
            $this->finding($batch, $row, $student->id, $eligible->first()->id, 'invoice_amount_mismatch', 'critical', 'invoice amount', number_format((float) $data['invoice_amount'], 2, '.', ''), number_format($invoiceTotal, 2, '.', ''), 'The source amount differs from the total of matching ERP invoices for the selected period.');
        }
        if ($eligible->count() > 1 && empty($data['invoice_number'])) {
            $this->finding($batch, $row, $student->id, $eligible->first()->id, 'multiple_erp_invoices', 'warning', 'invoice count', (string) $eligible->count(), 'One or more', 'Multiple ERP invoices were found for this student in the selected period. Verify that they are separate valid charges.');
        }
    }

    private function compareCrossSource(StudentReconciliationBatch $batch, array $groups): void
    {
        foreach ($groups as $studentId => $records) {
            foreach (['grade', 'class_name', 'residency_normalized'] as $field) {
                $values = collect($records)->filter(fn ($r) => ! empty($r['data'][$field]))
                    ->map(fn ($r) => $field === 'residency_normalized' ? $r['data'][$field] : StudentReconciliationWorkbookParser::normalizeText($r['data'][$field]))->unique();
                if ($values->count() < 2) continue;
                $displayValues = collect($records)->filter(fn ($r) => ! empty($r['data'][$field]))->map(fn ($r) => $r['data'][$field])->unique()->implode(' | ');
                foreach ($records as $record) {
                    if (empty($record['data'][$field])) continue;
                    $this->finding($batch, $record['row'], (int) $studentId, null, 'cross_source_conflict', 'warning', $field, (string) $record['data'][$field], $displayValues, 'Uploaded sources disagree about this student. Compare the listed source values and verify the correct placement or residency.');
                }
            }
        }
    }

    private function reportMissingStudents(StudentReconciliationBatch $batch, int $schoolId, $enrollments, array $sourceStudentIds): void
    {
        $activeIds = $enrollments->flatten()->filter(fn ($enrollment) => $enrollment->status === 'active')->pluck('student_id')->unique();
        $missingIds = $activeIds->reject(fn ($id) => isset($sourceStudentIds[$id]));
        if ($missingIds->isEmpty()) return;
        Student::where('school_id', $schoolId)->whereIn('id', $missingIds)->orderBy('id')->chunk(250, function ($students) use ($batch, $enrollments) {
            foreach ($students as $student) {
                $placement = $enrollments->get($student->id, collect())->sortByDesc('id')->first();
                $this->finding($batch, null, $student->id, null, 'erp_student_not_in_uploaded_register', 'warning', 'student register', null, $this->studentName($student), 'The ERP has an active enrolment in the selected period, but this student does not appear in the uploads marked as a complete register.');
            }
        });
    }

    private function reportInvoicesMissingFromSource(StudentReconciliationBatch $batch, $invoices, array $invoiceSourceStudentIds, array $invoiceSourceReferences): void
    {
        foreach ($invoices as $studentId => $records) {
            foreach ($records as $invoice) {
                if (isset($invoiceSourceStudentIds[$studentId]) || isset($invoiceSourceReferences[$studentId.':'.StudentReconciliationWorkbookParser::normalizeText($invoice->number)])) continue;
                $this->finding($batch, null, (int) $studentId, $invoice->id, 'erp_invoice_not_in_uploaded_register', 'warning', 'invoice register', null, $invoice->number.' · $'.number_format((float) $invoice->total_amount, 2), 'An ERP invoice exists in the selected period but was not found in uploads marked as a complete invoice register.');
            }
        }
    }

    private function finding(StudentReconciliationBatch $batch, ?StudentReconciliationRow $row, ?int $studentId, ?int $invoiceId, string $code, string $severity, ?string $field, mixed $sourceValue, mixed $erpValue, string $message): void
    {
        $this->pendingFindings[] = [
            'batch_id' => $batch->id, 'source_row_id' => null,
            '_source_row_key' => $row ? $row->source_id.':'.$row->source_row_number : null,
            'student_id' => $studentId, 'invoice_id' => $invoiceId,
            'code' => $code, 'severity' => $severity, 'field' => $field,
            'source_value' => $this->safeText($sourceValue), 'erp_value' => $this->safeText($erpValue), 'message' => $message,
            'status' => 'open', 'reviewed_by' => null, 'reviewed_at' => null, 'review_note' => null,
        ];
    }

    private function persistRowsAndFindings(StudentReconciliationBatch $batch, array $rows): void
    {
        $now = now();
        $rowInserts = [];
        foreach ($rows as $row) {
            $attributes = $row->getAttributes();
            unset($attributes['id']);
            $attributes['created_at'] = $now;
            $attributes['updated_at'] = $now;
            $rowInserts[] = $attributes;
        }
        foreach (array_chunk($rowInserts, 500) as $chunk) {
            DB::table('student_reconciliation_rows')->insert($chunk);
        }

        $rowIds = DB::table('student_reconciliation_rows')->where('batch_id', $batch->id)
            ->get(['id', 'source_id', 'source_row_number'])
            ->mapWithKeys(fn ($item) => [$item->source_id.':'.$item->source_row_number => $item->id]);
        $findingInserts = [];
        foreach ($this->pendingFindings as $finding) {
            $key = $finding['_source_row_key'];
            unset($finding['_source_row_key']);
            if ($key !== null) $finding['source_row_id'] = $rowIds->get($key);
            $finding['created_at'] = $now;
            $finding['updated_at'] = $now;
            $findingInserts[] = $finding;
        }
        foreach (array_chunk($findingInserts, 500) as $chunk) {
            DB::table('student_reconciliation_findings')->insert($chunk);
        }
        $this->pendingFindings = $findingInserts;
    }

    private function indexBy($students, string $field): array
    {
        $index = [];
        foreach ($students as $student) {
            $value = $student->{$field};
            if ($value !== null && trim((string) $value) !== '') $index[$this->identifier((string) $value)] = $student;
        }
        return $index;
    }

    private function identifier(string $value): string
    {
        $value = trim($value);
        if (is_numeric($value) && (float) $value === (float) (int) $value) return (string) (int) $value;
        return StudentReconciliationWorkbookParser::normalizeText($value);
    }

    private function studentName(Student $student): string
    {
        return trim(implode(' ', array_filter([$student->first_name, $student->other_names, $student->last_name])));
    }

    private function gradeKey(string $value): string
    {
        $value = StudentReconciliationWorkbookParser::normalizeText($value);
        $value = preg_replace('/\b(form|grade)\b/', '', $value) ?? $value;
        return preg_replace('/[^a-z0-9]/', '', trim($value)) ?? '';
    }

    private function safeText(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (is_bool($value)) return $value ? 'Yes' : 'No';
        if (is_array($value)) return Str::limit(json_encode($value, JSON_UNESCAPED_UNICODE) ?: '', 1000, '…');
        return Str::limit(trim((string) $value), 1000, '…');
    }
}
