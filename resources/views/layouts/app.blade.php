@php($user = auth()->user())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'School ERP') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
            <script>
                tailwind.config = {
                    darkMode: 'class',
                    theme: {
                        extend: {
                            fontFamily: {
                                sans: ['system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                            },
                        },
                    },
                };
            </script>
        @endif
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
        <div class="min-h-screen flex bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
            <aside class="hidden md:flex w-64 flex-col border-r border-slate-800 bg-slate-950/80 backdrop-blur-xl">
                <div class="h-16 flex items-center px-6 border-b border-slate-800">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500 text-xs font-bold tracking-tight">SE</span>
                        <span class="text-sm font-semibold tracking-tight">School ERP</span>
                    </span>
                </div>
                <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('dashboard') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        <span>Dashboard</span>
                    </a>
                    @if($user && $user->hasRole(['super-admin','school-admin']))
                        <!-- Academic Management -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('academic')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                                    <span class="text-[10px] font-medium">ACADEMIC</span>
                                </div>
                                <svg id="academic-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="academic-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.enrollments.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                    <span>Enrollments</span>
                                </a>
                                <a href="{{ route('admin.classes.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.classes.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-violet-400"></span>
                                    <span>Classes</span>
                                </a>
                                <a href="{{ route('admin.subjects.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.subjects.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-teal-400"></span>
                                    <span>Subjects</span>
                                </a>
                                <a href="{{ route('admin.curricula.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.curricula.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                                    <span>Curricula</span>
                                </a>
                                <a href="{{ route('admin.teachers.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.teachers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                    <span>Teachers</span>
                                </a>
                                <a href="{{ route('admin.timetables.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.timetables.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-pink-400"></span>
                                    <span>Timetable</span>
                                </a>
                                <a href="{{ route('admin.attendance.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.attendance.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-lime-400"></span>
                                    <span>Attendance</span>
                                </a>
                            </div>
                        </div>

                        <!-- Finance & Accounting -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('finance')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-green-400"></span>
                                    <span class="text-[10px] font-medium">FINANCE & ACCOUNTING</span>
                                </div>
                                <svg id="finance-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="finance-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.fees.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.fees.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                                    <span>Fee Structure</span>
                                </a>
                                <a href="{{ route('admin.invoices.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ (request()->routeIs('admin.invoices.*') || request()->routeIs('admin.payments.*')) ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-sky-400"></span>
                                    <span>Invoices & Payments</span>
                                </a>
                                <a href="{{ route('admin.receipts.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.receipts.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                    <span>Receipts</span>
                                </a>
                                <a href="{{ route('admin.cash-transfers.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.cash-transfers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                    <span>Cash Transfers</span>
                                </a>
                                <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.customers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-teal-400"></span>
                                    <span>Customers</span>
                                </a>
                                <a href="{{ route('admin.vendors.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.vendors.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-sky-400"></span>
                                    <span>Vendors</span>
                                </a>
                                <a href="{{ route('admin.budgets.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.budgets.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-pink-400"></span>
                                    <span>Budgets</span>
                                </a>
                            </div>
                        </div>

                        <!-- Accounting -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('accounting')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                                    <span class="text-[10px] font-medium">ACCOUNTING</span>
                                </div>
                                <svg id="accounting-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="accounting-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.accounts.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.accounts.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-lime-400"></span>
                                    <span>Chart of Accounts</span>
                                </a>
                                <a href="{{ route('admin.journals.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.journals.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                                    <span>Journal Entries</span>
                                </a>
                                <a href="{{ route('admin.bank-reconciliations.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.bank-reconciliations.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-orange-400"></span>
                                    <span>Bank Reconciliation</span>
                                </a>
                                <a href="{{ route('admin.interbank-transfers.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.interbank-transfers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                                    <span>Interbank Transfers</span>
                                </a>
                            </div>
                        </div>

                        <!-- Communication -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('communication')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                    <span class="text-[10px] font-medium">COMMUNICATION</span>
                                </div>
                                <svg id="communication-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="communication-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.communication.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.communication.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                                    <span>Chat</span>
                                </a>
                                <a href="{{ route('admin.communication.sms') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.communication.sms') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-green-400"></span>
                                    <span>SMS</span>
                                </a>
                                <a href="{{ route('admin.communication.email') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.communication.email') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                                    <span>Email</span>
                                </a>
                            </div>
                        </div>

                        <!-- School Development Association (SDA) -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('sda')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                                    <span class="text-[10px] font-medium">SCHOOL DEVELOPMENT</span>
                                </div>
                                <svg id="sda-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="sda-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.sda.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.dashboard') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                                    <span>Dashboard</span>
                                </a>
                                <a href="{{ route('admin.sda.committees.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.committees.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-green-400"></span>
                                    <span>Committees</span>
                                </a>
                                <a href="{{ route('admin.sda.roles.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.roles.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                                    <span>Roles</span>
                                </a>
                                <a href="{{ route('admin.sda.meetings.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.meetings.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                    <span>Meetings</span>
                                </a>
                                <a href="{{ route('admin.sda.minutes.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.minutes.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-yellow-400"></span>
                                    <span>Minutes</span>
                                </a>
                                <a href="{{ route('admin.sda.resolutions.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.resolutions.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-orange-400"></span>
                                    <span>Resolutions</span>
                                </a>
                                <a href="{{ route('admin.sda.reports.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.reports.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-red-400"></span>
                                    <span>Reports</span>
                                </a>
                                <a href="{{ route('admin.sda.tasks.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.tasks.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-teal-400"></span>
                                    <span>Tasks</span>
                                </a>
                            </div>
                        </div>

                        <!-- Reports -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('reports')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-orange-400"></span>
                                    <span class="text-[10px] font-medium">REPORTS</span>
                                </div>
                                <svg id="reports-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="reports-menu" class="hidden space-y-1 pl-6">
                                <!-- Core Financial Reports -->
                                <div class="text-xs text-slate-500 font-medium px-3 py-1">CORE FINANCIAL REPORTS</div>
                                <a href="{{ route('admin.reports.trial-balance') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.trial-balance') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                                    <span>Trial Balance</span>
                                </a>
                                <a href="{{ route('admin.reports.income-statement') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.income-statement') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                    <span>Income Statement (P&L)</span>
                                </a>
                                <a href="{{ route('admin.reports.balance-sheet') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.balance-sheet') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                                    <span>Balance Sheet</span>
                                </a>
                                <a href="{{ route('admin.reports.cash-flow') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.cash-flow') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                                    <span>Cash Flow Statement</span>
                                </a>
                                <a href="{{ route('admin.reports.general-ledger') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.general-ledger') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                    <span>General Ledger</span>
                                </a>

                                <!-- Management Reports -->
                                <div class="text-xs text-slate-500 font-medium px-3 py-1 mt-2">MANAGEMENT REPORTS</div>
                                <a href="{{ route('admin.reports.aged-receivables') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.aged-receivables') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-red-400"></span>
                                    <span>Aged Receivables</span>
                                </a>
                                <a href="{{ route('admin.reports.budget-vs-actual') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.budget-vs-actual') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-orange-400"></span>
                                    <span>Budget vs Actual</span>
                                </a>
                                <a href="{{ route('admin.reports.student-fee-collection') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.student-fee-collection') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-green-400"></span>
                                    <span>Student Fee Collection</span>
                                </a>
                                <a href="{{ route('admin.reports.expense-analysis') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.expense-analysis') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-yellow-400"></span>
                                    <span>Expense Analysis</span>
                                </a>
                                <a href="{{ route('admin.reports.departmental-performance') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.departmental-performance') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-pink-400"></span>
                                    <span>Departmental Performance</span>
                                </a>

                                <!-- Other Reports -->
                                <div class="text-xs text-slate-500 font-medium px-3 py-1 mt-2">OTHER REPORTS</div>
                                <a href="{{ route('admin.reports.debtor-creditor') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.debtor-creditor') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-teal-400"></span>
                                    <span>Debtor/Creditor Report</span>
                                </a>
                            </div>
                        </div>

                        <!-- System Administration -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('system')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                                    <span class="text-[10px] font-medium">SYSTEM</span>
                                </div>
                                <svg id="system-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="system-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.schools.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.schools.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                                    <span>Schools</span>
                                </a>
                            </div>
                        </div>
                        
                        @if($user && $user->hasRole('super-admin'))
                            <!-- Portal Testing -->
                            <div class="space-y-1">
                                <button onclick="toggleMenu('portals')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                        <span class="text-[10px] font-medium">PORTAL TESTING</span>
                                    </div>
                                    <svg id="portals-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="portals-menu" class="hidden space-y-1 pl-6">
                                    <a href="/admin/dashboard/headmaster" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.headmaster') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                                        <span>Headmaster Dashboard</span>
                                    </a>
                                    <a href="/admin/dashboard/deputy-headmaster" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.deputy-headmaster') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-green-400"></span>
                                        <span>Deputy Headmaster Dashboard</span>
                                    </a>
                                    <a href="/admin/dashboard/accounts-clerk" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.accounts-clerk') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-yellow-400"></span>
                                        <span>Accounts Clerk Dashboard</span>
                                    </a>
                                    <a href="/admin/dashboard/bursar" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.bursar') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-orange-400"></span>
                                        <span>Bursar Dashboard</span>
                                    </a>
                                    <a href="/admin/dashboard/procurement-officer" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.procurement-officer') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                        <span>Procurement Officer Dashboard</span>
                                    </a>
                                    <a href="{{ route('teacher.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('teacher.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                        <span>Teacher Portal</span>
                                    </a>
                                    <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('student.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                                        <span>Student Portal</span>
                                    </a>
                                    <a href="{{ route('parent.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('parent.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-orange-400"></span>
                                        <span>Parent Portal</span>
                                    </a>
                                    <a href="{{ route('librarian.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('librarian.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-pink-400"></span>
                                        <span>Librarian Portal</span>
                                    </a>
                                    
                                    <!-- SDA Portal Testing -->
                                    <div class="text-xs text-slate-500 font-medium px-3 py-1 mt-2">SDA PORTALS</div>
                                    <a href="{{ route('sda.chairman.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.chairman.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                        <span>SDA Chairman Dashboard</span>
                                    </a>
                                    <a href="{{ route('sda.secretary.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.secretary.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-blue-400"></span>
                                        <span>SDA Secretary Dashboard</span>
                                    </a>
                                    <a href="{{ route('sda.finance.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.finance.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-green-400"></span>
                                        <span>SDA Finance Dashboard</span>
                                    </a>
                                    <a href="{{ route('sda.procurement.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.procurement.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-yellow-400"></span>
                                        <span>SDA Procurement Dashboard</span>
                                    </a>
                                    <a href="{{ route('sda.member.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.member.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                                        <span>SDA Member Dashboard</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endif
                </nav>
                @if($user)
                    <div class="border-t border-slate-800 px-4 py-4 text-xs text-slate-400 space-y-1">
                        <div class="font-medium text-slate-100">{{ $user->name }}</div>
                        <div>{{ $user->school?->name ?? 'No school linked' }}</div>
                    </div>
                @endif
            </aside>

            <div class="flex-1 flex flex-col">
                <header class="h-16 border-b border-slate-800 flex items-center justify-between px-4 lg:px-8 bg-slate-950/70 backdrop-blur-xl">
                    <div class="flex flex-col">
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Control Center</span>
                        <span class="text-sm font-semibold text-slate-50">{{ $user?->school?->name ?? 'Multi-School ERP' }}</span>
                    </div>
                    @if($user)
                        <div class="flex items-center gap-4">
                            <div class="hidden sm:flex flex-col text-right text-xs text-slate-400">
                                <span class="text-slate-100 text-sm font-medium">{{ $user->name }}</span>
                                <span>{{ $user->email }}</span>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-100 hover:bg-slate-800 transition">
                                    Logout
                                </button>
                            </form>
                        </div>
                    @endif
                </header>

                <main class="flex-1 overflow-y-auto">
                    <div class="max-w-6xl mx-auto py-8 px-4 lg:px-8 space-y-4">
                        @if (session('status'))
                            <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                                {{ session('status') }}
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <!-- Footer Copyright -->
        <footer class="bg-slate-900 border-t border-slate-800 py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-slate-400 text-sm">
                        © 2025 Betterlink Systems - All Rights Reserved
                    </p>
                </div>
            </div>
        </footer>

        @stack('scripts')

        <script>
            function toggleMenu(menuId) {
                const menu = document.getElementById(menuId + '-menu');
                const arrow = document.getElementById(menuId + '-arrow');
                
                if (menu.classList.contains('hidden')) {
                    // Close all other menus first
                    document.querySelectorAll('[id$="-menu"]').forEach(m => {
                        if (m.id !== menuId + '-menu') {
                            m.classList.add('hidden');
                        }
                    });
                    document.querySelectorAll('[id$="-arrow"]').forEach(a => {
                        if (a.id !== menuId + '-arrow') {
                            a.classList.remove('rotate-180');
                        }
                    });
                    
                    // Open this menu
                    menu.classList.remove('hidden');
                    arrow.classList.add('rotate-180');
                } else {
                    // Close this menu
                    menu.classList.add('hidden');
                    arrow.classList.remove('rotate-180');
                }
            }

            // Auto-open menu based on current route
            document.addEventListener('DOMContentLoaded', function() {
                const currentRoute = window.location.pathname;
                
                // Check which menu should be open based on current route
                if (currentRoute.includes('/enrollments') || currentRoute.includes('/classes') || 
                    currentRoute.includes('/subjects') || currentRoute.includes('/curricula') || 
                    currentRoute.includes('/teachers') || currentRoute.includes('/timetables') || 
                    currentRoute.includes('/attendance')) {
                    toggleMenu('academic');
                } else if (currentRoute.includes('/fees') || currentRoute.includes('/invoices') || 
                      currentRoute.includes('/receipts') || currentRoute.includes('/customers') || 
                      currentRoute.includes('/vendors') || currentRoute.includes('/budgets')) {
                    toggleMenu('finance');
                } else if (currentRoute.includes('/accounts') || currentRoute.includes('/journals') || 
                      currentRoute.includes('/bank-reconciliations') || currentRoute.includes('/interbank-transfers')) {
                    toggleMenu('accounting');
                } else if (currentRoute.includes('/communication')) {
                    toggleMenu('communication');
                } else if (currentRoute.includes('/dashboard/headmaster') || currentRoute.includes('/dashboard/deputy-headmaster') || 
                      currentRoute.includes('/dashboard/accounts-clerk') || currentRoute.includes('/dashboard/bursar') || 
                      currentRoute.includes('/dashboard/procurement-officer')) {
                    toggleMenu('portals');
                } else if (currentRoute.includes('/reports')) {
                    toggleMenu('reports');
                } else if (currentRoute.includes('/schools')) {
                    toggleMenu('system');
                }
            });
        </script>
    </body>
</html>
