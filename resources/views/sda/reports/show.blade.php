@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">{{ $report->title }}</h1>
            <p class="text-xs text-slate-400 mt-1">SDA Report - {{ ucfirst($report->report_type) }}</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('sda.reports.index') }}" class="text-slate-400 hover:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            @if($report->status === 'draft')
                <a href="{{ route('sda.reports.edit', $report) }}" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                    Edit Report
                </a>
            @endif
        </div>
    </div>

    <!-- Report Details -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div>
                <h3 class="text-xs font-medium text-slate-400 mb-1">Report Type</h3>
                <p class="text-slate-200">{{ ucfirst($report->report_type) }}</p>
            </div>
            <div>
                <h3 class="text-xs font-medium text-slate-400 mb-1">Committee</h3>
                <p class="text-slate-200">{{ $report->committee->name }}</p>
            </div>
            <div>
                <h3 class="text-xs font-medium text-slate-400 mb-1">Period</h3>
                <p class="text-slate-200">{{ $report->report_period }}</p>
            </div>
            <div>
                <h3 class="text-xs font-medium text-slate-400 mb-1">Status</h3>
                <span class="px-2 py-1 rounded-full text-xs
                    @if($report->status === 'published') bg-emerald-900/50 text-emerald-300
                    @elseif($report->status === 'approved') bg-green-900/50 text-green-300
                    @elseif($report->status === 'under_review') bg-amber-900/50 text-amber-300
                    @elseif($report->status === 'submitted') bg-blue-900/50 text-blue-300
                    @elseif($report->status === 'draft') bg-slate-700 text-slate-300
                    @else bg-gray-700 text-gray-300 @endif">
                    {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                </span>
            </div>
        </div>

        @if($report->executive_summary)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-slate-200 mb-3">Executive Summary</h3>
                <div class="bg-slate-800/50 rounded-lg p-4 text-sm text-slate-300">
                    {{ $report->executive_summary }}
                </div>
            </div>
        @endif

        <div class="mb-6">
            <h3 class="text-sm font-medium text-slate-200 mb-3">Report Content</h3>
            <div class="bg-slate-800/50 rounded-lg p-4 text-sm text-slate-300 whitespace-pre-wrap">
                {{ $report->content }}
            </div>
        </div>

        @if($report->recommendations)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-slate-200 mb-3">Recommendations</h3>
                <div class="bg-slate-800/50 rounded-lg p-4 text-sm text-slate-300 whitespace-pre-wrap">
                    {{ $report->recommendations }}
                </div>
            </div>
        @endif

        @if($report->attachments && count($report->attachments) > 0)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-slate-200 mb-3">Attachments</h3>
                <div class="space-y-2">
                    @foreach($report->attachments as $attachment)
                        <div class="flex items-center justify-between bg-slate-800/50 rounded-lg p-3">
                            <div class="flex items-center space-x-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <div class="text-sm text-slate-200">{{ $attachment['original_name'] }}</div>
                                    <div class="text-xs text-slate-400">{{ number_format($attachment['size'] / 1024, 2) }} KB</div>
                                </div>
                            </div>
                            <a href="{{ route('sda.reports.download-attachment', $report, $attachment['id']) }}" 
                               class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                Download
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex items-center justify-between pt-6 border-t border-slate-700">
            <div class="text-xs text-slate-400">
                Created on {{ $report->created_at->format('F j, Y g:i A') }}
                @if($report->updated_at->gt($report->created_at))
                    • Updated on {{ $report->updated_at->format('F j, Y g:i A') }}
                @endif
            </div>
            <div class="flex items-center space-x-2">
                @if($report->status === 'draft')
                    <form action="{{ route('sda.reports.submit', $report) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            Submit for Review
                        </button>
                    </form>
                @endif
                <a href="{{ route('sda.reports.download', $report) }}" 
                   class="px-3 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition-colors">
                    Download PDF
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
