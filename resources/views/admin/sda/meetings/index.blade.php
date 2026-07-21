@extends('layouts.app')

@section('title', 'SDA Meetings')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">SDA Meetings</h1>
        <a href="{{ route('admin.sda.meetings.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Schedule Meeting
        </a>
    </div>

    <!-- Meetings List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Meetings</h6>
        </div>
        <div class="card-body">
            @if($meetings->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Meeting</th>
                                <th>Committee</th>
                                <th>Date & Time</th>
                                <th>Venue</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Attendance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($meetings as $meeting)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.sda.meetings.show', $meeting) }}" class="text-decoration-none">
                                            {{ $meeting->title }}
                                        </a>
                                        @if($meeting->description)
                                            <br><small class="text-muted">{{ Str::limit($meeting->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $meeting->committee->name }}</td>
                                    <td>
                                        {{ $meeting->meeting_date->format('M d, Y h:i A') }}
                                        @if($meeting->isUpcoming())
                                            <span class="badge badge-warning">Upcoming</span>
                                        @endif
                                    </td>
                                    <td>{{ $meeting->venue ?: 'Not specified' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $meeting->meeting_type === 'emergency' ? 'danger' : ($meeting->meeting_type === 'annual' ? 'success' : 'info') }}">
                                            {{ ucfirst($meeting->meeting_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $meeting->status === 'completed' ? 'success' : ($meeting->status === 'cancelled' ? 'danger' : ($meeting->status === 'in_progress' ? 'warning' : 'secondary')) }}">
                                            {{ ucfirst($meeting->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $meeting->getAttendanceCount() }}/{{ $meeting->getTotalMembers() }}
                                        @if($meeting->getTotalMembers() > 0)
                                            <br><small class="text-muted">
                                                {{ round(($meeting->getAttendanceCount() / $meeting->getTotalMembers()) * 100, 1) }}%
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.sda.meetings.show', $meeting) }}" class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($meeting->canBeEdited())
                                                <a href="{{ route('admin.sda.meetings.edit', $meeting) }}" class="btn btn-outline-secondary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            @if($meeting->status === 'scheduled')
                                                <form action="{{ route('admin.sda.meetings.start', $meeting) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" title="Start Meeting" onclick="return confirm('Start this meeting?')">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if($meeting->status === 'in_progress')
                                                <form action="{{ route('admin.sda.meetings.complete', $meeting) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-info" title="Complete Meeting" onclick="return confirm('Complete this meeting?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if($meeting->canBeEdited())
                                                <form action="{{ route('admin.sda.meetings.destroy', $meeting) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this meeting?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-calendar-alt fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-500">No meetings scheduled yet</h5>
                    <p class="text-gray-400">Schedule your first SDA meeting to get started.</p>
                    <a href="{{ route('admin.sda.meetings.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>Schedule Meeting
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
