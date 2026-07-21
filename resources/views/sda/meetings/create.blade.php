@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Schedule SDA Meeting</h1>
            <p class="text-xs text-slate-400 mt-1">Create a new School Development Association meeting.</p>
        </div>
        <a href="{{ route('sda.meetings.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
    </div>

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-200 rounded-lg p-4 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('sda.meetings.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Committee *</label>
                    <select name="sda_committee_id" required
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select committee</option>
                        @foreach($committees as $committee)
                            <option value="{{ $committee->id }}" {{ old('sda_committee_id') == $committee->id ? 'selected' : '' }}>
                                {{ $committee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sda_committee_id')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Meeting Title *</label>
                    <input type="text" name="title" required maxlength="255"
                           value="{{ old('title') }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Enter meeting title">
                    @error('title')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Meeting Date & Time *</label>
                    <input type="datetime-local" name="meeting_date" required
                           value="{{ old('meeting_date') }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('meeting_date')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Venue</label>
                    <input type="text" name="venue" maxlength="255"
                           value="{{ old('venue') }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g., School Hall">
                    @error('venue')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Meeting Type *</label>
                    <select name="meeting_type" required
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select type</option>
                        <option value="regular" {{ old('meeting_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                        <option value="emergency" {{ old('meeting_type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                        <option value="annual" {{ old('meeting_type') == 'annual' ? 'selected' : '' }}>Annual</option>
                        <option value="special" {{ old('meeting_type') == 'special' ? 'selected' : '' }}>Special</option>
                    </select>
                    @error('meeting_type')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" min="15"
                           value="{{ old('duration_minutes') }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g., 60">
                    @error('duration_minutes')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-2">Agenda</label>
                    <textarea name="agenda" rows="4"
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="List agenda items...">{{ old('agenda') }}</textarea>
                    @error('agenda')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-2">Description / Notes</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Optional additional details...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-700">
                <a href="{{ route('sda.meetings.index') }}" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors text-xs">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs">
                    Schedule Meeting
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
