<?php

namespace App\Services\Academic;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class StudentReconciliationWorkbookParser
{
    private const HEADERS = [
        'erp_student_id' => ['erp id', 'erp student id', 'student id', 'database id', 'student database id'],
        'admission_number' => ['admission number', 'admission no', 'admission #', 'adm no', 'adm number', 'adm #'],
        'registration_number' => ['registration number', 'registration no', 'registration #', 'reg number', 'reg no', 'reg #'],
        'full_name' => ['student name', 'learner name', 'full name', 'student', 'learner', 'name'],
        'first_name' => ['first name', 'firstname', 'given name'],
        'last_name' => ['last name', 'lastname', 'surname', 'family name'],
        'other_names' => ['other names', 'middle name', 'middle names'],
        'date_of_birth' => ['date of birth', 'dob', 'birth date', 'birthdate'],
        'grade' => ['grade', 'form', 'grade form', 'form grade', 'level'],
        'class_name' => ['class', 'stream', 'class stream', 'class name', 'stream name'],
        'residency' => ['residency', 'residence', 'boarding status', 'student type', 'boarding', 'boarder day'],
        'invoice_status' => ['invoice status', 'invoiced', 'invoice issued', 'billing status', 'billed'],
        'invoice_number' => ['invoice number', 'invoice no', 'invoice #', 'invoice ref', 'invoice reference'],
        'invoice_amount' => ['invoice amount', 'amount invoiced', 'expected total', 'expected fees', 'expected fee', 'expected amount', 'fees due', 'total fees', 'fee total', 'total amount', 'amount', 'fees', 'term total'],
        'academic_year' => ['academic year', 'year', 'school year'],
        'term' => ['term', 'school term', 'academic term'],
    ];

