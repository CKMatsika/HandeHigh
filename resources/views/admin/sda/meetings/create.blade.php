@extends('layouts.app')

@section('title', 'Schedule New Meeting')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="{{ route('admin.sda.meetings.index') }}" class="text-decoration-none text-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
            Schedule New Meeting
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.sda.meetings.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sda_committee_id">Committee <span class="text-danger">*</span></label>
                            <select class="form-control @error('sda_committee_id') is-invalid @enderror" 
                                    id="sda_committee_id" name="sda_committee_id" required>
                                <option value="">Select Committee</option>
                                @foreach($committees as $committee)
                                    <option value="{{ $committee->id }}" {{ old('sda_committee_id') == $committee->id ? 'selected' : '' }}>
                                        {{ $committee->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sda_committee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="meeting_type">Meeting Type <span class="text-danger">*</span></label>
                            <select class="form-control @error('meeting_type') is-invalid @enderror" 
                                    id="meeting_type" name="meeting_type" required>
                                <option value="">Select Type</option>
                                <option value="regular" {{ old('meeting_type') == 'regular' ? 'selected' : '' }}>Regular Meeting</option>
                                <option value="emergency" {{ old('meeting_type') == 'emergency' ? 'selected' : '' }}>Emergency Meeting</option>
                                <option value="annual" {{ old('meeting_type') == 'annual' ? 'selected' : '' }}>Annual General Meeting</option>
                                <option value="special" {{ old('meeting_type') == 'special' ? 'selected' : '' }}>Special Meeting</option>
                            </select>
                            @error('meeting_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title">Meeting Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                           id="title" name="title" value="{{ old('title') }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="meeting_date">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control @error('meeting_date') is-invalid @enderror" 
                                   id="meeting_date" name="meeting_date" required>
                            @error('meeting_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="venue">Venue</label>
                            <input type="text" class="form-control @error('venue') is-invalid @enderror" 
                                   id="venue" name="venue" value="{{ old('venue') }}" placeholder="e.g., Conference Room A">
                            @error('venue')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="duration_minutes">Duration (minutes)</label>
                            <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" 
                                   id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" 
                                   min="15" step="15">
                            @error('duration_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="agenda">Agenda</label>
                    <textarea class="form-control @error('agenda') is-invalid @enderror" 
                              id="agenda" name="agenda" rows="5" placeholder="List the agenda items for this meeting...">{{ old('agenda') }}</textarea>
                    @error('agenda')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.sda.meetings.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus mr-1"></i> Schedule Meeting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
    // Set minimum date to current datetime
    document.getElementById('meeting_date').min = new Date().toISOString().slice(0, 16);
</script>
@endsection
