@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Teacher Portal</a>
                <span class="text-slate-600">/</span>
                <h1 class="text-xl font-bold tracking-tight text-slate-50">My ICT Support Requests</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1">Track status and coordinator responses for technical assistance you have requested.</p>
        </div>
        <a href="{{ route('ict.tickets.create') }}"
           class="inline-flex items-center gap-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-cyan-600/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Submit New Request
        </a>
    </div>

    <!-- Ticket List Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Ticket #</th>
                        <th class="py-3 px-4">Title & Issue</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Priority</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Technician Assigned</th>
                        <th class="py-3 px-4">Submitted</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-cyan-400 whitespace-nowrap">
                                <a href="{{ route('ict.tickets.show', $ticket) }}" class="hover:underline">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>
                            <td class="py-3.5 px-4 max-w-sm">
                                <a href="{{ route('ict.tickets.show', $ticket) }}" class="font-medium text-slate-100 hover:text-cyan-400 transition block truncate">
                                    {{ $ticket->title }}
                                </a>
                                @if($ticket->resolution_notes)
                                    <span class="text-[11px] text-emerald-400 truncate block mt-0.5">
                                        &bull; Notes: {{ Str::limit($ticket->resolution_notes, 50) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 text-[10px] rounded-full bg-slate-800 text-slate-300">
                                    {{ $ticket->category_label }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $ticket->priority_badge_class }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $ticket->status_badge_class }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-300">
                                {{ $ticket->technician?->name ?? 'Awaiting Coordinator' }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-400 text-[11px]">
                                {{ $ticket->created_at->diffForHumans() }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <a href="{{ route('ict.tickets.show', $ticket) }}"
                                   class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs transition">
                                    View &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <p class="text-sm">You haven't submitted any support requests yet.</p>
                                <a href="{{ route('ict.tickets.create') }}" class="mt-2 inline-block text-cyan-400 hover:underline text-xs">
                                    Submit your first request &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
