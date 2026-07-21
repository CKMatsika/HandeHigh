@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">SDA Meetings</h1>
            <p class="text-xs text-slate-400 mt-1">View and manage School Development Association meetings.</p>
        </div>
        <a href="{{ route('sda.meetings.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Schedule Meeting
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-900/50 border border-green-700 text-green-200 rounded-lg p-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-200 rounded-lg p-4 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Committee</label>
                <select name="committee_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Committees</option>
                    @foreach($committees as $committee)
                        <option value="{{ $committee->id }}" {{ request('committee_id') == $committee->id ? 'selected' : '' }}>
                            {{ $committee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Type</label>
                <select name="meeting_type" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="regular" {{ request('meeting_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="emergency" {{ request('meeting_type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    <option value="annual" {{ request('meeting_type') == 'annual' ? 'selected' : '' }}>Annual</option>
                    <option value="special" {{ request('meeting_type') == 'special' ? 'selected' : '' }}>Special</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-3 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors text-xs">
                    Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Meetings table --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Date & Time</th>
                        <th class="px-4 py-3 text-left font-medium">Title</th>
                        <th class="px-4 py-3 text-left font-medium">Committee</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Venue</th>
                        <th class="px-4 py-3 text-left font-medium">Attendees</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($meetings as $meeting)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 text-slate-200">
                                {{ optional($meeting->meeting_date)->format('M d, Y h:i A') }}
                            </td>
                            <td class="px-4 py-3 text-slate-100">
                                {{ $meeting->title }}
                            </td>
                            <td class="px-4 py-3 text-slate-200">
                                {{ optional($meeting->committee)->name }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs bg-slate-700 text-slate-300">
                                    {{ ucfirst($meeting->meeting_type ?? 'regular') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ $meeting->venue ?? 'TBD' }}
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ $meeting->attendances->count() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('sda.meetings.show', $meeting) }}" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 transition-colors">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10m-9 4h4M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
                                    </svg>
                                    <div>No meetings found.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($meetings instanceof \Illuminate\Contracts\Pagination\Paginator && $meetings->hasPages())
            <div class="px-4 py-3 border-t border-slate-800/50">
                {{ $meetings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
