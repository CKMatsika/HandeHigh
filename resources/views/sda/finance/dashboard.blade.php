@extends('layouts.app')

@section('title', 'Finance Committee Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Finance Committee Dashboard</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('sda.reports.create') }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-alt mr-1"></i> Treasurer Report
            </a>
            <a href="{{ route('sda.resolutions.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-gavel mr-1"></i> Budget Resolution
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Budget Resolutions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['budget_resolutions'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                                Pending Approval</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_approval'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Financial Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['financial_tasks'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Overdue Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['overdue_tasks'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Financial Resolutions -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Financial Resolutions</h6>
                </div>
                <div class="card-body">
                    @if($financialResolutions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Resolution</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Deadline</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($financialResolutions as $resolution)
                                        <tr>
                                            <td>
                                                <a href="{{ route('sda.resolutions.show', $resolution) }}">{{ $resolution->title }}</a>
                                            </td>
                                            <td>
                                                @if($resolution->description && preg_match('/\$\d+/', $resolution->description, $matches))
                                                    <span class="text-success font-weight-bold">{{ $matches[0] }}</span>
                                                @else
                                                    <span class="text-muted">Not specified</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $resolution->isPassed() ? 'success' : ($resolution->status === 'rejected' ? 'danger' : 'secondary') }}">
                                                    {{ ucfirst($resolution->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($resolution->implementation_deadline)
                                                    @if($resolution->isOverdue())
                                                        <span class="text-danger">{{ $resolution->implementation_deadline->format('M d, Y') }}</span>
                                                    @else
                                                        {{ $resolution->implementation_deadline->format('M d, Y') }}
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('sda.resolutions.show', $resolution) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                @if($resolution->isPassed() && !$resolution->isImplemented())
                                                    <form action="{{ route('sda.resolutions.implement', $resolution) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Mark as implemented?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No financial resolutions found.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Finance Tasks -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">My Financial Tasks</h6>
                </div>
                <div class="card-body">
                    @if($financeTasks->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($financeTasks as $task)
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
                                        <a href="{{ route('sda.tasks.show', $task) }}" class="btn btn-sm btn-outline-primary mt-1">View</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No financial tasks assigned.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Financial Reports -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Financial Reports</h6>
                </div>
                <div class="card-body">
                    @if($financialReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($financialReports as $report)
                                        <tr>
                                            <td>{{ $report->title }}</td>
                                            <td>
                                                <span class="badge badge-success">{{ ucfirst($report->report_type) }}</span>
                                            </td>
                                            <td>{{ $report->getFormattedPeriod() }}</td>
                                            <td>
                                                <span class="badge badge-{{ $report->status === 'published' ? 'success' : ($report->status === 'approved' ? 'info' : 'secondary') }}">
                                                    {{ ucfirst($report->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('sda.reports.show', $report) }}" class="btn btn-sm btn-outline-primary">View</a>
                                                @if($report->file_path)
                                                    <a href="{{ route('sda.reports.download', $report) }}" class="btn btn-sm btn-outline-dark">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No financial reports available.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upcoming Finance Meetings -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Upcoming Finance Meetings</h6>
                </div>
                <div class="card-body">
                    @if($financeMeetings->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Title</th>
                                        <th>Agenda</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($financeMeetings as $meeting)
                                        <tr>
                                            <td>{{ $meeting->meeting_date->format('M d, Y h:i A') }}</td>
                                            <td>{{ $meeting->title }}</td>
                                            <td>
                                                @if($meeting->agenda)
                                                    <small class="text-muted">{{ Str::limit($meeting->agenda, 50) }}</small>
                                                @else
                                                    <small class="text-muted">No agenda</small>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('sda.meetings.show', $meeting) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No upcoming finance meetings.</p>
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
                            <div class="card border-left-success h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                                    <h6>Treasurer Report</h6>
                                    <p class="text-muted small">Create financial report</p>
                                    <a href="{{ route('sda.reports.create') }}" class="btn btn-success btn-sm">Create</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-primary h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-gavel fa-3x text-primary mb-3"></i>
                                    <h6>Budget Resolution</h6>
                                    <p class="text-muted small">Propose budget allocation</p>
                                    <a href="{{ route('sda.resolutions.create') }}" class="btn btn-primary btn-sm">Propose</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-info h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-tasks fa-3x text-info mb-3"></i>
                                    <h6>View Tasks</h6>
                                    <p class="text-muted small">Financial tasks assigned</p>
                                    <a href="{{ route('sda.tasks.index') }}" class="btn btn-info btn-sm">View</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card border-left-warning h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-dollar-sign fa-3x text-warning mb-3"></i>
                                    <h6>Financial Reports</h6>
                                    <p class="text-muted small">View all financial reports</p>
                                    <a href="{{ route('sda.reports.index') }}" class="btn btn-warning btn-sm">View</a>
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
