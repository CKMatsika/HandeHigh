@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ict.dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; ICT Desk</a>
                <span class="text-slate-600">/</span>
                <h1 class="text-xl font-bold tracking-tight text-slate-50">Support Ticket Queue</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1">Manage, assign, and resolve ICT and system issues logged by school staff and teachers.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('ict.tickets.create') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-cyan-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Log New Ticket
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" action="{{ route('ict.tickets.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()"
                        class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-cyan-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Category</label>
                <select name="category" onchange="this.form.submit()"
                        class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-cyan-500 focus:outline-none">
                    <option value="">All Categories</option>
                    <option value="login_account" {{ request('category') === 'login_account' ? 'selected' : '' }}>Login & Account</option>
                    <option value="hardware_lab" {{ request('category') === 'hardware_lab' ? 'selected' : '' }}>Lab / Hardware</option>
                    <option value="printer_scanner" {{ request('category') === 'printer_scanner' ? 'selected' : '' }}>Printer & Scanner</option>
                    <option value="network_wifi" {{ request('category') === 'network_wifi' ? 'selected' : '' }}>Network & Wi-Fi</option>
                    <option value="erp_support" {{ request('category') === 'erp_support' ? 'selected' : '' }}>ERP System Support</option>
                    <option value="other" {{ request('category') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Priority</label>
                <select name="priority" onchange="this.form.submit()"
                        class="w-full rounded-xl bg-slate-950 border border-slate-800 px-3 py-2 text-xs text-slate-200 focus:border-cyan-500 focus:outline-none">
                    <option value="">All Priorities</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>
            <div class="flex items-end">
                <a href="{{ route('ict.tickets.index') }}" class="w-full text-center py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs transition">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Ticket #</th>
                        <th class="py-3 px-4">Requester</th>
                        <th class="py-3 px-4">Title & Issue</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Priority</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Assigned To</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-slate-200 whitespace-nowrap">
                                <a href="{{ route('ict.tickets.show', $ticket) }}" class="text-cyan-400 hover:underline">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-medium text-slate-100 block">{{ $ticket->requester?->name ?? 'Staff' }}</span>
                                <span class="text-[10px] text-slate-500">{{ $ticket->requester?->email }}</span>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <a href="{{ route('ict.tickets.show', $ticket) }}" class="font-medium text-slate-100 hover:text-cyan-400 transition block truncate">
                                    {{ $ticket->title }}
                                </a>
                                <span class="text-[11px] text-slate-400 truncate block">{{ Str::limit($ticket->description, 60) }}</span>
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
                                {{ $ticket->technician?->name ?? 'Unassigned' }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-400 text-[11px]">
                                {{ $ticket->created_at->format('M d, H:i') }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <a href="{{ route('ict.tickets.show', $ticket) }}"
                                   class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs transition">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">
                                No support tickets match your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $tickets->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
