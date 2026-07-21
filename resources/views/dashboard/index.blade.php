@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">Welcome back, {{ $user->name }}.</h1>
                <p class="text-xs text-slate-400 mt-1">Overview of your multi-school academic and finance hub.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-slate-800 bg-gradient-to-br from-indigo-500/20 via-slate-900 to-slate-950 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Role</div>
                <div class="text-sm font-semibold text-slate-50">{{ $roles->join(', ') ?: 'User' }}</div>
                <p class="mt-1 text-xs text-slate-400">Controls what you can manage across the ERP.</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">School</div>
                @if($school)
                    <div class="text-sm font-semibold text-slate-50">{{ $school->name }}</div>
                    <p class="mt-1 text-xs text-slate-400">Code: {{ $school->code }} · {{ $school->email }}</p>
                @else
                    <p class="text-xs text-slate-400">No school profile linked yet.</p>
                @endif
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4 flex flex-col justify-between">
                <div>
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Environment</div>
                    <p class="text-sm text-slate-200">SQLite · Laravel 12</p>
                    <p class="mt-1 text-xs text-slate-400">Ready for multi-tenant academic & finance automation.</p>
                </div>
            </div>
        </div>

        <!-- Student Statistics -->
        @if(isset($total_students))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('admin.enrollments.index') }}" class="group rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4 block transition-all duration-200 hover:border-blue-500 hover:bg-slate-800 hover:shadow-lg hover:shadow-blue-500/10">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Students Overview</div>
                    <div class="text-2xl font-bold text-slate-50 group-hover:text-blue-400 transition-colors">{{ $total_students }}</div>
                    <p class="mt-1 text-xs text-slate-400">Total enrolled students</p>
                    <div class="mt-3 flex items-center gap-2">
                        <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                        <span class="text-xs text-slate-400">{{ $active_students }} active</span>
                    </div>
                    <div class="mt-2 text-xs text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">
                        → Click to view enrollments
                    </div>
                </a>
                
                <a href="{{ route('librarian.dashboard') }}" class="group rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4 block transition-all duration-200 hover:border-purple-500 hover:bg-slate-800 hover:shadow-lg hover:shadow-purple-500/10">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Library Status</div>
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-50 group-hover:text-purple-400 transition-colors">{{ $total_books ?? 0 }}</div>
                            <p class="text-xs text-slate-400">Total books</p>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-amber-500 group-hover:text-purple-400 transition-colors">{{ $borrowed_books ?? 0 }}</div>
                            <p class="text-xs text-slate-400">Borrowed</p>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-purple-400 opacity-0 group-hover:opacity-100 transition-opacity">
                        → Click to view library
                    </div>
                </a>
            </div>
        @endif

        <!-- Financial Overview -->
        @if(isset($total_invoices))
            <div class="space-y-6">
                <!-- Revenue & Invoice Metrics -->
                <div>
                    <h3 class="text-sm font-semibold text-slate-50 mb-4">Revenue & Invoice Metrics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('admin.invoices.index') }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-blue-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-blue-500 hover:bg-gradient-to-br hover:from-blue-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-blue-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Total Invoices</div>
                            <div class="text-2xl font-bold text-blue-500 group-hover:text-blue-400 transition-colors">${{ number_format($total_invoices, 2) }}</div>
                            <p class="mt-1 text-xs text-slate-400">Total invoice value</p>
                            <div class="mt-2 text-xs text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view invoices
                            </div>
                        </a>

                        <a href="{{ route('admin.cashbook.index') }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-emerald-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-emerald-500 hover:bg-gradient-to-br hover:from-emerald-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-emerald-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Total Receipts</div>
                            <div class="text-2xl font-bold text-emerald-500 group-hover:text-emerald-400 transition-colors">${{ number_format($total_receipts, 2) }}</div>
                            <p class="mt-1 text-xs text-slate-400">Payments received</p>
                            <div class="mt-2 text-xs text-emerald-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view cashbook
                            </div>
                        </a>

                        <a href="{{ route('admin.invoices.index', ['status' => 'unpaid']) }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-amber-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-amber-500 hover:bg-gradient-to-br hover:from-amber-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-amber-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Outstanding Balance</div>
                            <div class="text-2xl font-bold text-amber-500 group-hover:text-amber-400 transition-colors">${{ number_format($outstanding_balance, 2) }}</div>
                            <p class="mt-1 text-xs text-slate-400">Total invoices - receipts</p>
                            <div class="mt-2 text-xs text-amber-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view outstanding
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Expenditure Metrics -->
                <div>
                    <h3 class="text-sm font-semibold text-slate-50 mb-4">Expenditure Metrics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('admin.bills.index') }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-red-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-red-500 hover:bg-gradient-to-br hover:from-red-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-red-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Purchase Orders Waiting</div>
                            <div class="text-2xl font-bold text-red-500 group-hover:text-red-400 transition-colors">{{ $purchase_orders_waiting }}</div>
                            <p class="mt-1 text-xs text-slate-400">Pending approval</p>
                            <div class="mt-2 text-xs text-red-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view purchase orders
                            </div>
                        </a>

                        <a href="{{ route('admin.bills.index') }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-orange-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-orange-500 hover:bg-gradient-to-br hover:from-orange-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-orange-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Expenditure Total</div>
                            <div class="text-2xl font-bold text-orange-500 group-hover:text-orange-400 transition-colors">${{ number_format($expenditure_total, 2) }}</div>
                            <p class="mt-1 text-xs text-slate-400">Total bills/expenses</p>
                            <div class="mt-2 text-xs text-orange-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view expenditures
                            </div>
                        </a>

                        <a href="{{ route('admin.bills.index', ['status' => 'pending']) }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-purple-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-purple-500 hover:bg-gradient-to-br hover:from-purple-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-purple-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Outstanding to be Paid</div>
                            <div class="text-2xl font-bold text-purple-500 group-hover:text-purple-400 transition-colors">${{ number_format($outstanding_to_be_paid, 2) }}</div>
                            <p class="mt-1 text-xs text-slate-400">Unpaid bills</p>
                            <div class="mt-2 text-xs text-purple-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view outstanding bills
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Project Metrics -->
                <div>
                    <h3 class="text-sm font-semibold text-slate-50 mb-4">Project Metrics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('admin.projects.index') }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-teal-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-teal-500 hover:bg-gradient-to-br hover:from-teal-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-teal-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Projects Approved</div>
                            <div class="text-2xl font-bold text-teal-500 group-hover:text-teal-400 transition-colors">{{ $projects_approved }}</div>
                            <p class="mt-1 text-xs text-slate-400">Approved projects</p>
                            <div class="mt-2 text-xs text-teal-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view projects
                            </div>
                        </a>

                        <a href="{{ route('admin.projects.index', ['status' => 'active']) }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-indigo-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-indigo-500 hover:bg-gradient-to-br hover:from-indigo-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-indigo-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Projects In Progress</div>
                            <div class="text-2xl font-bold text-indigo-500 group-hover:text-indigo-400 transition-colors">{{ $projects_in_progress->count() }}</div>
                            <p class="mt-1 text-xs text-slate-400">
                                @if($projects_in_progress->count() > 0)
                                    Avg: {{ round($projects_in_progress->avg('progress_percentage'), 1) }}% achieved
                                @else
                                    No active projects
                                @endif
                            </p>
                            <div class="mt-2 text-xs text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view progress
                            </div>
                        </a>

                        <a href="{{ route('admin.projects.index', ['status' => 'completed']) }}" class="group rounded-xl border border-slate-800 bg-gradient-to-br from-green-500/20 via-slate-900 to-slate-950 px-4 py-4 block transition-all duration-200 hover:border-green-500 hover:bg-gradient-to-br hover:from-green-500/30 hover:via-slate-800 hover:to-slate-950 hover:shadow-lg hover:shadow-green-500/10">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400 mb-2">Resolutions Approved</div>
                            <div class="text-2xl font-bold text-green-500 group-hover:text-green-400 transition-colors">{{ $resolutions_approved }}</div>
                            <p class="mt-1 text-xs text-slate-400">Completed projects</p>
                            <div class="mt-2 text-xs text-green-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                → Click to view completed
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        @endif

            <!-- Recent Transactions -->
            @if($recent_transactions->count() > 0)
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
                    <h3 class="text-sm font-semibold text-slate-50 mb-3">Recent Transactions</h3>
                    <div class="space-y-2">
                        @foreach($recent_transactions as $transaction)
                            <div class="flex items-center justify-between py-2 border-b border-slate-800">
                                <div class="flex-1">
                                    <div class="text-sm text-slate-200">{{ $transaction->description }}</div>
                                    <div class="text-xs text-slate-400">{{ $transaction->transaction_date->format('M d, Y') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold {{ $transaction->transaction_type == 'income' ? 'text-emerald-500' : 'text-red-500' }}">
                                        {{ $transaction->transaction_type == 'income' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                                    </div>
                                    <div class="text-xs text-slate-400">{{ $transaction->bankAccount?->account_name ?? 'Cash' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        <!-- Smart Rules Status -->
        @if(isset($active_rules))
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
                <h3 class="text-sm font-semibold text-slate-50 mb-3">Active Smart Rules</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">Exam Access Restriction</span>
                        <span class="text-xs font-medium {{ $active_rules['exam_restriction'] ? 'text-amber-500' : 'text-emerald-500' }}">
                            {{ $active_rules['exam_restriction'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">Result Access Restriction</span>
                        <span class="text-xs font-medium {{ $active_rules['result_restriction'] ? 'text-amber-500' : 'text-emerald-500' }}">
                            {{ $active_rules['result_restriction'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">Registration Restriction</span>
                        <span class="text-xs font-medium {{ $active_rules['registration_restriction'] ? 'text-amber-500' : 'text-emerald-500' }}">
                            {{ $active_rules['registration_restriction'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">Late Payment Penalties</span>
                        <span class="text-xs font-medium {{ $active_rules['late_payment_penalties'] ? 'text-emerald-500' : 'text-slate-500' }}">
                            {{ $active_rules['late_payment_penalties'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Data Privacy Overview (Super Admin Only) -->
        @if(isset($privacy_report))
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
                <h3 class="text-sm font-semibold text-slate-50 mb-3">Data Privacy & Compliance</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs text-slate-400 mb-1">GDPR Status</div>
                        <div class="text-sm font-medium text-emerald-500">{{ $privacy_report['compliance_status']['gdpr_compliance']['status'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Data Records</div>
                        <div class="text-sm font-medium text-slate-50">
                            {{ $privacy_report['data_inventory']['students']['count'] }} Students
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 mb-1">Security Level</div>
                        <div class="text-sm font-medium text-emerald-500">{{ $privacy_report['compliance_status']['risk_assessment']['overall_risk'] }}</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Administration shortcuts -->
        @if($user->hasRole(['super-admin','school-admin']))
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-4">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-slate-50">Administration shortcuts</h2>
                    <p class="mt-1 text-xs text-slate-400">Quickly jump into key setup screens for your schools.</p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs mb-4">
                    <a href="{{ route('admin.schools.index') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 font-medium text-white hover:bg-indigo-600 transition">
                        Manage Schools
                    </a>
                    <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 font-medium text-white hover:bg-emerald-600 transition">
                        Enrollments
                    </a>
                    <a href="{{ route('admin.fees.index') }}" class="inline-flex items-center rounded-full bg-amber-500 px-4 py-1.5 font-medium text-white hover:bg-amber-600 transition">
                        Fees
                    </a>
                    <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center rounded-full bg-sky-500 px-4 py-1.5 font-medium text-white hover:bg-sky-600 transition">
                        Invoices & Payments
                    </a>
                </div>

                <!-- New Feature Shortcuts -->
                <div class="border-t border-slate-800 pt-4">
                    <h3 class="text-sm font-semibold text-slate-50 mb-3">New Features</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                        <a href="{{ route('admin.analytics.dashboard') }}" class="inline-flex items-center rounded-full bg-purple-500 px-4 py-1.5 font-medium text-white hover:bg-purple-600 transition">
                            Analytics
                        </a>
                        <a href="{{ route('admin.bank-accounts.index') }}" class="inline-flex items-center rounded-full bg-cyan-500 px-4 py-1.5 font-medium text-white hover:bg-cyan-600 transition">
                            Bank Accounts
                        </a>
                        <a href="{{ route('admin.cashbook.index') }}" class="inline-flex items-center rounded-full bg-teal-500 px-4 py-1.5 font-medium text-white hover:bg-teal-600 transition">
                            Cashbook
                        </a>
                        <a href="{{ route('admin.smart-rules.index') }}" class="inline-flex items-center rounded-full bg-rose-500 px-4 py-1.5 font-medium text-white hover:bg-rose-600 transition">
                            Smart Rules
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-2 gap-2 text-xs mt-3">
                        <a href="{{ route('admin.school-setup.index') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 font-medium text-white hover:bg-indigo-600 transition">
                            School Setup
                        </a>
                        <a href="{{ route('admin.user-management.index') }}" class="inline-flex items-center rounded-full bg-orange-500 px-4 py-1.5 font-medium text-white hover:bg-orange-600 transition">
                            User Management
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-2 gap-2 text-xs mt-3">
                        <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center rounded-full bg-pink-500 px-4 py-1.5 font-medium text-white hover:bg-pink-600 transition">
                            HR Management
                        </a>
                        <a href="#" class="inline-flex items-center rounded-full bg-green-500 px-4 py-1.5 font-medium text-white hover:bg-green-600 transition">
                            Timetable
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
