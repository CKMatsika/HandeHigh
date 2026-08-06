@extends('layouts.app')

@section('content')
@php
    $tabs = [
        'overview' => 'Overview',
        'qualifications' => 'Qualifications',
        'leave' => 'Leave',
        'payroll' => 'Payroll',
        'loans' => 'Loans',
    ];
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.employees.index') }}" class="text-slate-400 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div class="flex items-center gap-3">
                @if($employee->profile_photo)
                    <img src="{{ Storage::url($employee->profile_photo) }}" alt="{{ $employee->full_name }}" class="w-12 h-12 rounded-full object-cover">
                @else
                    <div class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center">
                        <span class="text-lg text-blue-400 font-bold">{{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}</span>
                    </div>
                @endif
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-slate-50">{{ $employee->full_name }}</h1>
                    <p class="text-xs text-slate-400">{{ $employee->position }} · {{ $employee->employee_id }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-xs font-medium text-white hover:bg-amber-700">Edit</a>
            @if($employee->employment_status === 'active')
                <button onclick="openTerminateModal()" class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-medium text-white hover:bg-red-700">Terminate</button>
            @endif
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-800">
        <nav class="flex gap-6">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.employees.show', ['employee' => $employee, 'tab' => $key]) }}"
                   class="pb-3 text-xs font-medium transition-colors border-b-2
                   {{ $tab === $key ? 'text-indigo-400 border-indigo-400' : 'text-slate-400 border-transparent hover:text-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Tab Content -->
    @if($tab === 'overview')
        @include('admin.employees._overview')
    @elseif($tab === 'qualifications')
        @include('admin.employees._qualifications')
    @elseif($tab === 'leave')
        @include('admin.employees._leave')
    @elseif($tab === 'payroll')
        @include('admin.employees._payroll')
    @elseif($tab === 'loans')
        @include('admin.employees._loans')
    @endif
</div>

<!-- Terminate Modal -->
<div id="terminateModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50" onclick="if(event.target===this)closeTerminateModal()">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 w-full max-w-md mx-4" onclick="event.stopPropagation()">
        <h3 class="text-lg font-semibold text-slate-50 mb-4">Terminate Employee</h3>
        <form action="{{ route('admin.employees.terminate', $employee) }}" method="POST">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Termination Date *</label>
                    <input type="date" name="termination_date" required value="{{ date('Y-m-d') }}"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Termination Reason *</label>
                    <textarea name="termination_reason" required rows="3"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="State the reason for termination..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">Confirm Termination</button>
                    <button type="button" onclick="closeTerminateModal()" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 py-2 px-4 rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openTerminateModal() { document.getElementById('terminateModal').classList.remove('hidden'); document.getElementById('terminateModal').classList.add('flex'); }
function closeTerminateModal() { document.getElementById('terminateModal').classList.add('hidden'); document.getElementById('terminateModal').classList.remove('flex'); }
</script>
@endsection
