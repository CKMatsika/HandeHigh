<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebtorCreditorReportController extends Controller
{
    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        // Debtors: student invoices outstanding
        $debtors = Invoice::where('school_id', $school->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->select('guardian_id', DB::raw('sum(balance) as outstanding'))
            ->groupBy('guardian_id')
            ->get();

        // Creditors: vendor bills outstanding
        $creditors = Bill::where('school_id', $school->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->select('vendor_id', DB::raw('sum(balance) as outstanding'))
            ->groupBy('vendor_id')
            ->with('vendor')
            ->get();

        // Non-student customers: receipts aren't tracked against AR yet, but list active customers
        $customers = Customer::where('school_id', $school->id)->active()->orderBy('name')->get();

        return view('admin.reports.debtor-creditor', compact('debtors', 'creditors', 'customers'));
    }
}
