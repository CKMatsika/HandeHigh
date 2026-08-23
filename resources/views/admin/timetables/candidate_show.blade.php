@extends('layouts.app')

@section('title', 'Candidate #' . $candidate->candidate_number . ' - ' . $timetable->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.index') }}">Timetables</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.show', $timetable) }}">{{ $timetable->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.candidates.index', $timetable) }}">Candidates</a></li>
                    <li class="breadcrumb-item active">Candidate #{{ $candidate->candidate_number }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <h1 class="h3 text-gray-800 font-weight-bold mb-0">Candidate Schedule #{{ $candidate->candidate_number }}</h1>
                @if($candidate->is_applied)
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> Active Applied Schedule</span>
                @else
                    <span class="badge bg-info text-dark">Generated Candidate</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @if(! $candidate->is_applied && $candidate->hard_conflicts_count === 0)
                <form action="{{ route('admin.timetables.candidates.apply', [$timetable, $candidate]) }}" method="POST" onsubmit="return confirm('Are you sure you want to apply this candidate schedule? This will update the active timetable slots.');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold">
                        <i class="fas fa-check-circle me-1"></i> Apply Candidate to Timetable
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.timetables.candidates.index', $timetable) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Candidate List
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle me-2"></i> Application Blocked:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Score & Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Optimization Score</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($candidate->score, 2) }} / 100</div>
                    <small class="text-muted">Normalized 8-category evaluation</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-{{ $candidate->hard_conflicts_count === 0 ? 'success' : 'danger' }} border-4">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-{{ $candidate->hard_conflicts_count === 0 ? 'success' : 'danger' }} text-uppercase mb-1">Hard Conflicts</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $candidate->hard_conflicts_count }}</div>
                    <small class="text-muted">{{ $candidate->hard_conflicts_count === 0 ? 'Passes all Phase 3C rules' : 'Must be resolved before publish' }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Soft Warnings</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $candidate->soft_warnings_count }}</div>
                    <small class="text-muted">Optimization guidance</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-info border-4">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Allocated Lessons</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ count($candidate->allocations ?? []) }}</div>
                    <small class="text-muted">{{ count($candidate->unallocated_requirements ?? []) }} unallocated requirements</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Score Breakdown Details -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-chart-pie me-2 text-primary"></i> Category Score Breakdown</h6>
                </div>
                <div class="card-body">
                    @php
                        $breakdown = $candidate->score_breakdown ?? [];
                        $categories = $breakdown['categories'] ?? [];
                        $weights = $breakdown['weights'] ?? [];
                    @endphp

                    @foreach($weights as $key => $maxWeight)
                        @php
                            $catScore = $categories[$key] ?? 0.0;
                            $pct = $maxWeight > 0 ? ($catScore / $maxWeight) * 100 : 0;
                            $label = ucwords(str_replace('_', ' ', $key));
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small font-weight-semibold text-dark">{{ $label }}</span>
                                <span class="small font-weight-bold">{{ number_format($catScore, 1) }} / {{ $maxWeight }} pts</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-info' : 'bg-warning') }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach

                    @if(!empty($breakdown['explanations']))
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="font-weight-bold small text-muted text-uppercase mb-2">Optimizer Explanations</h6>
                            <ul class="list-unstyled small mb-0 text-muted">
                                @foreach($breakdown['explanations'] as $exp)
                                    <li class="mb-1"><i class="fas fa-check-circle text-success me-1"></i> {{ $exp }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            @if(!empty($candidate->unallocated_requirements))
                <div class="card shadow-sm border-0 border-start border-danger border-4 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-exclamation-circle me-2"></i> Unallocated Requirements Diagnosis</h6>
                    </div>
                    <div class="card-body">
                        @foreach($candidate->unallocated_requirements as $unalloc)
                            <div class="alert alert-light border mb-2 py-2">
                                <div class="font-weight-bold text-dark">{{ $unalloc['class_name'] }} &bull; {{ $unalloc['subject_name'] }}</div>
                                <div class="small text-muted mb-2">Required: {{ $unalloc['required_periods'] }}, Allocated: {{ $unalloc['allocated_periods'] }} (Deficit: {{ $unalloc['deficit'] }})</div>
                                <ul class="small text-danger mb-0 ps-3">
                                    @foreach($unalloc['reasons'] as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Allocation Preview Table -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-table me-2 text-primary"></i> Generated Slot Allocations ({{ count($candidate->allocations ?? []) }})</h6>
                    <span class="badge bg-secondary font-monospace">Run Seed: {{ $candidate->generationRun?->seed ?? 'N/A' }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0 small">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3">Day</th>
                                    <th>Time</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Room</th>
                                    <th class="text-end pe-3">Lock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($candidate->allocations ?? [] as $slot)
                                    <tr>
                                        <td class="ps-3 font-weight-bold">{{ $slot['day_of_week'] }}</td>
                                        <td>{{ $slot['start_time'] }} - {{ $slot['end_time'] }}</td>
                                        <td><span class="badge bg-light text-dark border">Class #{{ $slot['school_class_id'] }}</span></td>
                                        <td><span class="font-weight-semibold text-primary">Subject #{{ $slot['subject_id'] }}</span></td>
                                        <td>{{ !empty($slot['teacher_id']) ? 'Teacher #' . $slot['teacher_id'] : '-' }}</td>
                                        <td>{{ !empty($slot['room_id']) ? 'Room #' . $slot['room_id'] : '-' }}</td>
                                        <td class="text-end pe-3">
                                            @if(!empty($slot['is_locked']))
                                                <i class="fas fa-lock text-warning" title="Preserved Locked Slot"></i>
                                            @else
                                                <i class="fas fa-unlock text-muted" title="Generated Allocation"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
