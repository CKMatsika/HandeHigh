<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Account;
use App\Models\Cashbook;
use App\Models\Bill;
use App\Models\Project;
use App\Services\SmartRulesService;
use App\Services\DataPrivacyService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403);
        }

        if (! $user->hasAnyRole(['super-admin', 'school-admin', 'accountant'])) {
            $dashboardRoutes = [
                'headmaster' => 'admin.dashboard.headmaster',
                'deputy-headmaster' => 'admin.dashboard.deputy-headmaster',
                'accounts-clerk' => 'admin.dashboard.accounts-clerk',
                'bursar' => 'admin.dashboard.bursar',
                'procurement-officer' => 'admin.dashboard.procurement-officer',
                'teacher' => 'teacher.dashboard',
                'student' => 'student.dashboard',
                'parent' => 'parent.dashboard',
                'librarian' => 'librarian.dashboard',
            ];

            foreach ($dashboardRoutes as $role => $route) {
                if ($user->hasRole($role)) {
                    return redirect()->route($route);
                }
            }

            abort(403);
        }

        $data = [
            'user' => $user,
            'school' => $school,
            'roles' => $user?->getRoleNames() ?? collect(),
        ];

        if ($school) {
            // Student stats (for admin/teacher views)
            if ($user->hasAnyRole(['super-admin', 'school-admin', 'teacher'])) {
                $data['total_students'] = Student::where('school_id', $school->id)->count();
                $data['active_students'] = Student::where('school_id', $school->id)
                    ->where('status', 'active')
                    ->count();
            }

            // Financial stats (for admin/accountant views)
            if ($user->hasAnyRole(['super-admin', 'school-admin', 'accountant', 'headmaster', 'deputy-head'])) {
                // Revenue and Invoice Metrics
                $data['total_invoices'] = Invoice::where('school_id', $school->id)
                    ->sum('total_amount');
                $data['total_receipts'] = Payment::where('school_id', $school->id)
                    ->where('status', 'completed')
                    ->sum('amount');
                $data['outstanding_balance'] = Invoice::where('school_id', $school->id)
                    ->sum('balance');
                
                // Expenditure Metrics
                $data['expenditure_total'] = Bill::where('school_id', $school->id)
                    ->sum('total_amount');
                $data['outstanding_to_be_paid'] = Bill::where('school_id', $school->id)
                    ->where('status', 'pending')
                    ->sum('balance');
                $data['purchase_orders_waiting'] = Bill::where('school_id', $school->id)
                    ->where('status', 'pending')
                    ->count();
                
                // Project Metrics
                $data['projects_approved'] = Project::where('school_id', $school->id)
                    ->where('status', 'approved')
                    ->count();
                $data['projects_in_progress'] = Project::where('school_id', $school->id)
                    ->where('status', 'active')
                    ->get()
                    ->map(function($project) {
                        $project->progress_percentage = $project->budget_amount > 0 
                            ? ($project->total_spent / $project->budget_amount) * 100 
                            : 0;
                        return $project;
                    });
                $data['resolutions_approved'] = Project::where('school_id', $school->id)
                    ->where('status', 'completed')
                    ->count();
                
                // Legacy metrics for backward compatibility
                $data['total_revenue'] = $data['total_receipts'];
                $data['pending_invoices'] = Invoice::where('school_id', $school->id)
                    ->where('status', 'unpaid')
                    ->count();
                $data['pending_amount'] = Invoice::where('school_id', $school->id)
                    ->where('status', 'unpaid')
                    ->sum('balance');
                    
                // Bank accounts and cashbook summary (using Account model)
                $data['bank_accounts'] = Account::where('school_id', $school->id)
                    ->where('type', 'asset')
                    ->where(function($query) {
                        $query->where('category', 'bank')
                              ->orWhere('category', 'cash')  // Include cash accounts
                              ->orWhere('code', 'like', '13%') // Bank accounts typically start with 13xx
                              ->orWhere('code', '1100');      // Cash on hand account
                    })
                    ->where('is_active', true)
                    ->withCount(['cashbookEntries as recent_transactions' => function($query) {
                        $query->where('transaction_date', '>=', now()->subDays(7));
                    }])
                    ->get();
                    
                $data['recent_transactions'] = Cashbook::where('school_id', $school->id)
                    ->with('account')
                    ->latest()
                    ->take(5)
                    ->get();
            }

            // Library stats (for admin/librarian views)
            if ($user->hasAnyRole(['super-admin', 'school-admin', 'librarian'])) {
                $data['total_books'] = Book::where('school_id', $school->id)->count();
                $data['borrowed_books'] = BorrowRecord::whereHas('book', function($q) use ($school) {
                    $q->where('school_id', $school->id);
                })->whereNull('returned_at')->count();
            }

            // Smart rules and restrictions
            if ($user->hasAnyRole(['super-admin', 'school-admin'])) {
                $smartRules = new SmartRulesService($school);
                $data['active_rules'] = [
                    'exam_restriction' => $school->getSetting('fees.restrict_exam_access', false),
                    'result_restriction' => $school->getSetting('fees.restrict_result_access', false),
                    'registration_restriction' => $school->getSetting('fees.restrict_next_term_registration', true),
                    'late_payment_penalties' => $school->getSetting('fees.late_payment_penalties', true),
                ];
            }

            // Data privacy overview
            if ($user->hasRole('super-admin')) {
                $privacyService = new DataPrivacyService($school);
                $data['privacy_report'] = $privacyService->generatePrivacyReport();
            }
        }

        return view('dashboard.index', $data);
    }
}
