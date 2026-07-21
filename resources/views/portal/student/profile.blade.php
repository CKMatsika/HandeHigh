@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Student Profile</h1>
            <p class="text-xs text-slate-400 mt-1">Your personal information and academic details.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('student.dashboard') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back to Dashboard</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-200">
            <div><span class="text-slate-400">Full Name:</span> {{ $student ? $student->first_name . ' ' . $student->last_name . ' ' . $student->other_names : 'Not available' }}</div>
            <div><span class="text-slate-400">Gender:</span> {{ $student ? ucfirst($student->gender) : 'N/A' }}</div>
            <div><span class="text-slate-400">Date of Birth:</span> {{ $student ? $student->date_of_birth->format('M j, Y') : 'N/A' }}</div>
            <div><span class="text-slate-400">Grade:</span> {{ $student ? $student->grade : 'N/A' }}</div>
            <div><span class="text-slate-400">Class:</span> {{ $enrollment?->class?->name ?? 'Not assigned' }}</div>
            <div><span class="text-slate-400">Status:</span> {{ $student ? ucfirst($student->status) : 'N/A' }}</div>
            <div><span class="text-slate-400">Boarding:</span> {{ $student ? ($student->is_boarding ? 'Yes' : 'No') : 'N/A' }}</div>
            <div><span class="text-slate-400">Transport:</span> {{ $student ? ($student->has_transport ? 'Yes' : 'No') : 'N/A' }}</div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <h2 class="text-sm font-medium text-slate-50 mb-3">Quick Links</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('student.fees') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">View Fees</a>
            <a href="{{ route('student.results') }}" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">View Results</a>
        </div>
    </div>
@endsection
