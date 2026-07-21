@extends('layouts.app')

@section('title', $meeting->title . ' - Meeting Details')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="{{ route('admin.sda.meetings.index') }}" class="text-decoration-none text-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
            {{ $meeting->title }}
        </h1>
        <div class="btn-group" role="group">
            @if($meeting->canBeEdited())
                <a href="{{ route('admin.sda.meetings.edit', $meeting) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
            @endif
            @if($meeting->status === 'scheduled')
                <form action="{{ route('admin.sda.meetings.start', $meeting) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Start this meeting?')">
                        <i class="fas fa-play mr-1"></i> Start Meeting
                    </button>
                </form>
            @endif
            @if($meeting->status === 'in_progress')
                <form action="{{ route('admin.sda.meetings.complete', $meeting) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-info" onclick="return confirm('Complete this meeting?')">
                        <i class="fas fa-check mr-1"></i> Complete Meeting
                    </button>
                </form>
            @endif
            @if($meeting->status === 'completed' && !$meeting->minutes)
                <a href="{{ route('admin.sda.minutes.create', $meeting) }}"  }}" class . class=" .
                    btn . btn . btn . sm . btn
                    . primary . >
                    <i class="fas fa-file-alt mr-1"></i> Create Minutes
                </a>
            @endif
        </div>
    </div>

    <!-- Meeting Details -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Meeting Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Meeting Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Committee:</strong> {{ $meeting->committee->name }}</p>
                            <p><strong>Date & Time:</strong> {{ $meeting->meeting_date->format('M d, Y h:i A') }}</p>
                            <p><strong>Venue:</strong> {{ $meeting->venue ?: 'Not specified' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Type:</strong> 
                                <span class="badge badge-{{ $meeting->meeting_type === 'emergency' ? 'danger' : ($meeting->meeting_type === 'annual' ? 'success' : 'info') }}">
                                    {{ ucfirst($meeting->meeting_type) }}
                                </span>
                            </p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-{{ $meeting->status === 'completed' ? 'success' : ($meeting->status === 'cancelled' ? 'danger' : ($meeting->status === 'in_progress' ? 'warning' : 'secondary')) }}">
                                    {{ ucfirst($meeting->status) }}
                                </span>
                            </p>
                            @if($meeting->duration_minutes)
                                <p><strong>Duration:</strong> {{ $meeting->duration_minutes }} minutes</p>
                            @endif
                        </div>
                    </div>
                    
                    @if($meeting->description)
                        <div class="mt-3">
                            <strong>Description:</strong>
                            <p>{{ $meeting->description }}</p>
                        </div>
                    @endif
                    
                    @if($meeting->agenda)
                        <div class="mt-3">
                            <strong>Agenda:</strong>
                            <div class="mt-2 p-3 bg-light rounded">
                                {{ nl2br(e($meeting->agenda)) }}
                            </div>
                        </div>
                    @endif
                    
                    @if($meeting->cancellation_reason)
                        <div class="mt-3 alert alert-danger">
                            <strong>Cancellation Reason:</strong> {{ $meeting->cancellation_reason }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Attendance -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance</h6>
                    @if($meeting->status !== 'scheduled')
                        <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#attendanceModal">
                            <i class="fas fa-user-check mr-1"></i> Record Attendance
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if($meeting->attendances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Arrival Time</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($meeting->attendances as $attendance)
                                        <tr>
                                            <td>{{ $attendance->user->name }}</td>
                                            <td>{{ $attendance->user->sdaCommitteeMembers->where('sda_committee_id', $meeting->committee->id)->first()?->role->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $attendance->attendance_status === 'present' ? 'success' : ($attendance->attendance_status === 'absent' ? 'danger' : ($attendance->attendance_status === 'late' ? 'warning' : 'secondary')) }}">
                                                    {{ ucfirst($attendance->attendance_status) }}
                                                </span>
                                            </td>
                                            <td>{{ $attendance->arrival_time ? $attendance->arrival_time->format('h:i A') : '-' }}</td>
                                            <td>{{ $attendance->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                Attendance: {{ $meeting->getAttendanceCount() }}/{{ $meeting->getTotalMembers() }} 
                                ({{ round(($meeting->getAttendanceCount() / $meeting->getTotalMembers()) * 100, 1) }}%)
                            </small>
                        </div>
                    @else
                        <p class="text-muted">No attendance recorded yet.</p>
                    @endif
                </div>
            </div>

            <!-- Resolutions -->
            @if($meeting->resolutions->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Resolutions</h6>
                    </div>
                    <div class="card-body">
                        @foreach($meeting->resolutions as $resolution)
                            <div class="border-bottom pb-3 mb-3">
                                <h6>{{ $resolution->title }}</h6>
                                <p class="text-muted small">{{ Str::limit($resolution->description, 150) }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge badge-{{ $resolution->isPassed() ? 'success' : ($resolution->status === 'rejected' ? 'danger' : 'secondary') }}">
                                        {{ ucfirst($resolution->status) }}
                                    </span>
                                    <a href="{{ route('admin.sda.resolutions.show', $resolution) }}" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Meeting Minutes -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Meeting Minutes</h6>
                </div>
                <div class="card-body">
                    @if($meeting->minutes)
                        <div class="mb-3">
                            <span class="badge badge-{{ $meeting->minutes->status === 'published' ? 'success' : ($meeting->minutes->status === 'approved' ? 'info' : 'secondary') }}">
                                {{ ucfirst($meeting->minutes->status) }}
                            </span>
                        </div>
                        <p class="text-muted small">
                            Recorded by: {{ $meeting->minutes->recorder->name }}<br>
                            {{ $meeting->minutes->created_at->format('M d, Y h:i A') }}
                        </p>
                        <a href="{{ route('admin.sda.minutes.show', $meeting->minutes) }}" class="btn btn-sm btn-primary btn-block">
                            <i class="fas fa-file-alt mr-1"></i> View Minutes
                        </a>
                    @else
                        @if($meeting->status === 'completed')
                            <p class="text-muted">No minutes created yet.</p>
                            <a href="{{ route('admin.sda.minutes.create', $meeting) }}" class="btn btn-sm btn-primary btn-block">
                                <i class="fas fa-plus mr-1"></i> Create Minutes
                            </a>
                        @else
                            <p class="text-muted">Minutes can be created after the meeting is completed.</p>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            @if($meeting->status === 'scheduled')
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <form action="{{ route('admin.sda.meetings.start', $meeting) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success" onclick="return confirm('Start this meeting?')">
                                    <i class="fas fa-play mr-1"></i> Start Meeting
                                </button>
                            </form>
                            <form action="{{ route('admin.sda.meetings.cancel', $meeting) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel this meeting?')">
                                    <i class="fas fa-times mr-1"></i> Cancel Meeting
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Committee Members -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Committee Members</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($meeting->committee->activeMembers as $member)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <strong>{{ $member->user->name }}</strong><br>
                                    <small class="text-muted">{{ $member->role->name }}</small>
                                </div>
                                @if($member->is_chairperson)
                                    <span class="badge badge-primary">Chair</span>
                                @elseif($member->is_secretary)
                                    <span class="badge badge-info">Secretary</span>
                                @elseif($member->is_treasurer)
                                    <span class="badge badge-success">Treasurer</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Modal -->
@if($meeting->status !== 'scheduled')
<div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Attendance</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.sda.meetings.attendance', $meeting) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Arrival Time</th>
                                    <th>Apology Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableMembers as $member)
                                    <tr>
                                        <td>{{ $member->user->name }}</td>
                                        <td>{{ $member->role->name }}</td>
                                        <td>
                                            <select name="attendances[{{ $member->user_id }}][attendance_status]" class="form-control form-control-sm">
                                                <option value="present">Present</option>
                                                <option value="absent">Absent</option>
                                                <option value="apologized">Apologized</option>
                                                <option value="late">Late</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" name="attendances[{{ $member->user_id }}][arrival_time]" class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="text" name="attendances[{{ $member->user_id }}][apology_reason]" class="form-control form-control-sm" placeholder="Reason for absence">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
