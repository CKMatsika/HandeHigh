<?php

namespace App\Imports;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/** Create-only teacher import; existing user accounts and profiles are never changed. */
class TeachersImport implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    private array $results = ['created' => [], 'skipped' => [], 'invalid' => []];
    private array $seenEmails = [];
    private array $seenEmployeeIds = [];

    public function __construct(private readonly int $schoolId)
    {
    }

    public function onRow(Row $row): void
    {
        $number = $row->getIndex();
        $data = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $row->toArray());
        foreach (['date_of_birth', 'hire_date'] as $dateField) {
            if (isset($data[$dateField]) && is_numeric($data[$dateField]) && (float) $data[$dateField] > 1000) {
                try {
                    $data[$dateField] = ExcelDate::excelToDateTimeObject((float) $data[$dateField])->format('Y-m-d');
                } catch (\Throwable) {
                    // Validation below will report an unusable date in the source row.
                }
            }
        }
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $employeeId = trim((string) ($data['employee_id'] ?? ''));

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:70'],
            'hire_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            $this->results['invalid'][] = ['row' => $number, 'name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')), 'reason' => implode(' ', $validator->errors()->all())];
            return;
        }

        if (isset($this->seenEmails[$email])) {
            $this->results['skipped'][] = ['row' => $number, 'name' => trim($data['first_name'].' '.$data['last_name']), 'reason' => 'Duplicate email in this workbook (first seen on row '.$this->seenEmails[$email].').'];
            return;
        }
        $this->seenEmails[$email] = $number;

        $employeeKey = strtolower($employeeId);
        if ($employeeId !== '' && isset($this->seenEmployeeIds[$employeeKey])) {
            $this->results['skipped'][] = ['row' => $number, 'name' => trim($data['first_name'].' '.$data['last_name']), 'reason' => 'Duplicate employee ID in this workbook (first seen on row '.$this->seenEmployeeIds[$employeeKey].').'];
            return;
        }
        if ($employeeId !== '') $this->seenEmployeeIds[$employeeKey] = $number;

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->results['skipped'][] = ['row' => $number, 'name' => trim($data['first_name'].' '.$data['last_name']), 'reason' => 'Email already belongs to an existing account; no records were changed.'];
            return;
        }
        if ($employeeId !== '' && Teacher::where('employee_id', $employeeId)->exists()) {
            $this->results['skipped'][] = ['row' => $number, 'name' => trim($data['first_name'].' '.$data['last_name']), 'reason' => 'Employee ID already exists; no records were changed.'];
            return;
        }

        try {
            $teacherId = DB::transaction(function () use ($data, $email, $employeeId) {
                $user = User::create([
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'school_id' => $this->schoolId,
                    // Never use a shared/default password for imported accounts.
                    'password' => Hash::make(Str::random(48)),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('teacher');

                $teacher = Teacher::create([
                    'school_id' => $this->schoolId,
                    'user_id' => $user->id,
                    'employee_id' => $employeeId !== '' ? $employeeId : $this->newEmployeeId(),
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'qualification' => $data['qualification'] ?? null,
                    'specialization' => $data['specialization'] ?? null,
                    'experience_years' => $data['experience_years'] ?? 0,
                    'hire_date' => $data['hire_date'] ?? null,
                    'salary' => $data['salary'] ?? null,
                    'status' => true,
                ]);

                return $teacher->id;
            });

            $this->results['created'][] = ['row' => $number, 'teacher_id' => $teacherId, 'name' => trim($data['first_name'].' '.$data['last_name'])];
        } catch (\Throwable $exception) {
            report($exception);
            $this->results['invalid'][] = ['row' => $number, 'name' => trim($data['first_name'].' '.$data['last_name']), 'reason' => 'Could not create teacher and account. Check employee ID uniqueness and the teacher role configuration.'];
        }
    }

    private function newEmployeeId(): string
    {
        do {
            $id = 'TCH-'.strtoupper(Str::random(8));
        } while (Teacher::where('employee_id', $id)->exists());

        return $id;
    }

    public function results(): array
    {
        return $this->results;
    }
}
