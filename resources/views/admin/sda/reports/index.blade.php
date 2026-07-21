@extends('layouts.app')

@section('title', 'SDA Reports')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">SDA Reports</h1>
        <a href="{{ route('admin.sda.reports.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> New Report
        </a>
    </div>

    <!-- Reports List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Reports</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>Committee</th>
                            <th>Type</th>
                            <th>Submitted By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Public</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.sda.reports.show', $report) }}" class="text-decoration-none">
                                        {{ $report->title }}
                                    </a>
                                    @if($report->executive_summary)
                                        <br><small class="text-muted">{{ Str::limit($report->executive_summary, 50) }}</small>
                                    @endif
                                </td>
                                <td>{{ $report->committee->name }}</td>
                                <td>
                                    <span class="badge badge-{{ $report->report_type === 'chairman' ? 'primary' : ($report->report_type === 'treasurer' ? 'success' : ($report->report_type === 'secretary' ? 'info' : 'secondary')) }}">
                                        {{ ucfirst($report->report_type) }}
                                    </span>
                                </td>
                                <td>{{ $report->submitter->name }}</td>
                                <td>{{ $report->getFormattedPeriod() }}</td>
                                <td>
                                    <span class="badge badge-{{ $report->status === 'published' ? 'success' : ($report->status === 'approved' ? 'info' : ($report->status === 'review' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($report->is_public)
                                        <i class="fas fa-globe text-success"></i>
                                    @else
                                        <i class="fas fa-lock text-muted"></i>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.sda.reports.show', $report) }}" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($report->canBeEdited())
                                            <a href="{{ route('admin.sda.reports.edit', $report) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if($report->status === 'draft')
                                            <form action="{{ route('admin.sda.reports.submit', $report) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" title="Submit" onclick="return confirm('Submit report for review?')">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($report->canBeReviewed())
                                            <form action="{{ route('admin.sda.reports.review', $report) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info" title="Review" onclick="return confirm('Review this report?')">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($report->canBePublished())
                                            <form action="{{ route('admin.sda.reports.publish', $report) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Publish" onclick="return confirm('Publish this report?')">
                                                    <i class="fas fa-globe"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($report->file_path)
                                            <a href="{{ route('admin.sda.reports.download', $report) }}" class="btn btn-outline-dark" title="Download Attachment">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
