@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Parent Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Welcome back, {{ $guardian ? $guardian->first_name : $user->name }}!</p>
            @if($user->sda_position)
                <div class="mt-1">
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">
                        <i class="fas fa-users mr-1"></i>SDA {{ $user->sda_position }}
                    </span>
                </div>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('parent.profile') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Profile</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Children</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $students->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Total Billed</p>
                    <p class="text-lg font-semibold text-slate-50">{{ number_format($invoices->sum('total_amount'), 2) }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-blue-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Amount Paid</p>
                    <p class="text-lg font-semibold text-slate-50">{{ number_format($invoices->sum(function($inv) { return $inv->total_amount - $inv->balance; }), 2) }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-green-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-green-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Balance Due</p>
                    <p class="text-lg font-semibold {{ $invoices->sum('balance') > 0 ? 'text-red-400' : 'text-green-400' }}">
                        {{ number_format($invoices->sum('balance'), 2) }}
                    </p>
                    @if($invoices->sum('balance') > 0)
                        <span class="text-xs text-red-400 block mt-1">Amount Due</span>
                    @else
                        <span class="text-xs text-green-400 block mt-1">Credit Balance</span>
                    @endif
                </div>
                <span class="h-8 w-8 rounded-full {{ $invoices->sum('balance') > 0 ? 'bg-red-500/20' : 'bg-green-500/20' }} flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full {{ $invoices->sum('balance') > 0 ? 'bg-red-400' : 'bg-green-400' }}"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Unread Messages</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $communications->where('status', 'unread')->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-indigo-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Avg Attendance</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $attendance->isNotEmpty() ? round($attendance->avg('rate')) : 0 }}%</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-cyan-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Children Overview -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">My Children</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View Details</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($students as $student)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-50">{{ $student->first_name }} {{ $student->last_name }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $student->grade }} • {{ $student->enrollments->first()?->class?->name ?? 'No Class' }}</p>
                                    
                                    <!-- SDA Position -->
                                    @if($student->sda_position)
                                        <div class="mt-2">
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">
                                                <i class="fas fa-users mr-1"></i>{{ $student->sda_position }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <!-- Student Type -->
                                    @if($student->enrollments->first()?->student_type)
                                        <div class="mt-1">
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-{{ $student->enrollments->first()->student_type_badge_color }}/20 text-{{ $student->enrollments->first()->student_type_badge_color }}-400">
                                                {{ $student->enrollments->first()->student_type_label }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <!-- Leadership Roles -->
                                    @if($student->hasLeadershipRoles())
                                        <div class="mt-1">
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400">
                                                <i class="fas fa-star mr-1"></i>{{ $student->currentPositions()->count() }} Leadership Role(s)
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <div class="flex items-center gap-2 mt-2">
                                        <a href="{{ route('parent.students.fees', $student) }}" class="text-[10px] text-indigo-400 hover:text-indigo-300 transition">Fees</a>
                                        <span class="text-[10px] text-slate-600">•</span>
                                        <a href="{{ route('parent.students.results', $student) }}" class="text-[10px] text-indigo-400 hover:text-indigo-300 transition">Results</a>
                                        <span class="text-[10px] text-slate-600">•</span>
                                        <a href="{{ route('parent.students.statement', $student) }}?academic_year={{ now()->format('Y') }}" class="text-[10px] text-purple-400 hover:text-purple-300 transition">Statement</a>
                                        {{-- Debug: Show route exists --}}
                                        @if(route('parent.students.statement', $student, false))
                                            <span class="text-green-400 text-[8px]">✓</span>
                                        @else
                                            <span class="text-red-400 text-[8px]">✗</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">
                                    {{ ucfirst($student->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No children registered</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Communications -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Recent Messages</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($communications as $comm)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ 
                                            $comm->type === 'email' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400' }}">
                                            {{ ucfirst($comm->type) }}
                                        </span>
                                        @if($comm->status === 'unread')
                                            <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                                        @endif
                                    </div>
                                    <p class="text-xs font-medium text-slate-50">{{ $comm->subject }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">From: {{ $comm->from }}</p>
                                    <p class="text-[10px] text-slate-500 mt-2">{{ $comm->date->format('M j, Y h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No messages</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">School Announcements</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($announcements as $announcement)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between mb-2">
                                <p class="text-xs font-medium text-slate-50">{{ $announcement->title }}</p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ 
                                    $announcement->priority === 'high' ? 'bg-red-500/20 text-red-400' : 
                                    ($announcement->priority === 'medium' ? 'bg-amber-500/20 text-amber-400' : 
                                    'bg-slate-700 text-slate-400') }}">
                                    {{ ucfirst($announcement->priority) }}
                                </span>
                            </div>
                            <p class="text-[10px] text-slate-400">{{ $announcement->message }}</p>
                            <p class="text-[10px] text-slate-500 mt-2">{{ $announcement->date->format('M j, Y') }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No announcements</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Results & Attendance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Recent Results -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Recent Results</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All Results</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($results as $result)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $result->student->first_name }} {{ $result->student->last_name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $result->subject->name }} • {{ $result->created_at->format('M j, Y') }}</p>
                        </div>
                        <span class="text-xs font-semibold {{ 
                            $result->score >= 80 ? 'text-emerald-400' : 
                            ($result->score >= 60 ? 'text-amber-400' : 'text-red-400') }}">
                            {{ $result->score }}%
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No results available</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Recent Attendance</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View Full Report</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($attendance as $record)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $record->student }}</p>
                            <p class="text-[10px] text-slate-400">{{ $record->date->format('D, M j') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full {{ 
                                $record->status === 'present' ? 'bg-emerald-500/20 text-emerald-400' : 
                                'bg-red-500/20 text-red-400' }}">
                                {{ ucfirst($record->status) }}
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $record->rate }}%</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No attendance records</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Fee Summary -->
    <div class="mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Fee Summary</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All Invoices</a>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Total Due</p>
                        <p class="text-lg font-semibold text-amber-400">${{ number_format($invoices->where('status', 'pending')->sum('total'), 0) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Paid This Month</p>
                        <p class="text-lg font-semibold text-emerald-400">${{ number_format($invoices->where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('total'), 0) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Overdue</p>
                        <p class="text-lg font-semibold text-red-400">${{ number_format($invoices->where('status', 'pending')->where('due_date', '<', now())->sum('total'), 0) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Total Paid</p>
                        <p class="text-lg font-semibold text-slate-50">${{ number_format($invoices->where('status', 'paid')->sum('total'), 0) }}</p>
                    </div>
                </div>
                
                <div class="space-y-2">
                    @forelse($invoices->take(5) as $invoice)
                        <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                            <div>
                                <p class="text-xs font-medium text-slate-50">{{ $invoice->invoice_number }} • {{ $invoice->student->first_name }} {{ $invoice->student->last_name }}</p>
                                <p class="text-[10px] text-slate-400">Due: {{ $invoice->due_date->format('M j, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold text-slate-50">${{ number_format($invoice->total, 2) }}</p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ 
                                    $invoice->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 
                                    ($invoice->status === 'pending' ? 'bg-amber-500/20 text-amber-400' : 
                                    'bg-red-500/20 text-red-400') }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No invoices found</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
