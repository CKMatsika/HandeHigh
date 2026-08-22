<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Imports\EnrollmentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $enrollments = Enrollment::with(['student.guardians', 'invoices'])
            ->where('school_id', $school->id)
            ->orderByDesc('enrollment_date')
            ->paginate(15);

        return view('admin.enrollments.index', [
            'school' => $school,
            'enrollments' => $enrollments,
        ]);
    }

    public function show(Enrollment $enrollment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $enrollment->school_id !== $school->id) {
            abort(403);
        }

        $enrollment->load(['student.guardians', 'invoices.items', 'invoices.allocations']);

        return view('admin.enrollments.show', [
            'school' => $school,
            'enrollment' => $enrollment,
        ]);
    }

    public function edit(Enrollment $enrollment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $enrollment->school_id !== $school->id) {
            abort(403);
        }

        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.enrollments.edit', [
            'school' => $school,
            'enrollment' => $enrollment,
            'terms' => $terms,
        ]);
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $enrollment->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:20'],
            'grade' => ['required', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:50'],
            'is_boarding' => ['nullable', 'boolean'],
            'has_transport' => ['nullable', 'boolean'],
            'enrollment_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:20'],
        ]);

        $enrollment->update([
            'academic_year' => $validated['academic_year'],
            'term' => $validated['term'],
            'grade' => $validated['grade'],
            'class_name' => $validated['class_name'] ?? null,
            'is_boarding' => $request->boolean('is_boarding'),
            'has_transport' => $request->boolean('has_transport'),
            'enrollment_date' => $validated['enrollment_date'],
            'status' => $validated['status'],
        ]);

        $enrollment->student?->update([
            'grade' => $validated['grade'],
            'class_name' => $validated['class_name'] ?? null,
            'is_boarding' => $request->boolean('is_boarding'),
            'has_transport' => $request->boolean('has_transport'),
        ]);

        return redirect()
            ->route('admin.enrollments.show', $enrollment)
            ->with('status', 'Enrollment updated successfully.');
    }

    public function print(Enrollment $enrollment)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $enrollment->school_id !== $school->id) {
            abort(403);
        }

        $enrollment->load(['student.guardians', 'invoices.items', 'invoices.allocations']);

        return view('admin.enrollments.print', [
            'school' => $school,
            'enrollment' => $enrollment,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $currentYear = now()->format('Y');
        $defaultAcademicYear = $currentYear;
        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.enrollments.create', [
            'school' => $school,
            'defaultAcademicYear' => $defaultAcademicYear,
            'terms' => $terms,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $validated = $request->validate([
            'guardian_first_name' => ['required', 'string', 'max:255'],
            'guardian_last_name' => ['required', 'string', 'max:255'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'guardian_address' => ['nullable', 'string', 'max:255'],
            'guardian_city' => ['nullable', 'string', 'max:100'],
            'guardian_country' => ['nullable', 'string', 'max:100'],
            'relationship' => ['nullable', 'string', 'max:50'],

            'student_first_name' => ['required', 'string', 'max:255'],
            'student_last_name' => ['required', 'string', 'max:255'],
            'student_gender' => ['nullable', 'string', 'max:20'],
            'student_date_of_birth' => ['nullable', 'date'],
            'grade' => ['required', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:50'],
            'is_boarding' => ['nullable', 'boolean'],
            'has_transport' => ['nullable', 'boolean'],

            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:20'],
            'enrollment_date' => ['required', 'date'],
        ]);

        $isBoarding = $request->boolean('is_boarding');
        $hasTransport = $request->boolean('has_transport');

        $enrollment = DB::transaction(function () use ($validated, $school, $isBoarding, $hasTransport) {
            $guardianQuery = Guardian::where('school_id', $school->id);

            if (! empty($validated['guardian_email'])) {
                $guardianQuery->where('email', $validated['guardian_email']);
            }

            $guardian = $guardianQuery->first();

            if (! $guardian) {
                $guardian = Guardian::create([
                    'school_id' => $school->id,
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'email' => $validated['guardian_email'] ?? null,
                    'phone' => $validated['guardian_phone'] ?? null,
                    'address' => $validated['guardian_address'] ?? null,
                    'city' => $validated['guardian_city'] ?? null,
                    'country' => $validated['guardian_country'] ?? null,
                ]);
            } else {
                $guardian->fill([
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'phone' => $validated['guardian_phone'] ?? $guardian->phone,
                    'address' => $validated['guardian_address'] ?? $guardian->address,
                    'city' => $validated['guardian_city'] ?? $guardian->city,
                    'country' => $validated['guardian_country'] ?? $guardian->country,
                ]);
                $guardian->save();
            }

            $student = Student::create([
                'school_id' => $school->id,
                'first_name' => $validated['student_first_name'],
                'last_name' => $validated['student_last_name'],
                'gender' => $validated['student_gender'] ?? null,
                'date_of_birth' => $validated['student_date_of_birth'] ?? null,
                'grade' => $validated['grade'],
                'class_name' => $validated['class_name'] ?? null,
                'is_boarding' => $isBoarding,
                'has_transport' => $hasTransport,
                'status' => 'active',
            ]);

            $guardian->students()->syncWithoutDetaching([
                $student->id => [
                    'relationship' => $validated['relationship'] ?? null,
                    'is_primary' => true,
                ],
            ]);

            $enrollment = Enrollment::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
                'grade' => $validated['grade'],
                'class_name' => $validated['class_name'] ?? null,
                'is_boarding' => $isBoarding,
                'has_transport' => $hasTransport,
                'enrollment_date' => $validated['enrollment_date'],
                'status' => 'active',
            ]);

            $feeStructures = FeeStructure::where('school_id', $school->id)
                ->where('academic_year', $validated['academic_year'])
                ->where('term', $validated['term'])
                ->where(function ($q) use ($validated) {
                    $q->whereNull('grade')
                        ->orWhere('grade', $validated['grade']);
                })
                ->get();

            $selectedFees = $feeStructures->filter(function (FeeStructure $fee) use ($isBoarding, $hasTransport) {
                if ($fee->service_type === 'boarding' && ! $isBoarding) {
                    return false;
                }
                if ($fee->service_type === 'transport' && ! $hasTransport) {
                    return false;
                }

                return true;
            });

            $invoiceTotal = 0;

            $invoiceNumber = 'INV-' . $school->code . '-' . now()->format('YmdHis');

            $invoice = Invoice::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'guardian_id' => $guardian->id,
                'enrollment_id' => $enrollment->id,
                'number' => $invoiceNumber,
                'type' => 'fees',
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
                'issued_at' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'status' => 'unpaid',
                'total_amount' => 0,
                'balance' => 0,
            ]);

            foreach ($selectedFees as $fee) {
                $quantity = 1;
                $lineTotal = $fee->amount * $quantity;
                $invoiceTotal += $lineTotal;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_structure_id' => $fee->id,
                    'description' => $fee->label,
                    'category' => $fee->category,
                    'quantity' => $quantity,
                    'unit_amount' => $fee->amount,
                    'line_total' => $lineTotal,
                ]);
            }

            if ($invoiceTotal > 0) {
                $invoice->update([
                    'total_amount' => $invoiceTotal,
                    'balance' => $invoiceTotal,
                ]);

                $entryDate = now()->toDateString();

                LedgerEntry::create([
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'invoice_id' => $invoice->id,
                    'entry_date' => $entryDate,
                    'account_code' => 'FEES_RECEIVABLE',
                    'type' => 'debit',
                    'amount' => $invoiceTotal,
                    'description' => 'Fees receivable for invoice ' . $invoice->number,
                ]);

                LedgerEntry::create([
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'invoice_id' => $invoice->id,
                    'entry_date' => $entryDate,
                    'account_code' => 'FEES_REVENUE',
                    'type' => 'credit',
                    'amount' => $invoiceTotal,
                    'description' => 'Fees revenue for invoice ' . $invoice->number,
                ]);
            }

            return $enrollment;
        });

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Student enrolled and invoice generated successfully.');
    }

    /**
     * Show the bulk enrollment form.
     *
     * @return \Illuminate\View\View
     */
    public function bulkCreate()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        return view('admin.enrollments.bulk-create', [
            'school' => $school,
        ]);
    }

    /**
     * Process the bulk enrollment file upload.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkStore(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('file');
            $schoolId = $school->id;

            DB::transaction(function () use ($file, $schoolId) {
                Excel::import(new EnrollmentsImport($schoolId), $file);
            });

            return redirect()
                ->route('admin.enrollments.index')
                ->with('success', 'Students enrolled successfully!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->with('import_errors', $failures)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error processing file: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Download the bulk enrollment template.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $template = tempnam(storage_path('app'), 'bulk-enrollment-template-');

        if ($template === false) {
            abort(500, 'Unable to create enrollment template.');
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $headers = [
                'student_name', 'email', 'phone', 'address', 'date_of_birth', 'gender',
                'class_name', 'academic_year', 'term', 'grade', 'is_boarding', 'has_transport', 'status'
            ];

            $sheet->fromArray([$headers]);
            $examples = [
                'John Doe', 'john@example.com', '1234567890', '123 Main St', '2010-05-15', 'male',
                'Grade 1', '2023-2024', 'First Term', '1', 'no', 'yes', 'active'
            ];
            $sheet->fromArray([$examples], null, 'A2');

            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($template);

            return response()
                ->download($template, 'bulk-enrollment-template.xlsx')
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            @unlink($template);
            throw $e;
        }
    }
}
