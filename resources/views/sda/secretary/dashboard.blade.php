@extends('layouts.app')

@section('title', 'Secretary Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">SDA Secretary Dashboard</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('sda.minutes.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-file-alt mr-1"></i> Create Minutes
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
                                Pending Minutes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_minutes'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
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
                                Completed Meetings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['completed_meetings'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                My Reports</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['my_reports'] }}</div>
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
        <!-- Meetings Needing Minutes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Meetings Needing Minutes</h6>
                </div>
                <div class="card-body">
                    @if($meetingsNeedingMinutes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Title</th>
                                        <th>Committee</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($meetingsNeedingMinutes as $meeting)
                                        <tr>
                                            <td>{{ $meeting->meeting_date->format('M d, Y') }}</td>
                                            <td>{{ $meeting->title }}</td>
                                            <td>{{ $meeting->committee->name }}</td>
                                            <td>
                                                <a href="{{ route('sda.minutes.create', $meeting) }}" class="btn btn-sm btn-primary">Create Minutes</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">All meetings have minutes recorded.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- My Minutes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Minutes</h6>
                </div>
                <div class="card-body">
                    @if($myMinutes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Meeting</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($myMinutes as $minute)
                                        <tr>
                                            <td>{{ $minute->meeting->title }}</td>
                                            <td>
                                                <span class="badge badge-{{ $minute->status === 'published' ? 'success' : ($minute->status === 'approved' ? 'info' : ($minute->status === 'review' ? 'warning' : 'secondary')) }}">
                                                    {{ ucfirst($minute->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $minute->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <a href="{{ route('sda.minutes.show', $minute) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                @if($minute->canBeEdited())
                                                    <a href="{{ route('sda.minutes.edit', $minute) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No minutes created yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
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
                                        <th>Venue</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingMeetings as $meeting)
                                        <tr>
                                            <td>{{ $meeting->meeting_date->format('M d, Y h:i A') }}</td>
                                            <td>{{ $meeting->title }}</td>
                                            <td>{{ $meeting->venue ?: 'Not specified' }}</td>
                                            <td>
                                                <a href="{{ route('sda.meetings.show', $meeting) }}" class="btn btn-sm btn-outline-primary">View</a>
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
                                                <span class="badge badge-{{ $report->status === 'published' ? 'success' : ($report->status === 'approved' ? 'info' : ($report->status === 'review' ? 'warning' : 'secondary')) }}">
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
                                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                                    <h6>Create Minutes</h6>
                                    <p class="text-muted small">Record meeting minutes</p>
                                    <a href="{{ route('sda.minutes.create') }}" class="btn btn-primary btn-sm">Create</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-success h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                                    <h6>Create Report</h6>
                                    <p class="text-muted small">Generate secretary report</p>
                                    <a href="{{ route('sda.reports.create') }}" class="btn btn-success btn-sm">Create</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-info h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar fa-3x text-info mb-3"></i>
                                    <h6>View Meetings</h6>
                                    <p class="text-muted small">All SDA meetings</p>
                                    <a href="{{ route('sda.meetings.index') }}" class="btn btn-info btn-sm">View</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-warning h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-gavel fa-3x text-warning mb-3"></i>
                                    <h6>View Resolutions</h6>
                                    <p class="text-muted small">Review all resolutions</p>
                                    <a href="{{ route('sda.resolutions.index') }}" class="btn btn-warning btn-sm">View</a>
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
