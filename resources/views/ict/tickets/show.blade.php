@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ auth()->user()->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin']) ? route('ict.tickets.index') : route('ict.tickets.my-tickets') }}" class="text-xs text-slate-400 hover:text-slate-200">
                    &larr; Back to Tickets
                </a>
                <span class="text-slate-600">/</span>
                <span class="font-mono text-xs text-cyan-400">{{ $ticket->ticket_number }}</span>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-slate-50 mt-1">{{ $ticket->title }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full {{ $ticket->priority_badge_class }}">
                Priority: {{ ucfirst($ticket->priority) }}
            </span>
            <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full {{ $ticket->status_badge_class }}">
                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Ticket Information (Col 2) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Issue Description Card -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Issue Details</span>
                    <span class="text-xs text-slate-500">Category: <strong class="text-slate-300 font-medium">{{ $ticket->category_label }}</strong></span>
                </div>

                <div class="text-sm text-slate-200 leading-relaxed whitespace-pre-line bg-slate-950/60 p-4 rounded-xl border border-slate-800/80">
                    {{ $ticket->description }}
                </div>

                <div class="flex flex-wrap items-center justify-between text-xs text-slate-400 pt-2 border-t border-slate-800/60">
                    <div>
                        Reported by: <span class="text-slate-200 font-semibold">{{ $ticket->requester?->name ?? 'Staff Member' }}</span>
                        <span class="text-slate-500">({{ $ticket->requester?->email }})</span>
                    </div>
                    <div>
                        {{ $ticket->created_at->format('F d, Y \a\t H:i') }}
                    </div>
                </div>
            </div>

            <!-- Resolution Notes Card -->
            @if($ticket->resolution_notes || in_array($ticket->status, ['resolved', 'closed']))
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-950/10 p-6 space-y-3">
                    <div class="flex items-center gap-2 text-emerald-400 text-xs font-semibold uppercase tracking-wider">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Resolution Notes
                    </div>
                    <div class="text-sm text-emerald-100 bg-slate-950/60 p-4 rounded-xl border border-emerald-500/20 leading-relaxed">
                        {{ $ticket->resolution_notes ?? 'Issue has been marked as resolved by ICT.' }}
                    </div>
                    @if($ticket->resolved_at)
                        <p class="text-[11px] text-emerald-400/70">
                            Resolved at: {{ $ticket->resolved_at->format('M d, Y H:i') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Management Panel (Col 1) -->
        <div class="space-y-6">
            <!-- Details Overview -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 space-y-3 text-xs">
                <h3 class="text-xs uppercase tracking-wider font-semibold text-slate-400 border-b border-slate-800 pb-2">Ticket Summary</h3>
                <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Assigned Tech:</span>
                    <span class="text-slate-200 font-medium">{{ $ticket->technician?->name ?? 'Unassigned' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Category:</span>
                    <span class="text-slate-200">{{ $ticket->category_label }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Priority:</span>
                    <span class="text-slate-200 capitalize">{{ $ticket->priority }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-400">Created:</span>
                    <span class="text-slate-200">{{ $ticket->created_at->diffForHumans() }}</span>
                </div>
            </div>

            <!-- Resolution & Status Form for ICT Lead / Admin -->
            @if(auth()->user()->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin']))
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 space-y-4">
                    <h3 class="text-xs uppercase tracking-wider font-semibold text-cyan-400 border-b border-slate-800 pb-2">
                        Update Ticket Status
                    </h3>

                    <form method="POST" action="{{ route('ict.tickets.update-status', $ticket) }}" class="space-y-3 text-xs">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-slate-400 mb-1 font-medium">Status *</label>
                            <select name="status" required
                                    class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-200 focus:border-cyan-500 focus:outline-none">
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1 font-medium">Assign Technician</label>
                            <select name="assigned_to"
                                    class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-200 focus:border-cyan-500 focus:outline-none">
                                <option value="">Unassigned</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}" {{ $ticket->assigned_to == $tech->id ? 'selected' : '' }}>
                                        {{ $tech->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-400 mb-1 font-medium">Resolution Notes / Action Taken</label>
                            <textarea name="resolution_notes" rows="4" placeholder="Describe the resolution steps, fix applied, or updates for the teacher..."
                                      class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-slate-200 focus:border-cyan-500 focus:outline-none">{{ old('resolution_notes', $ticket->resolution_notes) }}</textarea>
                        </div>

                        <button type="submit"
                                class="w-full py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-semibold transition shadow-lg shadow-cyan-600/20">
                            Save Status & Notes
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
