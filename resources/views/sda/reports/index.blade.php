@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">SDA Reports</h1>
            <p class="text-xs text-slate-400 mt-1">Manage School Development Association reports.</p>
        </div>
        <a href="{{ route('sda.reports.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Report
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

    <!-- Filters -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
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
                <select name="report_type" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="monthly" {{ request('report_type') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ request('report_type') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    <option value="annual" {{ request('report_type') == 'annual' ? 'selected' : '' }}>Annual</option>
                    <option value="special" {{ request('report_type') == 'special' ? 'selected' : '' }}>Special</option>
                    <option value="financial" {{ request('report_type') == 'financial' ? 'selected' : '' }}>Financial</option>
                    <option value="activity" {{ request('report_type') == 'activity' ? 'selected' : '' }}>Activity</option>
                </select>
            </div>
            <div>
                <select name="status" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-3 py-2 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors text-xs">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Title</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Committee</th>
                        <th class="px-4 py-3 text-left font-medium">Period</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Created</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($reports as $report)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                <div>
                                    <div class="font-medium text-slate-100">{{ $report->title }}</div>
                                    @if($report->attachments)
                                        <div class="text-xs text-slate-400 mt-1">
                                            {{ count($report->attachments) }} attachment(s)
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs bg-slate-700 text-slate-300">
                                    {{ ucfirst($report->report_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-slate-200">{{ $report->committee->name }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $report->report_period }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs
                                    @if($report->status === 'published') bg-emerald-900/50 text-emerald-300
                                    @elseif($report->status === 'approved') bg-green-900/50 text-green-300
                                    @elseif($report->status === 'under_review') bg-amber-900/50 text-amber-300
                                    @elseif($report->status === 'submitted') bg-blue-900/50 text-blue-300
                                    @elseif($report->status === 'draft') bg-slate-700 text-slate-300
                                    @else bg-gray-700 text-gray-300 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400">
                                {{ $report->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('sda.reports.show', $report) }}" 
                                       class="p-1 text-slate-400 hover:text-white" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @if($report->status === 'draft')
                                        <a href="{{ route('sda.reports.edit', $report) }}" 
                                           class="p-1 text-slate-400 hover:text-white" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    @endif
                                    @if($report->attachments && count($report->attachments) > 0)
                                        <a href="{{ route('sda.reports.download', $report) }}" 
                                           class="p-1 text-slate-400 hover:text-white" title="Download">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div>No reports found.</div>
                                    <a href="{{ route('sda.reports.create') }}" class="text-blue-400 hover:text-blue-300">Create your first report</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
            <div class="px-4 py-3 border-t border-slate-800/50">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
