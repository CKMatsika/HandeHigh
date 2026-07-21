@extends('layouts.app')

@section('title', 'SDA Resolutions')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">SDA Resolutions</h1>
        <a href="{{ route('admin.sda.resolutions.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> New Resolution
        </a>
    </div>

    <!-- Resolutions List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Resolutions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Resolution</th>
                            <th>Meeting</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Votes</th>
                            <th>Deadline</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resolutions as $resolution)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.sda.resolutions.show', $resolution) }}" class="text-decoration-none">
                                        {{ $resolution->title }}
                                    </a>
                                    @if($resolution->description)
                                        <br><small class="text-muted">{{ Str::limit($resolution->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>{{ $resolution->meeting->committee->name }}</td>
                                <td>
                                    <span class="badge badge-{{ $resolution->resolution_type === 'budget' ? 'success' : ($resolution->resolution_type === 'policy' ? 'info' : 'secondary') }}">
                                        {{ ucfirst($resolution->resolution_type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $resolution->priority === 'urgent' ? 'danger' : ($resolution->priority === 'high' ? 'warning' : ($resolution->priority === 'medium' ? 'info' : 'secondary')) }}">
                                        {{ ucfirst($resolution->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $resolution->isPassed() ? 'success' : ($resolution->status === 'rejected' ? 'danger' : 'secondary') }}">
                                        {{ ucfirst($resolution->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($resolution->votes_for > 0)
                                        {{ $resolution->votes_for }}/{{ $resolution->votes_for + $resolution->votes_against + $resolution->votes_abstained }}
                                    @else
                                        Not voted
                                    @endif
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
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.sda.resolutions.show', $resolution) }}" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(in_array($resolution->status, ['proposed', 'seconded']))
                                            <a href="{{ route('admin.sda.resolutions.edit', $resolution) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if($resolution->status === 'proposed')
                                            <form action="{{ route('admin.sda.resolutions.second', $resolution) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" title="Second" onclick="return confirm('Second this resolution?')">
                                                    <i class="fas fa-hand-paper"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($resolution->status === 'seconded')
                                            <form action="{{ route('admin.sda.resolutions.debate', $resolution) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info" title="Start Debate" onclick="return confirm('Start debate on this resolution?')">
                                                    <i class="fas fa-comments"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($resolution->canBeVoted())
                                            <form action="{{ route('admin.sda.resolutions.vote', $resolution) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Vote" onclick="return confirm('Record vote on this resolution?')">
                                                    <i class="fas fa-vote-yea"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($resolution->isPassed() && !$resolution->isImplemented())
                                            <form action="{{ route('admin.sda.resolutions.implement', $resolution) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info" title="Mark as Implemented" onclick="return confirm('Mark this resolution as implemented?')">
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if(!in_array($resolution->status, ['implemented', 'cancelled']))
                                            <form action="{{ route('admin.sda.resolutions.cancel', $resolution) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this resolution?')">
                                                    <i class="fas fa-times"></i>
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
        </div>
    </div>
</div>
@endsection
