<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;

class StudentStatementPrintService
{
    public function build(School $school, Student $student, string $academicYear, ?string $term): array
    {
        $invoicesQuery = Invoice::with(['items', 'allocations.payment'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('status', '!=', 'cancelled');

        if ($term) {
            $invoicesQuery->where('term', $term);
        }

        $invoices = $invoicesQuery->orderBy('issued_at')->get();

        $payments = Payment::with(['allocations.invoice'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereHas('allocations.invoice', function ($query) use ($academicYear, $term) {
                $query->where('academic_year', $academicYear);
                if ($term) {
                    $query->where('term', $term);
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
            foreach ($payment->allocations as $allocation) {
                $events->push([
                    'type' => 'payment',
                    'date' => $payment->paid_at,
                    'reference' => $payment->reference ?? $payment->method,
                    'description' => 'Payment (' . ucfirst(str_replace('_', ' ', $payment->method)) . ')',
                    'debit' => 0,
                    'credit' => $allocation->amount,
                    'balance' => 0,
                    'payment' => $payment,
                ]);
            }
        }

        $events = $events->sortBy('date');
        $openingBalance = $this->openingBalance($school, $student, $academicYear, $term);

        $scholarshipStatement = app(\App\Services\Finance\ScholarshipService::class)
            ->legacyStatement($student, $events, (float) $openingBalance, $academicYear, $term);
        $events = $scholarshipStatement['events'];
        $openingBalance = $scholarshipStatement['opening_balance'];

        $runningBalance = $openingBalance;
        $events = $events->map(function (array $event) use (&$runningBalance) {
            $runningBalance += $event['debit'] - $event['credit'];
            $event['balance'] = $runningBalance;

            return $event;
        });

        return [
            'events' => $events,
            'openingBalance' => $openingBalance,
            'closingBalance' => $runningBalance,
        ];
    }

    private function openingBalance(School $school, Student $student, string $academicYear, ?string $term): float
    {
        if ($term) {
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', $academicYear)
                ->where('term', '<', $term)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($query) use ($academicYear, $term) {
                    $query->where('academic_year', $academicYear)->where('term', '<', $term);
                })
                ->with('allocations')
                ->get()
                ->sum(fn ($payment) => $payment->allocations->sum('amount'));

            return (float) ($previousInvoices - $previousPayments);
        }

        $previousInvoices = Invoice::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->where('academic_year', '<', $academicYear)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $previousPayments = Payment::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereHas('allocations.invoice', fn ($query) => $query->where('academic_year', '<', $academicYear))
            ->with('allocations')
            ->get()
            ->sum(fn ($payment) => $payment->allocations->sum('amount'));

        return (float) ($previousInvoices - $previousPayments);
    }
}
