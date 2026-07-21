@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">School Setup</h1>
            <p class="text-xs text-slate-400 mt-1">Manage your school's information, settings, and activity logs.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.school-setup.edit') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit School Info
            </a>
            <a href="{{ route('admin.school-setup.settings') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-700 px-3 py-2 text-sm font-medium text-white hover:bg-slate-600 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Settings
            </a>
            <a href="{{ route('admin.school-setup.logs') }}" class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Activity Logs
            </a>
        </div>
    </div>

    <!-- School Information Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-lg font-semibold text-slate-50 mb-4">School Information</h2>
            <div class="space-y-3">
                <div class="flex items-center gap-4">
                    @if($school->logo)
                        <img src="{{ Storage::url($school->logo) }}" alt="{{ $school->name }}" class="w-16 h-16 rounded-lg object-cover">
                    @else
                        <div class="w-16 h-16 rounded-lg bg-slate-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <h3 class="text-lg font-medium text-slate-50">{{ $school->name }}</h3>
                        <p class="text-sm text-slate-400">{{ $school->motto }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Email:</span>
                        <span class="text-slate-300">{{ $school->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Phone:</span>
                        <span class="text-slate-300">{{ $school->phone }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Type:</span>
                        <span class="text-slate-300">{{ ucfirst($school->school_type) }}</span>
                    </div>
                    @if($school->established_year)
                        <div class="flex justify-between">
                            <span class="text-slate-400">Established:</span>
                            <span class="text-slate-300">{{ $school->established_year }}</span>
                        </div>
                    @endif
                </div>
                
                <div class="pt-3 border-t border-slate-800">
                    <p class="text-sm text-slate-300">{{ $school->address }}, {{ $school->city }}, {{ $school->state }}, {{ $school->country }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h2 class="text-lg font-semibold text-slate-50 mb-4">Academic Settings</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-400">Academic Year:</span>
                    <span class="text-slate-300">{{ $settings['academic_year_start'] ?? 'N/A' }} - {{ $settings['academic_year_end'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Terms per Year:</span>
                    <span class="text-slate-300">{{ $settings['terms_per_year'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Attendance Threshold:</span>
                    <span class="text-slate-300">{{ $settings['attendance_threshold'] ?? 'N/A' }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Max Class Size:</span>
                    <span class="text-slate-300">{{ $settings['max_class_size'] ?? 'N/A' }} students</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Timezone:</span>
                    <span class="text-slate-300">{{ $settings['timezone'] ?? 'UTC' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Currency:</span>
                    <span class="text-slate-300">{{ $settings['currency'] ?? 'USD' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Logs -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-50">Recent Activity</h2>
            <a href="{{ route('admin.school-setup.logs') }}" class="text-sm text-blue-400 hover:text-blue-300">
                View All Logs
            </a>
        </div>
        
        <div class="space-y-2">
            @forelse($recentLogs as $log)
                <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full 
                            {{ $log->action === 'create' ? 'bg-emerald-500' : '' }}
                            {{ $log->action === 'update' ? 'bg-blue-500' : '' }}
                            {{ $log->action === 'delete' ? 'bg-red-500' : '' }}
                            {{ $log->action === 'login' ? 'bg-amber-500' : '' }}"></div>
                        <div>
                            <div class="text-sm text-slate-300">
                                <span class="font-medium">{{ $log->user->name ?? 'System' }}</span>
                                <span class="text-slate-400">{{ ucfirst($log->action) }}</span>
                                <span class="text-slate-400">{{ $log->module ?? 'system' }}</span>
                            </div>
                            @if($log->description)
                                <div class="text-xs text-slate-400">{{ $log->description }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="text-xs text-slate-400">
                        {{ $log->created_at->diffForHumans() }}
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p>No recent activity found</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
