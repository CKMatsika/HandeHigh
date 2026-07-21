<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Assessment;
use App\Models\Result;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Book;
use App\Models\BorrowRecord;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        $school = auth()->user()->school;
        
        return view('admin.analytics.dashboard', [
            'academicStats' => $this->getAcademicStats($school),
            'financialStats' => $this->getFinancialStats($school),
            'attendanceStats' => $this->getAttendanceStats($school),
            'libraryStats' => $this->getLibraryStats($school),
            'teacherWorkload' => $this->getTeacherWorkload($school),
            'recentActivity' => $this->getRecentActivity($school),
        ]);
    }

    public function academicPerformance()
    {
        $school = auth()->user()->school;
        
        return view('admin.analytics.academic-performance', [
            'classPerformance' => $this->getClassPerformance($school),
            'subjectPerformance' => $this->getSubjectPerformance($school),
            'gradeDistribution' => $this->getGradeDistribution($school),
            'trendAnalysis' => $this->getAcademicTrends($school),
        ]);
    }

    public function financialAnalytics()
    {
        $school = auth()->user()->school;
        
        return view('admin.analytics.financial', [
            'revenueStats' => $this->getRevenueStats($school),
            'expenseStats' => $this->getExpenseStats($school),
            'paymentTrends' => $this->getPaymentTrends($school),
            'debtAnalysis' => $this->getDebtAnalysis($school),
        ]);
    }

    protected function getAcademicStats($school)
    {
        return [
            'totalStudents' => Student::where('school_id', $school->id)->count(),
            'totalTeachers' => Teacher::where('school_id', $school->id)->count(),
            'totalClasses' => SchoolClass::where('school_id', $school->id)->count(),
            'averageGPA' => $this->calculateAverageGPA($school),
            'passRate' => $this->calculatePassRate($school),
            'enrollmentRate' => $this->calculateEnrollmentRate($school),
        ];
    }

    protected function getFinancialStats($school)
    {
        $currentYear = Carbon::now()->year;
        
        return [
            'totalRevenue' => Payment::where('school_id', $school->id)
                ->whereYear('payment_date', $currentYear)
                ->sum('amount'),
            'totalExpenses' => $this->calculateTotalExpenses($school, $currentYear),
            'outstandingInvoices' => Invoice::where('school_id', $school->id)
                ->where('status', '!=', 'paid')
                ->sum('balance'),
            'collectionRate' => $this->calculateCollectionRate($school),
        ];
    }

    protected function getAttendanceStats($school)
    {
        // This would need to be implemented based on your attendance system
        return [
            'averageAttendance' => 85.5, // placeholder
            'presentToday' => 245, // placeholder
            'absentToday' => 15, // placeholder
        ];
    }

    protected function getLibraryStats($school)
    {
        return [
            'totalBooks' => Book::where('school_id', $school->id)->sum('total_copies'),
            'availableBooks' => Book::where('school_id', $school->id)->sum('available_copies'),
            'borrowedBooks' => BorrowRecord::where('school_id', $school->id)
                ->whereNull('returned_at')
                ->count(),
            'overdueBooks' => BorrowRecord::where('school_id', $school->id)
                ->whereNull('returned_at')
                ->where('due_date', '<', now())
                ->count(),
        ];
    }

    protected function getTeacherWorkload($school)
    {
        $teachers = Teacher::where('school_id', $school->id)
            ->with(['classes', 'subjects'])
            ->get();
            
        return $teachers->map(function($teacher) {
            return [
                'name' => $teacher->full_name,
                'classes' => $teacher->classes->count(),
                'subjects' => $teacher->subjects->count(),
                'students' => $this->getTeacherStudentCount($teacher),
                'hours' => $this->calculateTeacherHours($teacher),
            ];
        });
    }

    protected function getRecentActivity($school)
    {
        // This would pull from audit logs
        return [
            'recentEnrollments' => Enrollment::where('school_id', $school->id)
                ->latest()
                ->take(5)
                ->get(),
            'recentPayments' => Payment::where('school_id', $school->id)
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    protected function getClassPerformance($school)
    {
        return SchoolClass::where('school_id', $school->id)
            ->with(['students.results.assessment'])
            ->get()
            ->map(function($class) {
                $results = $class->students->flatMap->results;
                return [
                    'className' => $class->name,
                    'studentCount' => $class->students->count(),
                    'averageScore' => $results->avg('score'),
                    'passRate' => $this->calculateClassPassRate($results),
                ];
            });
    }

    protected function getSubjectPerformance($school)
    {
        // Implementation for subject-wise performance analysis
        return [];
    }

    protected function getGradeDistribution($school)
    {
        $results = Result::whereHas('student', function($query) use ($school) {
            $query->where('school_id', $school->id);
        })->get();

        return [
            'A' => $results->where('score', '>=', 80)->count(),
            'B' => $results->whereBetween('score', [70, 79])->count(),
            'C' => $results->whereBetween('score', [60, 69])->count(),
            'D' => $results->whereBetween('score', [50, 59])->count(),
            'F' => $results->where('score', '<', 50)->count(),
        ];
    }

    protected function getAcademicTrends($school)
    {
        // Implementation for trend analysis over time
        return [];
    }

    // Helper methods
    protected function calculateAverageGPA($school)
    {
        return Result::whereHas('student', function($query) use ($school) {
            $query->where('school_id', $school->id);
        })->avg('score') ?? 0;
    }

    protected function calculatePassRate($school)
    {
        $total = Result::whereHas('student', function($query) use ($school) {
            $query->where('school_id', $school->id);
        })->count();
        
        $passed = Result::whereHas('student', function($query) use ($school) {
            $query->where('school_id', $school->id);
        })->where('score', '>=', 50)->count();
        
        return $total > 0 ? ($passed / $total) * 100 : 0;
    }

    protected function calculateEnrollmentRate($school)
    {
        // Implementation based on your enrollment criteria
        return 95.5; // placeholder
    }

    protected function calculateTotalExpenses($school, $year)
    {
        // Implementation based on your expense tracking
        return 150000; // placeholder
    }

    protected function calculateCollectionRate($school)
    {
        $totalBilled = Invoice::where('school_id', $school->id)->sum('amount');
        $totalCollected = Payment::where('school_id', $school->id)->sum('amount');
        
        return $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;
    }

    protected function getTeacherStudentCount($teacher)
    {
        // Implementation to calculate total students taught by teacher
        return 45; // placeholder
    }

    protected function calculateTeacherHours($teacher)
    {
        // Implementation to calculate teaching hours
        return 25; // placeholder
    }

    protected function calculateClassPassRate($results)
    {
        $total = $results->count();
        $passed = $results->where('score', '>=', 50)->count();
        
        return $total > 0 ? ($passed / $total) * 100 : 0;
    }

    protected function getRevenueStats($school)
    {
        return [
            'monthly' => $this->getMonthlyRevenue($school),
            'yearly' => $this->getYearlyRevenue($school),
            'bySource' => $this->getRevenueBySource($school),
        ];
    }

    protected function getExpenseStats($school)
    {
        return [
            'monthly' => $this->getMonthlyExpenses($school),
            'yearly' => $this->getYearlyExpenses($school),
            'byCategory' => $this->getExpensesByCategory($school),
        ];
    }

    protected function getPaymentTrends($school)
    {
        return []; // Implementation
    }

    protected function getDebtAnalysis($school)
    {
        return []; // Implementation
    }

    // Additional helper methods for financial analytics
    protected function getMonthlyRevenue($school)
    {
        return []; // Implementation
    }

    protected function getYearlyRevenue($school)
    {
        return []; // Implementation
    }

    protected function getRevenueBySource($school)
    {
        return []; // Implementation
    }

    protected function getMonthlyExpenses($school)
    {
        return []; // Implementation
    }

    protected function getYearlyExpenses($school)
    {
        return []; // Implementation
    }

    protected function getExpensesByCategory($school)
    {
        return []; // Implementation
    }
}