    public function parse(UploadedFile $file, string $sourceType): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $grid = $sheet->toArray(null, true, true, false);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('The file could not be read as an Excel or CSV register.');
        }

        $headerIndex = null;
        $columns = [];
        $highestHeaderRow = min(count($grid), 30);
        for ($i = 0; $i < $highestHeaderRow; $i++) {
            $candidateColumns = $this->mapHeaders($grid[$i] ?? [], $grid[$i - 1] ?? []);
            $hasIdentity = isset($candidateColumns['full_name']) || (isset($candidateColumns['first_name']) && isset($candidateColumns['last_name'])) || isset($candidateColumns['erp_student_id']) || isset($candidateColumns['admission_number']) || isset($candidateColumns['registration_number']);
            if ($hasIdentity && count($candidateColumns) >= 2) {
                $headerIndex = $i;
                $columns = $candidateColumns;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new InvalidArgumentException('Could not find a usable heading row. Include a student name or ID plus at least one other recognised heading.');
        }
        $headerLabels = $this->headerLabels($grid[$headerIndex] ?? [], $grid[$headerIndex - 1] ?? []);

        $rows = [];
        $end = min(count($grid), $headerIndex + 5001);
        for ($i = $headerIndex + 1; $i < $end; $i++) {
            $mapped = [];
            foreach ($columns as $field => $columnIndex) {
                $mapped[$field] = $this->normalizeValue($field, $grid[$i][$columnIndex] ?? null);
            }
            $invoiceAmountColumn = $columns['invoice_amount'] ?? null;
            $mapped['invoice_amount_basis'] = $invoiceAmountColumn === null ? null : ($headerLabels[$invoiceAmountColumn] ?? null);
            $mapped['invoice_amount_is_billed'] = $this->amountHeadingIndicatesInvoice($mapped['invoice_amount_basis']);
            $mapped['source_type'] = $sourceType;
            $mapped = $this->completeIdentity($mapped);
            if ($this->isSummaryRow($mapped) || ! $this->hasUsefulData($mapped)) {
                continue;
            }
            $mapped['source_row_number'] = $i + 1;
            $mapped['identity_name_key'] = $this->nameKey($mapped['full_name'] ?? '');
            $rows[] = $mapped;
        }

        if (count($rows) >= 5000 && count($grid) > $end) {
            throw new InvalidArgumentException('A source file exceeds the 5,000 data-row limit. Split it into smaller registers.');
        }
        if ($rows === []) {
            throw new InvalidArgumentException('The detected heading row did not contain any usable student rows.');
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $grid);

        return [
            'header_row' => $headerIndex + 1,
            'recognized_columns' => array_keys($columns),
            'rows' => $rows,
        ];
    }

    public static function nameKey(?string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii(trim((string) $name)))) ?? '';
    }

    public static function normalizeTerm(?string $term): ?string
    {
        $value = Str::lower(trim((string) $term));
        if ($value === '') return null;
        if (preg_match('/\b1\b|first/', $value)) return 'term1';
        if (preg_match('/\b2\b|second/', $value)) return 'term2';
        if (preg_match('/\b3\b|third/', $value)) return 'term3';
        return preg_replace('/[^a-z0-9]/', '', Str::ascii($value));
    }

    public static function normalizeText(?string $value): string
    {
        return preg_replace('/\s+/', ' ', Str::lower(Str::ascii(trim((string) $value)))) ?? '';
    }

    private function mapHeaders(array $headerRow, array $previousHeaderRow = []): array
    {
        $result = [];
        $labels = $this->headerLabels($headerRow, $previousHeaderRow);
        $hasSplitName = in_array('surname', $labels, true) && in_array('name', $labels, true);
        foreach ($labels as $index => $normalized) {
            if ($hasSplitName && $normalized === 'student') continue;
            if ($hasSplitName && $normalized === 'name') {
                $result['first_name'] ??= $index;
                continue;
            }
            foreach (self::HEADERS as $field => $aliases) {
                $normalizedAliases = array_map(fn ($alias) => $this->normalizeHeading($alias), $aliases);
                if (! isset($result[$field]) && in_array($normalized, $normalizedAliases, true)) {
                    $result[$field] = $index;
                    break;
                }
            }
        }
        return $result;
    }

    private function headerLabels(array $headerRow, array $previousHeaderRow = []): array
    {
        $labels = [];
        foreach ($headerRow as $index => $heading) {
            $current = $this->normalizeHeading($heading);
            $previous = $this->normalizeHeading($previousHeaderRow[$index] ?? '');
            $isGroupHeading = in_array($previous, ['expected', 'invoice', 'invoiced', 'fee', 'fees', 'billing', 'billed', 'amount', 'total'], true);
            $labels[$index] = $isGroupHeading && $current !== '' && $previous !== $current
                ? trim($previous.' '.$current)
                : ($current !== '' ? $current : $previous);
        }

        return $labels;
    }

    private function normalizeHeading(mixed $heading): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii(trim((string) $heading)))) ?? '';
        return trim(preg_replace('/\s+/', ' ', $normalized) ?? '');
    }

    private function amountHeadingIndicatesInvoice(?string $heading): bool
    {
        return $heading !== null && preg_match('/\b(invoice|invoiced|billed|expected)\b/', $heading) === 1;
    }

    private function normalizeValue(string $field, mixed $value): mixed
    {
        if ($value === null || trim((string) $value) === '') return null;
        if ($field === 'date_of_birth') {
            try {
                if (is_numeric($value) && (float) $value > 1000) return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
                $timestamp = strtotime((string) $value);
                return $timestamp === false ? trim((string) $value) : date('Y-m-d', $timestamp);
            } catch (Throwable) {
                return trim((string) $value);
            }
        }
        if ($field === 'invoice_amount') {
            if (is_numeric($value)) return round((float) $value, 2);
            $clean = str_replace([',', '$', 'USD', ' '], '', Str::upper((string) $value));
            if (preg_match('/^\((\d+(?:\.\d{1,2})?)\)$/', $clean, $m)) return -round((float) $m[1], 2);
            return is_numeric($clean) ? round((float) $clean, 2) : null;
        }
        if ($field === 'academic_year') {
            return is_numeric($value) ? (string) (int) $value : trim((string) $value);
        }
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    }

    private function completeIdentity(array $row): array
    {
        if (empty($row['full_name']) && (! empty($row['first_name']) || ! empty($row['last_name']))) {
            $row['full_name'] = trim(implode(' ', array_filter([$row['first_name'] ?? null, $row['other_names'] ?? null, $row['last_name'] ?? null])));
        }
        if (! empty($row['residency'])) {
            $residency = Str::lower(trim($row['residency']));
            if (in_array($residency, ['yes', 'y', 'true', '1', 'boarding', 'boarder', 'boarding student'], true)) $row['residency_normalized'] = 'boarding';
            elseif (in_array($residency, ['no', 'n', 'false', '0', 'day', 'day scholar', 'day student'], true)) $row['residency_normalized'] = 'day';
            else $row['residency_normalized'] = null;
        }
        if (! empty($row['invoice_status'])) {
            $status = Str::lower(trim($row['invoice_status']));
            if (preg_match('/not\s+invoic|unbilled|no invoice|false|^no$|^n$/', $status)) $row['invoice_claimed'] = false;
            elseif (preg_match('/invoic|billed|paid|unpaid|outstanding|partial|^yes$|^y$|^true$|^1$/', $status)) $row['invoice_claimed'] = true;
            else $row['invoice_claimed'] = null;
        } elseif (! empty($row['invoice_amount_is_billed']) && is_numeric($row['invoice_amount'] ?? null)) {
            $row['invoice_claimed'] = true;
        } else {
            $row['invoice_claimed'] = null;
        }
        $row['invoice_evidence'] = ($row['source_type'] ?? null) === 'invoice_register'
            || ! empty($row['invoice_number'])
            || $row['invoice_claimed'] !== null
            || (! empty($row['invoice_amount_is_billed']) && is_numeric($row['invoice_amount'] ?? null));
        return $row;
    }

    private function hasUsefulData(array $row): bool
    {
        foreach (['full_name', 'erp_student_id', 'admission_number', 'registration_number', 'invoice_number'] as $field) {
            if (! empty($row[$field])) return true;
        }
        return false;
    }

    private function isSummaryRow(array $row): bool
    {
        if (! empty($row['erp_student_id']) || ! empty($row['admission_number']) || ! empty($row['registration_number']) || ! empty($row['invoice_number'])) {
            return false;
        }

        $name = self::nameKey($row['full_name'] ?? '');
        return preg_match('/^(?:(?:grand)?totals?|subtotal)(?:students?|fees?|amount)?$/', $name) === 1;
    }
}
