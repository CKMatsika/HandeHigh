@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Profile</h1>
            <p class="text-xs text-slate-400 mt-1">Your personal information and settings.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back to Dashboard</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-200">
            <div><span class="text-slate-400">Name:</span> {{ $user->name }}</div>
            <div><span class="text-slate-400">Email:</span> {{ $user->email }}</div>
            <div><span class="text-slate-400">School:</span> {{ $school->name }}</div>
            <div><span class="text-slate-400">Role:</span> Teacher</div>
            <div><span class="text-slate-400">Joined:</span> {{ $user->created_at->format('M j, Y') }}</div>
            <div><span class="text-slate-400">Status:</span> Active</div>
        </div>
    </div>
@endsection
