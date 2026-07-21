<?php

namespace App\Http\Controllers\Sda;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaCommitteeMember;
use App\Models\SdaMeeting;
use App\Models\SdaMeetingAttendance;
use App\Models\SdaMeetingMinute;
use App\Models\SdaReport;
use App\Models\SdaResolution;
use App\Models\SdaTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SdaController extends Controller
{
    /**
     * Redirect to the appropriate dashboard based on user role.
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Check if user has any SDA roles
        $sdaMembership = SdaCommitteeMember::where('user_id', $user->id)
            ->with('role', 'committee')
            ->first();

        if (!$sdaMembership) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not assigned to any SDA committee.');
        }

        // Redirect based on role
        switch ($sdaMembership->role->name) {
            case 'Chairman':
                return redirect()->route('sda.chairman.dashboard');
            case 'Vice Chairman':
                return redirect()->route('sda.chairman.dashboard'); // Same as chairman for now
            case 'Secretary':
                return redirect()->route('sda.secretary.dashboard');
            case 'Treasurer':
                return redirect()->route('sda.finance.dashboard');
            case 'Finance Committee':
                return redirect()->route('sda.finance.dashboard');
            case 'Procurement Committee':
                return redirect()->route('sda.procurement.dashboard');
            default:
                return redirect()->route('sda.member.dashboard');
        }
    }

    /**
     * Chairman Dashboard
     */
    public function chairmanDashboard()
    {
        $user = Auth::user();
        
        $stats = [
            'active_committees' => SdaCommittee::active()->count(),
            'passed_resolutions' => SdaResolution::passed()->count(),
            'pending_reports' => SdaReport::where('status', 'submitted')->count(),
            'upcoming_meetings' => SdaMeeting::upcoming()->count(),
        ];

        $recentMeetings = SdaMeeting::with(['committee'])
            ->orderBy('meeting_date', 'desc')
            ->limit(5)
            ->get();

        $pendingResolutions = SdaResolution::with(['meeting'])
            ->whereIn('status', ['proposed', 'seconded', 'debated'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('sda.chairman.dashboard', compact('stats', 'recentMeetings', 'pendingResolutions'));
    }

    /**
     * Secretary Dashboard
     */
    public function secretaryDashboard()
    {
        $user = Auth::user();
        
        $stats = [
            'pending_minutes' => SdaMeetingMinute::where('status', 'draft')->count(),
            'completed_meetings' => SdaMeeting::completed()->count(),
            'my_reports' => SdaReport::where('submitted_by', $user->id)->count(),
            'upcoming_meetings' => SdaMeeting::upcoming()->count(),
        ];

        $meetingsNeedingMinutes = SdaMeeting::completed()
            ->whereDoesntHave('minutes')
            ->with(['committee'])
            ->orderBy('meeting_date', 'desc')
            ->limit(5)
            ->get();

        $myMinutes = SdaMeetingMinute::where('recorded_by', $user->id)
            ->with(['meeting'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $upcomingMeetings = SdaMeeting::upcoming()
            ->with(['committee'])
            ->orderBy('meeting_date', 'asc')
            ->limit(5)
            ->get();

        $myReports = SdaReport::where('submitted_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('sda.secretary.dashboard', compact('stats', 'meetingsNeedingMinutes', 'myMinutes', 'upcomingMeetings', 'myReports'));
    }

    /**
     * Finance Committee Dashboard
     */
    public function financeDashboard()
    {
        $user = Auth::user();
        
        $stats = [
            'budget_resolutions' => SdaResolution::where('resolution_type', 'budget')->count(),
            'pending_approval' => SdaResolution::where('resolution_type', 'budget')->whereIn('status', ['proposed', 'seconded'])->count(),
            'financial_tasks' => SdaTask::where('assigned_to', $user->id)->whereHas('resolution', function($query) {
                $query->where('resolution_type', 'budget');
            })->count(),
            'overdue_tasks' => SdaTask::where('assigned_to', $user->id)->overdue()->count(),
        ];

        $financialResolutions = SdaResolution::where('resolution_type', 'budget')
            ->with(['meeting'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $financeTasks = SdaTask::where('assigned_to', $user->id)
            ->whereHas('resolution', function($query) {
                $query->where('resolution_type', 'budget');
            })
            ->with(['resolution'])
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $financialReports = SdaReport::where('report_type', 'treasurer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $financeMeetings = SdaMeeting::upcoming()
            ->whereHas('committee', function($query) {
                $query->where('requires_financial_approval', true);
            })
            ->orderBy('meeting_date', 'asc')
            ->limit(5)
            ->get();

        return view('sda.finance.dashboard', compact('stats', 'financialResolutions', 'financeTasks', 'financialReports', 'financeMeetings'));
    }

    /**
     * Procurement Committee Dashboard
     */
    public function procurementDashboard()
    {
        $user = Auth::user();
        
        $stats = [
            'procurement_resolutions' => SdaResolution::where('resolution_type', 'procurement')->count(),
            'pending_approvals' => SdaResolution::where('resolution_type', 'procurement')->whereIn('status', ['proposed', 'seconded'])->count(),
            'completed_procurements' => SdaResolution::where('resolution_type', 'procurement')->where('status', 'implemented')->count(),
            'overdue_tasks' => SdaTask::where('assigned_to', $user->id)->overdue()->count(),
        ];

        $procurementResolutions = SdaResolution::where('resolution_type', 'procurement')
            ->with(['meeting'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $procurementTasks = SdaTask::where('assigned_to', $user->id)
            ->whereHas('resolution', function($query) {
                $query->where('resolution_type', 'procurement');
            })
            ->with(['resolution'])
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $procurementReports = SdaReport::where('report_type', 'special')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $procurementMeetings = SdaMeeting::upcoming()
            ->whereHas('committee', function($query) {
                $query->where('requires_procurement_approval', true);
            })
            ->orderBy('meeting_date', 'asc')
            ->limit(5)
            ->get();

        return view('sda.procurement.dashboard', compact('stats', 'procurementResolutions', 'procurementTasks', 'procurementReports', 'procurementMeetings'));
    }

    /**
     * Committee Member Dashboard
     */
    public function memberDashboard()
    {
        $user = Auth::user();
        
        $myCommittees = SdaCommitteeMember::where('user_id', $user->id)
            ->with(['committee', 'role'])
            ->get();

        $stats = [
            'my_committees' => $myCommittees->count(),
            'my_resolutions' => SdaResolution::where('proposed_by', $user->id)->count(),
            'my_tasks' => SdaTask::where('assigned_to', $user->id)->count(),
            'attendance_rate' => $this->calculateAttendanceRate($user->id),
        ];

        $myTasks = SdaTask::where('assigned_to', $user->id)
            ->with(['resolution', 'committee'])
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $myResolutions = SdaResolution::where('proposed_by', $user->id)
            ->with(['meeting'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $upcomingMeetings = SdaMeeting::upcoming()
            ->whereHas('committee', function($query) use ($user) {
                $query->whereHas('members', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->with(['committee'])
            ->orderBy('meeting_date', 'asc')
            ->limit(5)
            ->get();

        $recentAttendance = SdaMeetingAttendance::where('user_id', $user->id)
            ->with(['meeting'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $myReports = SdaReport::where('submitted_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('sda.member.dashboard', compact('stats', 'myCommittees', 'myTasks', 'myResolutions', 'upcomingMeetings', 'recentAttendance', 'myReports'));
    }

    /**
     * Calculate attendance rate for a user
     */
    private function calculateAttendanceRate($userId)
    {
        $totalMeetings = SdaMeetingAttendance::where('user_id', $userId)->count();
        if ($totalMeetings === 0) {
            return 0;
        }

        $presentMeetings = SdaMeetingAttendance::where('user_id', $userId)
            ->whereIn('attendance_status', ['present', 'apologized'])
            ->count();

        return round(($presentMeetings / $totalMeetings) * 100, 1);
    }
}
