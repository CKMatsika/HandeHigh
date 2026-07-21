@extends('layouts.app')

@section('title', 'SDA Tasks')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">SDA Tasks</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.sda.tasks.overdue') }}" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-exclamation-triangle mr-1"></i> Overdue
            </a>
            <a href="{{ route('admin.sda.tasks.high-priority') }}" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-star mr-1"></i> High Priority
            </a>
            <a href="{{ route('admin.sda.tasks.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus mr-1"></i> New Task
            </a>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Tasks</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Assigned To</th>
                            <th>Committee</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Days Remaining</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.sda.tasks.show', $task) }}" class="text-decoration-none">
                                        {{ $task->title }}
                                    </a>
                                    @if($task->description)
                                        <br><small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                                    @endif
                                    @if($task->resolution)
                                        <br><small class="text-info">
                                            <i class="fas fa-gavel"></i> Resolution: {{ $task->resolution->title }}
                                        </small>
                                    @endif
                                </td>
                                <td>{{ $task->assignee->name }}</td>
                                <td>{{ $task->committee->name }}</td>
                                <td>
                                    <span class="badge badge-{{ $task->getPriorityColor() }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $task->getStatusColor() }}">
                                        {{ ucfirst($task->status) }}
                                    </span>
                                </td>
                                <td>{{ $task->due_date->format('M d, Y') }}</td>
                                <td>
                                    @if($task->isOverdue())
                                        <span class="text-danger font-weight-bold">{{ $task->getDaysUntilDue() }}</span>
                                    @elseif($task->isCompleted())
                                        <span class="text-success">Completed</span>
                                    @else
                                        <span class="text-{{ $task->due_date->diffInDays(now()) <= 3 ? 'warning font-weight-bold' : 'muted' }}">
                                            {{ $task->getDaysUntilDue() }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.sda.tasks.show', $task) }}" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(!$task->isCompleted() && $task->status !== 'cancelled')
                                            <a href="{{ route('admin.sda.tasks.edit', $task) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if($task->status === 'pending')
                                            <form action="{{ route('admin.sda.tasks.start', $task) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-info" title="Start Task" onclick="return confirm('Start this task?')">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($task->canBeCompleted())
                                            <form action="{{ route('admin.sda.tasks.complete', $task) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Complete Task" onclick="return confirm('Complete this task?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if(!$task->isCompleted() && $task->status !== 'cancelled')
                                            <form action="{{ route('admin.sda.tasks.reassign', $task) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" title="Reassign" onclick="return confirm('Reassign this task?')">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if(!$task->isCompleted())
                                            <form action="{{ route('admin.sda.tasks.cancel', $task) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger" title="Cancel" onclick="return confirm('Cancel this task?')">
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
