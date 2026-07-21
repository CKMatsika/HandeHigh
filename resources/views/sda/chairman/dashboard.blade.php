@extends('layouts.app')

@section('title', 'Chairman Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">SDA Chairman Dashboard</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('sda.reports.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-file-alt mr-1"></i> Create Report
            </a>
            <a href="{{ route('sda.meetings.create') }}" class="btn btn-sm btn-success">
                <i class="fas fa-calendar-plus mr-1"></i> Schedule Meeting
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
                                Active Committees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_committees'] }}</div>
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
                                Passed Resolutions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['passed_resolutions'] }}</div>
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
                                Pending Reports</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_reports'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
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
                                Upcoming Meetings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['upcoming_meetings'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Meetings -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Meetings</h6>
                </div>
                <div class="card-body">
                    @if($recentMeetings->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentMeetings as $meeting)
                                        <tr>
                                            <td>{{ $meeting->meeting_date->format('M d, Y') }}</td>
                                            <td>{{ $meeting->title }}</td>
                                            <td>
                                                <span class="badge badge-{{ $meeting->status === 'completed' ? 'success' : ($meeting->status === 'in_progress' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($meeting->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('sda.meetings.show', $meeting) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                @if($meeting->status === 'completed' && !$meeting->minutes)
                                                    <a href="{{ route('sda.minutes.create', $meeting) }}" class="btn btn-sm btn-outline-info">Minutes</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No meetings scheduled yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pending Resolutions -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pending Resolutions</h6>
                </div>
                <div class="card-body">
                    @if($pendingResolutions->count() > 0)
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
                                    @foreach($pendingResolutions as $resolution)
                                        <tr>
                                            <td>{{ $resolution->title }}</td>
                                            <td>{{ $resolution->meeting->title }}</td>
                                            <td>
                                                <span class="badge badge-{{ $resolution->status === 'seconded' ? 'warning' : 'secondary' }}">
                                                    {{ ucfirst($resolution->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('sda.resolutions.show', $resolution) }}" class="btn btn-sm btn-outline-primary">Review</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No pending resolutions.</p>
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
                                    <i class="fas fa-calendar-plus fa-3x text-primary mb-3"></i>
                                    <h6>Schedule Meeting</h6>
                                    <p class="text-muted small">Schedule a new SDA meeting</p>
                                    <a href="{{ route('sda.meetings.create') }}" class="btn btn-primary btn-sm">Schedule</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-success h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                                    <h6>Create Report</h6>
                                    <p class="text-muted small">Generate chairman report</p>
                                    <a href="{{ route('sda.reports.create') }}" class="btn btn-success btn-sm">Create</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-info h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-gavel fa-3x text-info mb-3"></i>
                                    <h6>View Resolutions</h6>
                                    <p class="text-muted small">Review all resolutions</p>
                                    <a href="{{ route('sda.resolutions.index') }}" class="btn btn-info btn-sm">View</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-warning h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-tasks fa-3x text-warning mb-3"></i>
                                    <h6>Manage Tasks</h6>
                                    <p class="text-muted small">Track implementation tasks</p>
                                    <a href="{{ route('sda.tasks.index') }}" class="btn btn-warning btn-sm">Manage</a>
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
