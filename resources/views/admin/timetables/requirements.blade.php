@extends('layouts.app')

@section('title', 'Scheduling Requirements - ' . $timetable->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.index') }}">Timetables</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.timetables.show', $timetable) }}">{{ $timetable->name }}</a></li>
                    <li class="breadcrumb-item active">Scheduling Requirements</li>
                </ol>
            </nav>
            <h1 class="h3 text-gray-800 font-weight-bold mb-0">Timetable Scheduling Requirements</h1>
            <p class="text-muted small mb-0">Specify required lesson counts, teacher bindings, specialist rooms, and priorities per class & subject.</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.timetables.requirements.import', $timetable) }}" method="POST" onsubmit="return confirm('Import curriculum weekly requirements into this timetable?');">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-file-import me-1"></i> Import from Curriculum ({{ $curriculumCount }})
                </button>
            </form>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRequirementModal">
                <i class="fas fa-plus me-1"></i> Add Custom Requirement
            </button>
            <a href="{{ route('admin.timetables.generate.show', $timetable) }}" class="btn btn-success btn-sm font-weight-bold">
                <i class="fas fa-magic me-1"></i> Proceed to Generator
            </a>
            <a href="{{ route('admin.timetables.show', $timetable) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-tasks me-2 text-primary"></i> Active Requirements Table ({{ $requirements->count() }})</h6>
            @if($requirements->isEmpty())
                <span class="badge bg-warning text-dark">No custom requirements configured (using Curriculum fallback)</span>
            @else
                <span class="badge bg-success">Explicit Requirements Configured</span>
            @endif
        </div>
        <div class="card-body p-0">
            @if($requirements->isEmpty())
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-clipboard-list fa-3x mb-3 text-gray-300"></i>
                    <p class="mb-3">No explicit scheduling requirements have been created for this timetable yet.</p>
                    <p class="small text-muted mb-4">The generator will dynamically read requirements directly from the active school Curriculum, or you can import and customize them below.</p>
                    <form action="{{ route('admin.timetables.requirements.import', $timetable) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-import me-1"></i> Import Curriculum Requirements Now
                        </button>
                    </form>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Class</th>
                                <th>Subject</th>
                                <th>Assigned Teacher</th>
                                <th>Preferred Room</th>
                                <th>Weekly Periods</th>
                                <th>Max Daily</th>
                                <th>Priority</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requirements as $req)
                                <tr>
                                    <td class="ps-4 font-weight-bold">{{ $req->schoolClass?->name ?? 'Class #' . $req->school_class_id }}</td>
                                    <td><span class="badge bg-light text-primary border font-weight-semibold">{{ $req->subject?->name ?? 'Subject #' . $req->subject_id }}</span></td>
                                    <td>{{ $req->teacher?->full_name ?? 'Any eligible teacher' }}</td>
                                    <td>{{ $req->room?->name ?? 'Any standard room' }}</td>
                                    <td><span class="badge bg-primary rounded-pill">{{ $req->weekly_periods }} / week</span></td>
                                    <td>{{ $req->max_daily_lessons }} max</td>
                                    <td>
                                        @if($req->priority >= 8)
                                            <span class="badge bg-danger">Critical ({{ $req->priority }})</span>
                                        @elseif($req->priority >= 4)
                                            <span class="badge bg-warning text-dark">High ({{ $req->priority }})</span>
                                        @else
                                            <span class="badge bg-secondary">Normal ({{ $req->priority }})</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="{{ route('admin.timetables.requirements.destroy', [$timetable, $req]) }}" method="POST" onsubmit="return confirm('Remove this scheduling requirement?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
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

<!-- Add Requirement Modal -->
<div class="modal fade" id="addRequirementModal" tabindex="-1" aria-labelledby="addRequirementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.timetables.requirements.store', $timetable) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold" id="addRequirementModalLabel"><i class="fas fa-plus-circle me-1"></i> Add Scheduling Requirement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-semibold">Class</label>
                        <select name="school_class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-semibold">Subject</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-semibold">Teacher (Optional)</label>
                        <select name="teacher_id" class="form-select">
                            <option value="">-- Auto-Assign Eligible Teacher --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-semibold">Specialist Room (Optional)</label>
                        <select name="room_id" class="form-select">
                            <option value="">-- Any Standard Room --</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-weight-semibold">Weekly Periods</label>
                            <input type="number" name="weekly_periods" class="form-control" value="4" min="1" max="20" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-weight-semibold">Max Daily Lessons</label>
                            <input type="number" name="max_daily_lessons" class="form-control" value="2" min="1" max="5" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-semibold">Allocation Priority</label>
                        <select name="priority" class="form-select">
                            <option value="1">1 - Standard Subject</option>
                            <option value="5">5 - Core / STEM Subject</option>
                            <option value="10">10 - Critical / Specialist Lab Requirement</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">Save Requirement</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
