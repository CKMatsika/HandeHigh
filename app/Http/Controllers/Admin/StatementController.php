<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class StatementController extends Controller
{
    public function create(Student $student)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $student->school_id !== $school->id) {
            abort(403);
        }

        $academicYear = request('academic_year', now()->format('Y'));
        $term = request('term');

        $years = Invoice::where('school_id', $school->id)
            ->distinct()
            ->pluck('academic_year')
            ->filter()
            ->sortDesc()
            ->values();

        $terms = ['Term 1', 'Term 2', 'Term 3'];

        return view('admin.statements.create', [
            'school' => $school,
            'student' => $student,
            'academicYear' => $academicYear,
            'term' => $term,
            'years' => $years,
            'terms' => $terms,
        ]);
    }

    public function show(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $student->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:20'],
        ]);

        $academicYear = $validated['academic_year'];
        $term = $validated['term'] ?? null;

        $invoicesQuery = Invoice::with(['items', 'allocations.payment'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $invoicesQuery->where('term', $term);
        }

        $invoices = $invoicesQuery->orderBy('issued_at')->get();

        $payments = Payment::with(['allocations.invoice'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                $q->where('academic_year', $academicYear);
                if ($term) {
                    $q->where('term', $term);
                }
            })
            ->orderBy('paid_at')
            ->get();

        $events = collect();

        foreach ($invoices as $invoice) {
            $events->push([
                'type' => 'invoice',
                'date' => $invoice->issued_at,
                'reference' => $invoice->number,
                'description' => 'Invoice ' . $invoice->number,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'balance' => 0,
                'invoice' => $invoice,
            ]);
        }

        foreach ($payments as $payment) {
            foreach ($payment->allocations as $alloc) {
                $events->push([
                    'type' => 'payment',
                    'date' => $payment->paid_at,
                    'reference' => $payment->reference ?? $payment->method,
                    'description' => 'Payment (' . ucfirst(str_replace('_', ' ', $payment->method)) . ')',
                    'debit' => 0,
                    'credit' => $alloc->amount,
                    'balance' => 0,
                    'payment' => $payment,
                ]);
            }
        }

        $events = $events->sortBy('date');

        // Calculate opening balance (all transactions before the selected period)
        $openingBalance = 0;
        if ($term) {
            // For specific term, get all transactions from previous terms in the same year
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', $academicYear)
                ->where('term', '<', $term)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                    $q->where('academic_year', $academicYear)
                      ->where('term', '<', $term);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        } else {
            // For full year, get all transactions from previous years
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', '<', $academicYear)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear) {
                    $q->where('academic_year', '<', $academicYear);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        }

        // Calculate running balance for current period
        $runningBalance = $openingBalance;
        $events = $events->map(function ($event) use (&$runningBalance, $openingBalance) {
            $runningBalance += $event['debit'] - $event['credit'];
            $event['balance'] = $runningBalance;
            
            // Debug logging
            \Log::info('Statement Balance Calculation', [
                'type' => $event['type'],
                'reference' => $event['reference'],
                'debit' => $event['debit'],
                'credit' => $event['credit'],
                'running_balance' => $runningBalance,
                'opening_balance' => $openingBalance
            ]);
            
            return $event;
        });

        $closingBalance = $runningBalance;

        return view('admin.statements.show', [
            'school' => $school,
            'student' => $student,
            'academicYear' => $academicYear,
            'term' => $term,
            'events' => $events,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
        ]);
    }

    public function print(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $student->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:20'],
        ]);

        $academicYear = $validated['academic_year'];
        $term = $validated['term'] ?? null;

        $invoicesQuery = Invoice::with(['items', 'allocations.payment'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $invoicesQuery->where('term', $term);
        }

        $invoices = $invoicesQuery->orderBy('issued_at')->get();

        $payments = Payment::with(['allocations.invoice'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                $q->where('academic_year', $academicYear);
                if ($term) {
                    $q->where('term', $term);
                }
            })
            ->orderBy('paid_at')
            ->get();

        $events = collect();

        foreach ($invoices as $invoice) {
            $events->push([
                'type' => 'invoice',
                'date' => $invoice->issued_at,
                'reference' => $invoice->number,
                'description' => 'Invoice ' . $invoice->number,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'balance' => 0,
                'invoice' => $invoice,
            ]);
        }

        foreach ($payments as $payment) {
            foreach ($payment->allocations as $alloc) {
                $events->push([
                    'type' => 'payment',
                    'date' => $payment->paid_at,
                    'reference' => $payment->reference ?? $payment->method,
                    'description' => 'Payment (' . ucfirst(str_replace('_', ' ', $payment->method)) . ')',
                    'debit' => 0,
                    'credit' => $alloc->amount,
                    'balance' => 0,
                    'payment' => $payment,
                ]);
            }
        }

        $events = $events->sortBy('date');

        // Calculate opening balance (all transactions before the selected period)
        $openingBalance = 0;
        if ($term) {
            // For specific term, get all transactions from previous terms in the same year
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', $academicYear)
                ->where('term', '<', $term)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                    $q->where('academic_year', $academicYear)
                      ->where('term', '<', $term);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        } else {
            // For full year, get all transactions from previous years
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', '<', $academicYear)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear) {
                    $q->where('academic_year', '<', $academicYear);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        }

        // Calculate running balance for current period
        $runningBalance = $openingBalance;
        $events = $events->map(function ($event) use (&$runningBalance, $openingBalance) {
            $runningBalance += $event['debit'] - $event['credit'];
            $event['balance'] = $runningBalance;
            return $event;
        });

        $closingBalance = $runningBalance;

        return view('admin.statements.print', [
            'school' => $school,
            'student' => $student,
            'academicYear' => $academicYear,
            'term' => $term,
            'events' => $events,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
        ]);
    }

    public function download(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $student->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:20'],
        ]);

        $academicYear = $validated['academic_year'];
        $term = $validated['term'] ?? null;

        // Get the same data as the print method
        $invoicesQuery = Invoice::with(['items', 'allocations.payment'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $invoicesQuery->where('term', $term);
        }

        $invoices = $invoicesQuery->orderBy('issued_at')->get();

        $payments = Payment::with(['allocations.invoice'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                $q->where('academic_year', $academicYear);
                if ($term) {
                    $q->where('term', $term);
                }
            })
            ->orderBy('paid_at')
            ->get();

        $events = collect();

        foreach ($invoices as $invoice) {
            $events->push([
                'type' => 'invoice',
                'date' => $invoice->issued_at,
                'reference' => $invoice->number,
                'description' => 'Invoice ' . $invoice->number,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'balance' => 0,
                'invoice' => $invoice,
            ]);
        }

        foreach ($payments as $payment) {
            foreach ($payment->allocations as $alloc) {
                $events->push([
                    'type' => 'payment',
                    'date' => $payment->paid_at,
                    'reference' => $payment->reference ?? $payment->method,
                    'description' => 'Payment (' . ucfirst(str_replace('_', ' ', $payment->method)) . ')',
                    'debit' => 0,
                    'credit' => $alloc->amount,
                    'balance' => 0,
                    'payment' => $payment,
                ]);
            }
        }

        $events = $events->sortBy('date');

        // Calculate opening balance
        $openingBalance = 0;
        if ($term) {
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', $academicYear)
                ->where('term', '<', $term)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                    $q->where('academic_year', $academicYear)
                      ->where('term', '<', $term);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        } else {
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', '<', $academicYear)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear) {
                    $q->where('academic_year', '<', $academicYear);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        }

        // Calculate running balance
        $runningBalance = $openingBalance;
        $events = $events->map(function ($event) use (&$runningBalance, $openingBalance) {
            $runningBalance += $event['debit'] - $event['credit'];
            $event['balance'] = $runningBalance;
            return $event;
        });

        $closingBalance = $runningBalance;

        // Generate PDF
        $pdf = PDF::loadView('admin.statements.pdf', [
            'school' => $school,
            'student' => $student,
            'academicYear' => $academicYear,
            'term' => $term,
            'events' => $events,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
        ]);

        $filename = 'statement_' . $student->last_name . '_' . $student->first_name . '_' . $academicYear . ($term ? '_' . $term : '') . '.pdf';

        return $pdf->download($filename);
    }

    public function email(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $student->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email'],
        ]);

        $academicYear = $validated['academic_year'];
        $term = $validated['term'] ?? null;
        $email = $validated['email'];

        // Get the same data as the download method
        $invoicesQuery = Invoice::with(['items', 'allocations.payment'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $invoicesQuery->where('term', $term);
        }

        $invoices = $invoicesQuery->orderBy('issued_at')->get();

        $payments = Payment::with(['allocations.invoice'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                $q->where('academic_year', $academicYear);
                if ($term) {
                    $q->where('term', $term);
                }
            })
            ->orderBy('paid_at')
            ->get();

        $events = collect();

        foreach ($invoices as $invoice) {
            $events->push([
                'type' => 'invoice',
                'date' => $invoice->issued_at,
                'reference' => $invoice->number,
                'description' => 'Invoice ' . $invoice->number,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'balance' => 0,
                'invoice' => $invoice,
            ]);
        }

        foreach ($payments as $payment) {
            foreach ($payment->allocations as $alloc) {
                $events->push([
                    'type' => 'payment',
                    'date' => $payment->paid_at,
                    'reference' => $payment->reference ?? $payment->method,
                    'description' => 'Payment (' . ucfirst(str_replace('_', ' ', $payment->method)) . ')',
                    'debit' => 0,
                    'credit' => $alloc->amount,
                    'balance' => 0,
                    'payment' => $payment,
                ]);
            }
        }

        $events = $events->sortBy('date');

        // Calculate opening balance
        $openingBalance = 0;
        if ($term) {
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', $academicYear)
                ->where('term', '<', $term)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                    $q->where('academic_year', $academicYear)
                      ->where('term', '<', $term);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        } else {
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', '<', $academicYear)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear) {
                    $q->where('academic_year', '<', $academicYear);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        }

        // Calculate running balance
        $runningBalance = $openingBalance;
        $events = $events->map(function ($event) use (&$runningBalance, $openingBalance) {
            $runningBalance += $event['debit'] - $event['credit'];
            $event['balance'] = $runningBalance;
            return $event;
        });

        $closingBalance = $runningBalance;

        // Generate PDF
        $pdf = PDF::loadView('admin.statements.pdf', [
            'school' => $school,
            'student' => $student,
            'academicYear' => $academicYear,
            'term' => $term,
            'events' => $events,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
        ]);

        $filename = 'statement_' . $student->last_name . '_' . $student->first_name . '_' . $academicYear . ($term ? '_' . $term : '') . '.pdf';
        
        // Store PDF temporarily
        $pdfPath = 'temp/' . $filename;
        Storage::disk('local')->put($pdfPath, $pdf->output());

        // Send email
        try {
            Mail::send('emails.statement', [
                'school' => $school,
                'student' => $student,
                'academicYear' => $academicYear,
                'term' => $term,
                'openingBalance' => $openingBalance,
                'closingBalance' => $closingBalance,
            ], function ($message) use ($email, $student, $filename, $pdfPath) {
                $message->to($email)
                    ->subject('Student Statement - ' . $student->first_name . ' ' . $student->last_name)
                    ->attach(Storage::disk('local')->path($pdfPath), [
                        'as' => $filename,
                        'mime' => 'application/pdf',
                    ]);
            });

            // Clean up temporary file
            Storage::disk('local')->delete($pdfPath);

            return back()->with('status', 'Statement emailed successfully to ' . $email);
        } catch (\Exception $e) {
            // Clean up temporary file
            Storage::disk('local')->delete($pdfPath);
            
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }
}
