@extends('layouts.app')

@section('title', 'Candidate Schedules - ' . $timetable->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.index') }}">Timetables</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.show', $timetable) }}">{{ $timetable->name }}</a></li>
                    <li class="breadcrumb-item active">Candidate Schedules</li>
                </ol>
            </nav>
            <h1 class="h3 text-gray-800 font-weight-bold mb-0">Generated Candidate Schedules</h1>
            <p class="text-muted small mb-0">Compare and select from valid deterministic candidate timetables.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.timetables.generate.show', $timetable) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-magic me-1"></i> New Generation Run
            </a>
            <a href="{{ route('admin.timetables.show', $timetable) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Timetable
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Current Schedule Baseline vs Candidates Comparison -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-balance-scale me-2 text-primary"></i> Multi-Candidate Comparative Matrix</h6>
                    <span class="badge bg-light text-dark border">Current Schedule: {{ number_format($comparison['current_schedule']['score'] ?? 0, 1) }} pts</span>
                </div>
                <div class="card-body p-0">
                    @if(empty($comparison['candidates']))
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-layer-group fa-3x mb-3 text-gray-300"></i>
                            <p class="mb-3">No candidate schedules have been generated yet for this timetable.</p>
                            <a href="{{ route('admin.timetables.generate.show', $timetable) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-magic me-1"></i> Launch Candidate Generator
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Candidate</th>
                                        <th>Optimization Score</th>
                                        <th>Hard Conflicts</th>
                                        <th>Soft Warnings</th>
                                        <th>Allocated Slots</th>
                                        <th>Score Delta</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($comparison['candidates'] as $cand)
                                        <tr class="{{ $cand['is_applied'] ? 'table-success' : '' }}">
                                            <td class="ps-4 font-weight-bold">
                                                <a href="{{ route('admin.timetables.candidates.show', [$timetable, $cand['id']]) }}" class="text-decoration-none">
                                                    Candidate #{{ $cand['candidate_number'] }}
                                                </a>
                                                @if($cand['is_applied'])
                                                    <span class="badge bg-success ms-2"><i class="fas fa-check me-1"></i> Applied</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height: 8px; width: 100px;">
                                                        <div class="progress-bar bg-success" style="width: {{ $cand['score'] }}%"></div>
                                                    </div>
                                                    <span class="font-weight-bold text-dark">{{ number_format($cand['score'], 1) }} / 100</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if($cand['hard_conflicts_count'] === 0)
                                                    <span class="badge bg-success"><i class="fas fa-shield-alt me-1"></i> 0 (Valid)</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> {{ $cand['hard_conflicts_count'] }} Conflicts</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">{{ $cand['soft_warnings_count'] }} warnings</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $cand['allocations_count'] }} slots</span>
                                                @if($cand['unallocated_count'] > 0)
                                                    <span class="badge bg-danger ms-1">{{ $cand['unallocated_count'] }} unallocated</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($cand['score_delta_vs_current'] > 0)
                                                    <span class="text-success font-weight-bold">+{{ number_format($cand['score_delta_vs_current'], 1) }}</span>
                                                @elseif($cand['score_delta_vs_current'] < 0)
                                                    <span class="text-danger font-weight-bold">{{ number_format($cand['score_delta_vs_current'], 1) }}</span>
                                                @else
                                                    <span class="text-muted">0.0</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($cand['is_applied'])
                                                    <span class="badge bg-success">Active Timetable</span>
                                                @elseif($cand['hard_conflicts_count'] === 0)
                                                    <span class="badge bg-info text-dark">Ready to Apply</span>
                                                @else
                                                    <span class="badge bg-secondary">Review Needed</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="{{ route('admin.timetables.candidates.show', [$timetable, $cand['id']]) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye me-1"></i> Review
                                                    </a>
                                                    @if(! $cand['is_applied'] && $cand['hard_conflicts_count'] === 0)
                                                        <form action="{{ route('admin.timetables.candidates.apply', [$timetable, $cand['id']]) }}" method="POST" onsubmit="return confirm('Apply Candidate #{{ $cand['candidate_number'] }} to the active timetable schedule? Unlocked slots will be replaced.');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check me-1"></i> Apply
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
