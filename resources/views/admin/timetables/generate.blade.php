@extends('layouts.app')

@section('title', 'Generate Timetable - ' . $timetable->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.index') }}">Timetables</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.show', $timetable) }}">{{ $timetable->name }}</a></li>
                    <li class="breadcrumb-item active">Automated Generation & Optimization</li>
                </ol>
            </nav>
            <h1 class="h3 text-gray-800 font-weight-bold mb-0">Automated Timetable Generation (Phase 3D)</h1>
            <p class="text-muted small mb-0">Deterministic, multi-candidate generation respecting Phase 3C hard constraints and optimizing soft preferences.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.timetables.requirements.index', $timetable) }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-list-check me-1"></i> Scheduling Requirements
            </a>
            <a href="{{ route('admin.timetables.candidates.index', $timetable) }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-layer-group me-1"></i> View Existing Candidates
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle me-2"></i> Generation Error:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Settings Form -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-cogs me-2"></i> Generation & Optimization Engine Parameters</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.timetables.generate.run', $timetable) }}" method="POST" id="generateForm">
                        @csrf

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label font-weight-semibold">Deterministic Seed</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-key text-muted"></i></span>
                                    <input type="text" name="seed" id="seedInput" class="form-control font-monospace" value="{{ old('seed', date('YmdHi')) }}" placeholder="e.g. 20260823">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('seedInput').value = Math.floor(Math.random()*90000000 + 10000000);">Randomize</button>
                                </div>
                                <small class="text-muted">Same seed with same inputs produces identical reproducible schedules.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label font-weight-semibold">Candidate Schedules to Produce</label>
                                <select name="candidate_count" id="candidateCount" class="form-select">
                                    <option value="1">1 Candidate (Fastest)</option>
                                    <option value="3" selected>3 Candidates (Recommended for comparison)</option>
                                    <option value="5">5 Candidates (In-depth exploration)</option>
                                </select>
                                <small class="text-muted">Generates distinct valid variations ranked by optimization score.</small>
                            </div>
                        </div>

                        <!-- Scope & Locking -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-lock me-1 text-warning"></i> Locking & Scope (Partial Regeneration)</h6>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="preserve_locked" id="preserveLocked" value="1" checked>
                                    <label class="form-check-label font-weight-bold" for="preserveLocked">
                                        Preserve Administrator-Locked Slots (<span class="badge bg-warning text-dark">{{ $timetable->slots()->where('is_locked', true)->count() }} locked</span>)
                                    </label>
                                    <div class="small text-muted">Locked allocations will remain fixed and will never be overwritten.</div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Filter Class (Optional)</label>
                                        <select name="class_id" class="form-select form-select-sm">
                                            <option value="">-- All Classes --</option>
                                            @foreach($classes as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Filter Teacher (Optional)</label>
                                        <select name="teacher_id" class="form-select form-select-sm">
                                            <option value="">-- All Teachers --</option>
                                            @foreach($teachers as $t)
                                                <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Filter Subject (Optional)</label>
                                        <select name="subject_id" class="form-select form-select-sm">
                                            <option value="">-- All Subjects --</option>
                                            @foreach($subjects as $s)
                                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Filter Day (Optional)</label>
                                        <select name="day_of_week" class="form-select form-select-sm">
                                            <option value="">-- All Days --</option>
                                            <option value="Monday">Monday</option>
                                            <option value="Tuesday">Tuesday</option>
                                            <option value="Wednesday">Wednesday</option>
                                            <option value="Thursday">Thursday</option>
                                            <option value="Friday">Friday</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Optimization Profile Weights -->
                        <div class="mb-4">
                            <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-sliders-h me-1 text-primary"></i> Soft Constraint Optimization Weights</h6>
                            <p class="text-muted small">Customize weight distribution for scoring (normalized automatically to 100.00 points).</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small font-weight-semibold d-flex justify-content-between">
                                        <span>Subject Daily Spread</span>
                                        <span class="badge bg-secondary">20 pts</span>
                                    </label>
                                    <input type="number" name="weights[subject_daily_spread]" class="form-control form-control-sm" value="20" min="0" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-weight-semibold d-flex justify-content-between">
                                        <span>Teacher Workload Balance</span>
                                        <span class="badge bg-secondary">20 pts</span>
                                    </label>
                                    <input type="number" name="weights[teacher_workload_balance]" class="form-control form-control-sm" value="20" min="0" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-weight-semibold d-flex justify-content-between">
                                        <span>Class Workload Balance</span>
                                        <span class="badge bg-secondary">15 pts</span>
                                    </label>
                                    <input type="number" name="weights[class_workload_balance]" class="form-control form-control-sm" value="15" min="0" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-weight-semibold d-flex justify-content-between">
                                        <span>Consecutive Lesson Limit</span>
                                        <span class="badge bg-secondary">15 pts</span>
                                    </label>
                                    <input type="number" name="weights[consecutive_lesson_penalty]" class="form-control form-control-sm" value="15" min="0" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-weight-semibold d-flex justify-content-between">
                                        <span>Morning Core Subjects (STEM)</span>
                                        <span class="badge bg-secondary">10 pts</span>
                                    </label>
                                    <input type="number" name="weights[core_subject_morning_preference]" class="form-control form-control-sm" value="10" min="0" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-weight-semibold d-flex justify-content-between">
                                        <span>Teacher Free Period Balance</span>
                                        <span class="badge bg-secondary">10 pts</span>
                                    </label>
                                    <input type="number" name="weights[teacher_free_period_balance]" class="form-control form-control-sm" value="10" min="0" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-weight-semibold d-flex justify-content-between">
                                        <span>Room Utilization</span>
                                        <span class="badge bg-secondary">5 pts</span>
                                    </label>
                                    <input type="number" name="weights[room_utilization]" class="form-control form-control-sm" value="5" min="0" max="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-weight-semibold d-flex justify-content-between">
                                        <span>Class Schedule Continuity (No Gaps)</span>
                                        <span class="badge bg-secondary">5 pts</span>
                                    </label>
                                    <input type="number" name="weights[gap_penalty]" class="form-control form-control-sm" value="5" min="0" max="100">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" id="btnSimulate" onclick="runLiveSimulation()">
                                <i class="fas fa-vial me-1 text-info"></i> Run Pre-Generation Simulation
                            </button>
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                                <i class="fas fa-magic me-1"></i> Generate Candidate Schedules
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Simulation & Recent Runs Panel -->
        <div class="col-lg-4">
            <!-- Live Simulation Results Card -->
            <div class="card shadow-sm border-0 mb-4" id="simulationCard" style="display: none;">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-microscope me-2 text-info"></i> Simulation Result</h6>
                    <span id="simBadge" class="badge bg-success">SAFE TO APPLY</span>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Current Score:</span>
                        <strong id="simCurrentScore">--</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Projected Score:</span>
                        <strong class="text-success" id="simProjectedScore">--</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Hard Conflicts:</span>
                        <strong class="text-danger" id="simHardConflicts">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Soft Warnings:</span>
                        <strong class="text-warning" id="simSoftWarnings">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Locked Preserved:</span>
                        <strong id="simLockedCount">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Requirements Fulfilled:</span>
                        <strong id="simFulfilled">--</strong>
                    </div>
                    <div class="alert alert-info py-2 small mb-0 mt-3" id="simAlert">
                        Simulation evaluated without modifying database state.
                    </div>
                </div>
            </div>

            <!-- Recent Generation Runs -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-history me-1 text-primary"></i> Recent Generation Runs</h6>
                </div>
                <div class="card-body p-0">
                    @if($recentRuns->isEmpty())
                        <div class="p-4 text-center text-muted small">
                            No previous generation runs recorded for this timetable.
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($recentRuns as $run)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="font-weight-bold text-dark">Seed: {{ $run->seed }}</div>
                                        <div class="small text-muted">{{ $run->started_at?->diffForHumans() ?? $run->created_at->diffForHumans() }} &bull; {{ $run->candidates_count }} candidates</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary rounded-pill">{{ number_format($run->score, 1) }} pts</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function runLiveSimulation() {
    const btn = document.getElementById('btnSimulate');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Simulating...';

    const form = document.getElementById('generateForm');
    const formData = new FormData(form);

    fetch("{{ route('admin.timetables.simulate', $timetable) }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-vial me-1 text-info"></i> Run Pre-Generation Simulation';

        if (data.success && data.simulation) {
            const sim = data.simulation;
            document.getElementById('simulationCard').style.display = 'block';
            document.getElementById('simCurrentScore').innerText = sim.current_score + ' pts';
            document.getElementById('simProjectedScore').innerText = sim.projected_score + ' pts (' + (sim.score_delta >= 0 ? '+' : '') + sim.score_delta + ')';
            document.getElementById('simHardConflicts').innerText = sim.hard_conflicts;
            document.getElementById('simSoftWarnings').innerText = sim.soft_warnings;
            document.getElementById('simLockedCount').innerText = sim.locked_slots_preserved;
            document.getElementById('simFulfilled').innerText = sim.requirements_fulfilled + ' / ' + (sim.requirements_fulfilled + sim.requirements_remaining);

            const badge = document.getElementById('simBadge');
            if (sim.is_safe) {
                badge.className = 'badge bg-success';
                badge.innerText = 'SAFE TO APPLY';
            } else {
                badge.className = 'badge bg-danger';
                badge.innerText = 'BLOCKED (' + sim.hard_conflicts + ' HARD CONFLICTS)';
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-vial me-1 text-info"></i> Run Pre-Generation Simulation';
        alert('Simulation failed: ' + err.message);
    });
}
</script>
@endsection
