@extends('layouts.app')

@section('title', 'Meeting Minutes')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Meeting Minutes</h1>
    </div>

    <!-- Minutes List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Meeting Minutes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Committee</th>
                            <th>Date</th>
                            <th>Recorded By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($minutes as $minute)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.sda.minutes.show', $minute) }}" class="text-decoration-none">
                                        {{ $minute->meeting->title }}
                                    </a>
                                </td>
                                <td>{{ $minute->meeting->committee->name }}</td>
                                <td>{{ $minute->meeting->meeting_date->format('M d, Y') }}</td>
                                <td>{{ $minute->recorder->name }}</td>
                                <td>
                                    <span class="badge badge-{{ $minute->status === 'published' ? 'success' : ($minute->status === 'approved' ? 'info' : ($minute->status === 'review' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst($minute->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.sda.minutes.show', $minute) }}" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($minute->canBeEdited())
                                            <a href="{{ route('admin.sda.minutes.edit', $minute) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if($minute->status === 'draft')
                                            <form action="{{ route('admin.sda.minutes.submit', $minute) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" title="Submit for Review" onclick="return confirm('Submit minutes for chairman review?')">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($minute->status === 'review')
                                            <form action="{{ route('admin.sda.minutes.chairman-review', $minute) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info" title="Review" onclick="return confirm('Review these minutes?')">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($minute->isApproved())
                                            <form action="{{ route('admin.sda.minutes.publish', $minute) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Publish" onclick="return confirm('Publish these minutes?')">
                                                    <i class="fas fa-globe"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($minute->isApproved())
                                            <a href="{{ route('admin.sda.minutes.download', $minute) }}" class="btn btn-outline-dark" title="Download">
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
