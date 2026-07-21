@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.school-setup.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Activity Logs</h1>
            <p class="text-xs text-slate-400 mt-1">View and filter system activity logs for security and compliance.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <select name="action" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                            {{ ucfirst($action) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="module" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('-', ' ', $module)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="user_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Users</option>
                    @foreach($users as $userId => $userName)
                        <option value="{{ $userId }}" {{ request('user_id') == $userId ? 'selected' : '' }}>
                            {{ $userName }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="From">
            </div>
            <div>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="To">
            </div>
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-4 py-2 rounded-lg transition-colors">
                    Filter
                </button>
                <a href="{{ route('admin.school-setup.logs') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-4 py-2 rounded-lg transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Timestamp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Module</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-3 text-xs text-slate-300">
                                <div>
                                    <div>{{ $log->created_at->format('M d, Y H:i:s') }}</div>
                                    <div class="text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                @if($log->user)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-700 flex items-center justify-center">
                                            <span class="text-xs text-slate-300">{{ substr($log->user->name, 0, 1) }}</span>
                                        </div>
                                        <span>{{ $log->user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">System</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $log->action === 'create' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $log->action === 'update' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                    {{ $log->action === 'delete' ? 'bg-red-500/20 text-red-400' : '' }}
                                    {{ $log->action === 'login' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                    {{ $log->action === 'logout' ? 'bg-purple-500/20 text-purple-400' : '' }}
                                    {{ $log->action === 'view' ? 'bg-cyan-500/20 text-cyan-400' : '' }}
                                    {{ $log->action === 'export' ? 'bg-indigo-500/20 text-indigo-400' : '' }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                <span class="inline-flex items-center rounded-full bg-slate-800 px-2 py-1 text-xs">
                                    {{ ucfirst(str_replace('-', ' ', $log->module ?? 'system')) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                @if($log->description)
                                    <div>{{ $log->description }}</div>
                                @endif
                                @if($log->subject_id)
                                    <div class="text-slate-400">ID: {{ $log->subject_id }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">
                                {{ $log->ip_address }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                <div class="mb-4">
                                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-slate-50 mb-2">No activity logs found</h3>
                                <p class="text-sm text-slate-400">No activity logs match your current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Total Logs</p>
                    <p class="text-2xl font-bold text-slate-50">{{ $logs->total() }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Today's Activity</p>
                    <p class="text-2xl font-bold text-emerald-500">
                        {{ \App\Models\AuditLog::where('school_id', auth()->user()->school_id)->whereDate('created_at', today())->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Active Users</p>
                    <p class="text-2xl font-bold text-amber-500">
                        {{ \App\Models\AuditLog::where('school_id', auth()->user()->school_id)->whereDate('created_at', today())->distinct('user_id')->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Security Events</p>
                    <p class="text-2xl font-bold text-red-500">
                        {{ \App\Models\AuditLog::where('school_id', auth()->user()->school_id)->whereIn('action', ['login', 'logout', 'delete'])->whereDate('created_at', today())->count() }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-red-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
