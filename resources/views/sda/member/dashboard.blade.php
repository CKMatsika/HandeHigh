@extends('layouts.app')

@section('title', 'Committee Member Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">SDA Member Dashboard</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('sda.resolutions.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-gavel mr-1"></i> Propose Resolution
            </a>
            <a href="{{ route('sda.reports.create') }}" class="btn btn-sm btn-info">
                <i class="fas fa-file-alt mr-1"></i> Create Report
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                My Committees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['my_committees'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                My Resolutions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['my_resolutions'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-gavel fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                My Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['my_tasks'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Meeting Attendance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['attendance_rate'] }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- My Committees -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Committees</h6>
                </div>
                <div class="card-body">
                    @if($myCommittees->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Committee</th>
                                        <th>Role</th>
                                        <th>Members</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myCommittees as $membership)
                                        <tr>
                                            <td>{{ $membership->committee->name }}</td>
                                            <td>
                                                <span class="badge badge-{{ $membership->is_chairperson ? 'primary' : ($membership->is_secretary ? 'info' : ($membership->is_treasurer ? 'success' : 'secondary')) }}">
                                                    {{ $membership->role->name }}
                                                </span>
                                            </td>
                                            <td>{{ $membership->committee->activeMembers()->count() }}</td>
                                            <td>
                                                <span class="text-muted">N/A</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">You are not assigned to any committees.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- My Tasks -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Tasks</h6>
                </div>
                <div class="card-body">
                    @if($myTasks->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($myTasks as $task)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <h6 class="mb-1">{{ $task->title }}</h6>
                                        <small class="text-muted">{{ $task->description }}</small>
                                        <br>
                                        <small class="text-{{ $task->isOverdue() ? 'danger' : 'muted' }}">
                                            Due: {{ $task->due_date->format('M d, Y') }}
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge badge-{{ $task->getPriorityColor() }}">{{ ucfirst($task->priority) }}</span>
                                        <br>
                                        <span class="badge badge-{{ $task->getStatusColor() }}">{{ ucfirst($task->status) }}</span>
                                        <br>
                                        <a href="{{ route('sda.tasks.show', $task) }}" class="btn btn-sm btn-outline-primary mt-1">View</a>
                                        @if($task->canBeCompleted())
                                            <form action="{{ route('sda.tasks.complete', $task) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success mt-1" onclick="return confirm('Complete this task?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No tasks assigned to you.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- My Resolutions -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Resolutions</h6>
                </div>
                <div class="card-body">
                    @if($myResolutions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Resolution</th>
                                        <th>Meeting</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myResolutions as $resolution)
                                        <tr>
                                            <td>{{ $resolution->title }}</td>
                                            <td>{{ $resolution->meeting->title }}</td>
                                            <td>
                                                <span class="badge badge-{{ $resolution->isPassed() ? 'success' : ($resolution->status === 'rejected' ? 'danger' : 'secondary') }}">
                                                    {{ ucfirst($resolution->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('sda.resolutions.show', $resolution) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                @if(in_array($resolution->status, ['proposed', 'seconded']))
                                                    <a href="{{ route('sda.resolutions.edit', $resolution) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No resolutions proposed yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upcoming Meetings -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Upcoming Meetings</h6>
                </div>
                <div class="card-body">
                    @if($upcomingMeetings->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Title</th>
                                        <th>Committee</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingMeetings as $meeting)
                                        <tr>
                                            <td>{{ $meeting->meeting_date->format('M d, Y h:i A') }}</td>
                                            <td>{{ $meeting->title }}</td>
                                            <td>{{ $meeting->committee->name }}</td>
                                            <td>
                                                <a href="{{ route('sda.meetings.show', $meeting) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                @if($meeting->agenda)
                                                    <button class="btn btn-sm btn-outline-info" onclick="alert('Agenda: {{ $meeting->agenda }}')">Agenda</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No upcoming meetings scheduled.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Meeting Attendance -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Meeting Attendance</h6>
                </div>
                <div class="card-body">
                    @if($recentAttendance->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Meeting</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentAttendance as $attendance)
                                        <tr>
                                            <td>{{ $attendance->meeting->title }}</td>
                                            <td>{{ $attendance->meeting->meeting_date->format('M d, Y') }}</td>
                                            <td>
                                                <span class="badge badge-{{ $attendance->attendance_status === 'present' ? 'success' : ($attendance->attendance_status === 'apologized' ? 'warning' : 'danger') }}">
                                                    {{ ucfirst($attendance->attendance_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('sda.meetings.show', $attendance->meeting) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                @if($attendance->meeting->minutes)
                                                    <a href="{{ route('sda.minutes.show', $attendance->meeting->minutes) }}" class="btn btn-sm btn-outline-info">Minutes</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No meeting attendance records.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- My Reports -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Reports</h6>
                </div>
                <div class="card-body">
                    @if($myReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myReports as $report)
                                        <tr>
                                            <td>{{ $report->title }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ ucfirst($report->report_type) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $report->status === 'published' ? 'success' : ($report->status === 'approved' ? 'info' : 'secondary') }}">
                                                    {{ ucfirst($report->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('sda.reports.show', $report) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                @if($report->canBeEdited())
                                                    <a href="{{ route('sda.reports.edit', $report) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No reports created yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-primary h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-gavel fa-3x text-primary mb-3"></i>
                                    <h6>Propose Resolution</h6>
                                    <p class="text-muted small">Submit new resolution</p>
                                    <a href="{{ route('sda.resolutions.create') }}" class="btn btn-primary btn-sm">Propose</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-success h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                                    <h6>Create Report</h6>
                                    <p class="text-muted small">Generate committee report</p>
                                    <a href="{{ route('sda.reports.create') }}" class="btn btn-success btn-sm">Create</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-info h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-tasks fa-3x text-info mb-3"></i>
                                    <h6>View Tasks</h6>
                                    <p class="text-muted small">My assigned tasks</p>
                                    <a href="{{ route('sda.tasks.index') }}" class="btn btn-info btn-sm">View</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-warning h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar fa-3x text-warning mb-3"></i>
                                    <h6>View Meetings</h6>
                                    <p class="text-muted small">All SDA meetings</p>
                                    <a href="{{ route('sda.meetings.index') }}" class="btn btn-warning btn-sm">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
