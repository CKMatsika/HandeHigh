@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Student Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Welcome back, {{ $student ? $student->first_name : $user->name }}!</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('student.profile') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Profile</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Grade</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $student ? $student->grade : 'N/A' }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Class</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $enrollment?->class?->name ?? 'Not assigned' }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-violet-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-violet-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Student Type</p>
                    <p class="text-sm font-semibold text-slate-50">{{ $enrollment?->student_type_label ?? 'Day Student' }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-{{ $enrollment?->student_type_badge_color ?? 'info' }}/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-{{ $enrollment?->student_type_badge_color ?? 'info' }}"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Leadership Roles</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $user->currentPositions()->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-amber-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Activities</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $user->currentClubs()->count() + $user->currentSports()->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-purple-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Assignments -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Recent Assignments</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($assignments as $assignment)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-50">{{ $assignment->title }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $assignment->subject }}</p>
                                    <p class="text-[10px] text-slate-500 mt-2">Due: {{ $assignment->due_date->format('M j, Y') }}</p>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $assignment->status === 'pending' ? 'bg-amber-500/20 text-amber-400' : 'bg-emerald-500/20 text-emerald-400' }}">
                                    {{ ucfirst($assignment->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No assignments</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Announcements</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($announcements as $announcement)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between mb-2">
                                <p class="text-xs font-medium text-slate-50">{{ $announcement->title }}</p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $announcement->priority === 'high' ? 'bg-red-500/20 text-red-400' : ($announcement->priority === 'medium' ? 'bg-amber-500/20 text-amber-400' : 'bg-slate-700 text-slate-400') }}">
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

        <!-- Extracurricular Activities -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Activities & Leadership</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
                </div>
                <div class="p-4 space-y-3">
                    <!-- Leadership Positions -->
                    @forelse($user->currentPositions()->take(2) as $position)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-50">{{ $position->position_title }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $position->type_label }}</p>
                                    @if($position->description)
                                        <p class="text-[10px] text-slate-500 mt-2">{{ Str::limit($position->description, 50) }}</p>
                                    @endif
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-{{ $position->badge_color }}/20 text-{{ $position->badge_color }}-400">
                                    {{ $position->type_label }}
                                </span>
                            </div>
                        </div>
                    @empty
                    @endforelse

                    <!-- Clubs -->
                    @forelse($user->currentClubs()->take(2) as $club)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-50">{{ $club->club_name }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $club->type_label }} @if($club->role) - {{ $club->role }} @endif</p>
                                    @if($club->description)
                                        <p class="text-[10px] text-slate-500 mt-2">{{ Str::limit($club->description, 50) }}</p>
                                    @endif
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-{{ $club->badge_color }}/20 text-{{ $club->badge_color }}-400">
                                    {{ $club->type_label }}
                                </span>
                            </div>
                        </div>
                    @empty
                    @endforelse

                    <!-- Sports -->
                    @forelse($user->currentSports()->take(2) as $sport)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-50">{{ $sport->sport_name }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $sport->category_label }} @if($sport->position) - {{ $sport->position }} @endif</p>
                                    @if($sport->achievements)
                                        <p class="text-[10px] text-slate-500 mt-2">{{ Str::limit($sport->achievements, 50) }}</p>
                                    @endif
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-{{ $sport->badge_color }}/20 text-{{ $sport->badge_color }}-400">
                                    {{ $sport->category_label }}
                                </span>
                            </div>
                        </div>
                    @empty
                    @endforelse

                    @if($user->currentPositions()->get()->isEmpty() && $user->currentClubs()->get()->isEmpty() && $user->currentSports()->get()->isEmpty())
                        <p class="text-xs text-slate-500 text-center py-4">No activities or leadership roles</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Career Guidance Widget -->
    <div class="mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Career Guidance</h2>
                <a href="{{ route('student.counsellor') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">Talk to Career Counsellor</a>
            </div>
            <div class="p-4">
                @if($hasCareerAssessment && $careerAssessment)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <p class="text-[10px] text-slate-400 mb-1">Top Strengths</p>
                            @forelse($careerTopStrengths as $strength)
                                <span class="inline-flex items-center text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 mr-1 mb-1">{{ $strength }}</span>
                            @empty
                                <p class="text-xs text-slate-500">No data</p>
                            @endforelse
                        </div>
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <p class="text-[10px] text-slate-400 mb-1">Top Career Matches</p>
                            @forelse($careerTopMatches as $match)
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-slate-300">{{ $match['name'] }}</span>
                                    <span class="text-[10px] text-indigo-400">{{ $match['match'] }}%</span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">No matches yet</p>
                            @endforelse
                        </div>
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50 flex flex-col items-center justify-center">
                            <p class="text-xs text-slate-400 mb-2">Want personalized advice?</p>
                            <a href="{{ route('student.counsellor') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                                Chat with AI Counsellor
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-500">
                        <span>Assessment: {{ $careerAssessment->created_at->format('M j, Y') }}</span>
                        <span>{{ count($careerAssessment->suggested_careers ?? []) }} careers matched</span>
                    </div>
                @else
                    <div class="text-center py-6">
                        <p class="text-xs text-slate-400 mb-3">No career assessment yet. Talk to our AI Career Counsellor to explore your options!</p>
                        <a href="{{ route('student.counsellor') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                            Start Career Exploration 🚀
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Timetable Section -->
    <div class="mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Weekly Timetable</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View Full Schedule</a>
            </div>
            <div class="p-4">
                @forelse($timetable as $day)
                    <div class="mb-6 last:mb-0">
                        <h3 class="text-xs font-medium text-slate-50 mb-3">{{ $day->day }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            @forelse($day->periods as $period)
                                <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                                    <p class="text-[10px] text-slate-400">{{ $period->time }}</p>
                                    <p class="text-xs font-medium text-slate-50 mt-1">{{ $period->subject }}</p>
                                    <p class="text-[10px] text-slate-500 mt-1">{{ $period->teacher }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 col-span-full">No periods scheduled</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No timetable available</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Results & Invoices -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Recent Results -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Recent Results</h2>
                <a href="{{ route('student.results') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($results as $result)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $result->subject->name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $result->created_at->format('M j, Y') }}</p>
                        </div>
                        <span class="text-xs font-semibold {{ $result->score >= 80 ? 'text-emerald-400' : ($result->score >= 60 ? 'text-amber-400' : 'text-red-400') }}">
                            {{ $result->score }}%
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No results available</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Recent Invoices</h2>
                <a href="{{ route('student.fees') }}" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($invoices as $invoice)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $invoice->invoice_number }}</p>
                            <p class="text-[10px] text-slate-400">{{ $invoice->issued_at->format('M j, Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold text-slate-50">${{ number_format($invoice->total, 2) }}</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full {{ $invoice->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : ($invoice->status === 'pending' ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No invoices available</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
