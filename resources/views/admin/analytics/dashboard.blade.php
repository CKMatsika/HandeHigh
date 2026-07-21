@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Analytics Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Comprehensive insights into your school's performance and operations.</p>
        </div>
    </div>

    <!-- Academic Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-blue-500/20 via-slate-900 to-slate-950 px-4 py-4">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Total Students</div>
            <div class="text-2xl font-bold text-slate-50">{{ $academicStats['totalStudents'] }}</div>
            <p class="mt-1 text-xs text-slate-400">Enrolled students</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-green-500/20 via-slate-900 to-slate-950 px-4 py-4">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Total Teachers</div>
            <div class="text-2xl font-bold text-slate-50">{{ $academicStats['totalTeachers'] }}</div>
            <p class="mt-1 text-xs text-slate-400">Teaching staff</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-purple-500/20 via-slate-900 to-slate-950 px-4 py-4">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Classes</div>
            <div class="text-2xl font-bold text-slate-50">{{ $academicStats['totalClasses'] }}</div>
            <p class="mt-1 text-xs text-slate-400">Active classes</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-amber-500/20 via-slate-900 to-slate-950 px-4 py-4">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Average GPA</div>
            <div class="text-2xl font-bold text-slate-50">{{ number_format($academicStats['averageGPA'], 2) }}</div>
            <p class="mt-1 text-xs text-slate-400">Overall performance</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.timetables.index') }}" class="rounded-xl border border-slate-800 bg-gradient-to-br from-indigo-500/20 via-slate-900 to-slate-950 px-4 py-4 hover:border-indigo-500/50 transition-colors cursor-pointer">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Timetable</div>
                    <div class="text-lg font-bold text-indigo-400">Manage</div>
                    <p class="mt-1 text-xs text-slate-400">View and edit schedules</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-indigo-500/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Financial Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-emerald-500/20 via-slate-900 to-slate-950 px-4 py-4">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Total Revenue</div>
            <div class="text-2xl font-bold text-emerald-500">${{ number_format($financialStats['totalRevenue'], 2) }}</div>
            <p class="mt-1 text-xs text-slate-400">This year</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-red-500/20 via-slate-900 to-slate-950 px-4 py-4">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Outstanding Debt</div>
            <div class="text-2xl font-bold text-red-500">${{ number_format($financialStats['outstandingInvoices'], 2) }}</div>
            <p class="mt-1 text-xs text-slate-400">Unpaid invoices</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-cyan-500/20 via-slate-900 to-slate-950 px-4 py-4">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Total Expenses</div>
            <div class="text-2xl font-bold text-cyan-500">${{ number_format($financialStats['totalExpenses'], 2) }}</div>
            <p class="mt-1 text-xs text-slate-400">This year</p>
        </div>
    </div>

    <!-- Library & Attendance -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h3 class="text-sm font-semibold text-slate-50 mb-3">Library Statistics</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-lg font-semibold text-slate-50">{{ $libraryStats['totalBooks'] }}</div>
                    <p class="text-xs text-slate-400">Total books</p>
                </div>
                <div>
                    <div class="text-lg font-semibold text-amber-500">{{ $libraryStats['borrowedBooks'] }}</div>
                    <p class="text-xs text-slate-400">Borrowed</p>
                </div>
                <div>
                    <div class="text-lg font-semibold text-emerald-500">{{ $libraryStats['availableBooks'] }}</div>
                    <p class="text-xs text-slate-400">Available</p>
                </div>
                <div>
                    <div class="text-lg font-semibold text-red-500">{{ $libraryStats['overdueBooks'] }}</div>
                    <p class="text-xs text-slate-400">Overdue</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h3 class="text-sm font-semibold text-slate-50 mb-3">Teacher Workload</h3>
            <div class="space-y-2">
                @foreach($teacherWorkload as $teacher)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800">
                        <div>
                            <div class="text-sm text-slate-200">{{ $teacher->first_name }} {{ $teacher->last_name }}</div>
                            <div class="text-xs text-slate-400">{{ $teacher->specialization ?? 'General' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-slate-50">{{ $teacher->classes_count ?? 0 }} classes</div>
                            <div class="text-xs text-slate-400">{{ $teacher->students_count ?? 0 }} students</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h3 class="text-sm font-semibold text-slate-50 mb-3">Recent Enrollments</h3>
            <div class="space-y-2">
                @foreach($recentActivity['recentEnrollments'] as $enrollment)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800">
                        <div>
                            <div class="text-sm text-slate-200">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</div>
                            <div class="text-xs text-slate-400">{{ $enrollment->class->name ?? 'N/A' }}</div>
                        </div>
                        <div class="text-xs text-slate-400">{{ $enrollment->created_at->format('M d, Y') }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <h3 class="text-sm font-semibold text-slate-50 mb-3">Recent Payments</h3>
            <div class="space-y-2">
                @foreach($recentActivity['recentPayments'] as $payment)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800">
                        <div>
                            <div class="text-sm text-slate-200">{{ $payment->student->first_name }} {{ $payment->student->last_name }}</div>
                            <div class="text-xs text-slate-400">{{ $payment->payment_method }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-emerald-500">${{ number_format($payment->amount, 2) }}</div>
                            <div class="text-xs text-slate-400">{{ $payment->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
