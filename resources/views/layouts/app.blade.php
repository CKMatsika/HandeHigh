@php($user = auth()->user())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
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
            <aside class="hidden md:flex w-64 shrink-0 h-screen sticky top-0 flex-col border-r border-slate-800 bg-slate-950/80 backdrop-blur-xl">
                <div class="h-16 flex items-center px-6 border-b border-slate-800">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500 text-xs font-bold tracking-tight">SE</span>
                        <span class="text-sm font-semibold tracking-tight">School ERP</span>
                    </span>
                </div>
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('dashboard') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                        <x-nav-icon name="dashboard" class="text-emerald-400" />
                        <span>Dashboard</span>
                    </a>
                    @if($user && ($user->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster', 'accounts-clerk', 'bursar', 'procurement-officer', 'stores-person', 'stores-manager', 'accountant']) || $user->can('inventory.view')))
                        @can('dashboard.headmaster')
                            <a href="{{ route('admin.dashboard.headmaster') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.headmaster') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                <x-nav-icon name="dashboard" class="text-blue-400" />
                                <span>Headmaster Dashboard</span>
                            </a>
                        @endcan
                        @can('dashboard.deputy-headmaster')
                            <a href="{{ route('admin.dashboard.deputy-headmaster') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.deputy-headmaster') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                <x-nav-icon name="dashboard" class="text-green-400" />
                                <span>Deputy Headmaster Dashboard</span>
                            </a>
                        @endcan
                        @can('dashboard.accounts-clerk')
                            <a href="{{ route('admin.dashboard.accounts-clerk') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.accounts-clerk') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                <x-nav-icon name="dashboard" class="text-yellow-400" />
                                <span>Accounts Clerk Dashboard</span>
                            </a>
                        @endcan
                        @can('dashboard.bursar')
                            <a href="{{ route('admin.dashboard.bursar') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.bursar') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                <x-nav-icon name="dashboard" class="text-orange-400" />
                                <span>Bursar Dashboard</span>
                            </a>
                        @endcan
                        @can('dashboard.procurement-officer')
                            <a href="{{ route('admin.dashboard.procurement-officer') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.procurement-officer') ? 'bg-indigo-500 text-slate-50' : 'text-slate-300' }}">
                                <x-nav-icon name="dashboard" class="text-purple-400" />
                                <span>Procurement Officer Dashboard</span>
                            </a>
                        @endcan
                        @can('dashboard.stores-manager')
                            <a href="{{ route('admin.dashboard.stores-manager') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.stores-manager') ? 'bg-emerald-600 text-slate-50 font-medium' : 'text-slate-300' }}">
                                <x-nav-icon name="dashboard" class="text-teal-400" />
                                <span>Stores Manager Dashboard</span>
                            </a>
                        @endcan
                    @endif

                    @if($user && ($user->can('inventory.view') || $user->hasAnyRole(['super-admin', 'school-admin', 'stores-person', 'stores-manager', 'bursar', 'headmaster', 'procurement-officer', 'accountant', 'teacher'])))
                        <!-- Inventory Management -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('inventory')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="box" class="text-emerald-400" />
                                    <span class="text-[10px] font-medium tracking-wider">INVENTORY MANAGEMENT</span>
                                </div>
                                <svg id="inventory-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="inventory-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.dashboard.stores-manager') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dashboard.stores-manager') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="dashboard" class="text-teal-400" />
                                    <span>Stores Dashboard</span>
                                </a>
                                <a href="{{ route('admin.inventory.items.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.items.*') || request()->routeIs('admin.inventory.index') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="package" class="text-emerald-400" />
                                    <span>Item Catalogue</span>
                                </a>
                                <a href="{{ route('admin.inventory.categories.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.categories.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="tag" class="text-lime-400" />
                                    <span>Categories</span>
                                </a>
                                <a href="{{ route('admin.inventory.warehouses.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.warehouses.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="warehouse" class="text-cyan-400" />
                                    <span>Warehouses & Storage</span>
                                </a>
                                <a href="{{ route('admin.inventory.stock-levels') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.stock-levels*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="layers" class="text-violet-400" />
                                    <span>Stock Levels & Reorder</span>
                                </a>
                                <a href="{{ route('admin.inventory.stock-requests.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.stock-requests.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard" class="text-amber-400" />
                                    <span>Stock Requests & Issues</span>
                                </a>
                                <a href="{{ route('admin.inventory.stock-takes.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.stock-takes.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard-check" class="text-sky-400" />
                                    <span>Stock Takes & Audits</span>
                                </a>
                                <a href="{{ route('admin.inventory.returns.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.returns.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="rotate" class="text-rose-400" />
                                    <span>Goods Returns</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($user && ($user->can('procurement.view') || $user->hasAnyRole(['super-admin', 'school-admin', 'procurement-officer', 'bursar', 'stores-person', 'headmaster', 'accountant'])))
                        <!-- Procurement & Tenders -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('procurement')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="cart" class="text-pink-400" />
                                    <span class="text-[10px] font-medium tracking-wider">PROCUREMENT HUB</span>
                                </div>
                                <svg id="procurement-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="procurement-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.procurement.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.procurement.index') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="cart" class="text-pink-400" />
                                    <span>Procurement Hub</span>
                                </a>
                                <a href="{{ route('admin.procurement.rfq.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.procurement.rfq.*') || request()->routeIs('admin.procurement.quotations.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="file-search" class="text-indigo-400" />
                                    <span>RFQs & Tender Bids</span>
                                </a>
                                <a href="{{ route('admin.procurement.requisitions.create') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.procurement.requisitions.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard" class="text-amber-400" />
                                    <span>Requisitions</span>
                                </a>
                                <a href="{{ route('admin.dashboard.stores-manager') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.procurement.goods-received-vouchers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="receipt" class="text-teal-400" />
                                    <span>Inward GRN Receipts</span>
                                </a>
                                <a href="{{ route('admin.inventory.returns.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.returns.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="rotate" class="text-rose-400" />
                                    <span>Supplier Returns (Debit Notes)</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($user && ($user->can('inventory.assets.view') || $user->hasAnyRole(['super-admin', 'school-admin', 'stores-person', 'stores-manager', 'bursar', 'accountant', 'headmaster'])))
                        <!-- Asset Management -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('assets')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="building" class="text-indigo-400" />
                                    <span class="text-[10px] font-medium tracking-wider">ASSET MANAGEMENT</span>
                                </div>
                                <svg id="assets-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="assets-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.inventory.fixed-assets.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.fixed-assets.index') || request()->routeIs('admin.inventory.fixed-assets.show') || request()->routeIs('admin.inventory.fixed-assets.create') || request()->routeIs('admin.inventory.fixed-assets.edit') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="building" class="text-indigo-400" />
                                    <span>Fixed Asset Register</span>
                                </a>
                                <a href="{{ route('admin.inventory.fixed-assets.depreciation') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.fixed-assets.depreciation*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-amber-400" />
                                    <span>Depreciation Schedules</span>
                                </a>
                                <a href="{{ route('admin.inventory.fixed-assets.maintenance') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.fixed-assets.maintenance*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="wrench" class="text-emerald-400" />
                                    <span>Maintenance & Repairs</span>
                                </a>
                                <a href="{{ route('admin.inventory.settings.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.inventory.settings.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="scale" class="text-purple-400" />
                                    <span>Capitalization Policies</span>
                                </a>
                            </div>
                        </div>
                    @endif
                    @if($user && $user->hasRole(['super-admin','school-admin']))
                        <!-- Academic Management -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('academic')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="book-open" class="text-blue-400" />
                                    <span class="text-[10px] font-medium">ACADEMIC</span>
                                </div>
                                <svg id="academic-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="academic-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.students.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.students.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="users" class="text-blue-400" />
                                    <span>Students</span>
                                </a>
                                <a href="{{ route('admin.enrollments.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.enrollments.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="user-plus" class="text-emerald-400" />
                                    <span>Enrollments</span>
                                </a>
                                <a href="{{ route('admin.classes.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.classes.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="school" class="text-violet-400" />
                                    <span>Classes</span>
                                </a>
                                <a href="{{ route('admin.subjects.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.subjects.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="book" class="text-teal-400" />
                                    <span>Subjects</span>
                                </a>
                                <a href="{{ route('admin.curricula.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.curricula.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="book-open" class="text-rose-400" />
                                    <span>Curricula</span>
                                </a>
                                <a href="{{ route('admin.timetables.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.timetables.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="calendar" class="text-pink-400" />
                                    <span>Timetable</span>
                                </a>
                                <a href="{{ route('admin.exams.performance-reports.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.exams.performance-reports.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-purple-400" />
                                    <span>End-of-Term Reports</span>
                                </a>
                                <a href="{{ route('admin.attendance.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.attendance.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard-check" class="text-lime-400" />
                                    <span>Attendance</span>
                                </a>
                                <a href="{{ route('admin.hostels.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.hostels.*') || request()->routeIs('admin.dormitories.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="bed" class="text-amber-400" />
                                    <span>Hostels & Dormitories</span>
                                </a>
                                <a href="{{ route('admin.year-end.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.year-end.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="arrows" class="text-cyan-400" />
                                    <span>Year-End Processes</span>
                                </a>
                            </div>
                        </div>

                        <!-- Year-End Processes -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('yearend')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="arrows" class="text-cyan-400" />
                                    <span class="text-[10px] font-medium">YEAR-END PROCESSES</span>
                                </div>
                                <svg id="yearend-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="yearend-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.year-end.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.year-end.index') || request()->routeIs('admin.year-end.draft*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="arrows" class="text-cyan-400" />
                                    <span>Year-End Transition</span>
                                </a>
                                <a href="{{ route('admin.year-end.clearance.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.year-end.clearance*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="graduation" class="text-emerald-400" />
                                    <span>Graduation Clearance</span>
                                </a>
                            </div>
                        </div>

                        <!-- Hostel & Boarding Management -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('hostels')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="bed" class="text-amber-400" />
                                    <span class="text-[10px] font-medium">HOSTELS & BOARDING</span>
                                </div>
                                <svg id="hostels-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="hostels-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.hostels.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.hostels.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="bed" class="text-amber-400" />
                                    <span>Hostels</span>
                                </a>
                                <a href="{{ route('admin.dormitories.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.dormitories.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="bed" class="text-emerald-400" />
                                    <span>Dormitories & Beds</span>
                                </a>
                            </div>
                        </div>

                        <!-- Career Guidance -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('career')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="compass" class="text-teal-400" />
                                    <span class="text-[10px] font-medium">CAREER GUIDANCE</span>
                                </div>
                                <svg id="career-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="career-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.career-guidance.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.career-guidance.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard-check" class="text-emerald-400" />
                                    <span>Assessments</span>
                                </a>
                                <a href="{{ route('admin.career-guidance.paths') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.career-guidance.paths*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="route" class="text-indigo-400" />
                                    <span>Career Paths</span>
                                </a>
                                <a href="{{ route('admin.career-guidance.interests') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.career-guidance.interests*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="star" class="text-amber-400" />
                                    <span>Student Interests</span>
                                </a>
                            </div>
                        </div>

                        <!-- HR Management -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('hr')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="briefcase" class="text-purple-400" />
                                    <span class="text-[10px] font-medium">HR MANAGEMENT</span>
                                </div>
                                <svg id="hr-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="hr-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.employees.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.employees.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="user" class="text-blue-400" />
                                    <span>Employees</span>
                                </a>
                                <a href="{{ route('admin.teachers.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.teachers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="user" class="text-purple-400" />
                                    <span>Teachers</span>
                                </a>
                                <a href="{{ route('admin.departments.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.departments.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="building" class="text-amber-400" />
                                    <span>Departments</span>
                                </a>
                                <a href="{{ route('admin.leaves.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.leaves.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="calendar" class="text-rose-400" />
                                    <span>Leave Management</span>
                                </a>
                                <a href="{{ route('admin.payrolls.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.payrolls.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="cash" class="text-emerald-400" />
                                    <span>Payroll</span>
                                </a>
                                <a href="{{ route('admin.loans.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.loans.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="wallet" class="text-cyan-400" />
                                    <span>Loans</span>
                                </a>
                            </div>
                        </div>

                        <!-- Finance & Accounting -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('finance')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="cash" class="text-green-400" />
                                    <span class="text-[10px] font-medium">FINANCE & ACCOUNTING</span>
                                </div>
                                <svg id="finance-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="finance-menu" class="hidden space-y-1 pl-6">
                                @if(auth()->user()->hasAnyRole(['super-admin','school-admin','headmaster','deputy-headmaster','bursar','accountant']))
                                    <a href="{{ route('admin.funding.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 text-slate-300"><x-nav-icon name="graduation" class="text-indigo-400" /><span>Scholarships, Sponsors & BEAM</span></a>
                                @endif
                                <a href="{{ route('admin.fees.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.fees.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="receipt" class="text-amber-400" />
                                    <span>Fee Structure</span>
                                </a>
                                <a href="{{ route('admin.invoices.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ (request()->routeIs('admin.invoices.*') || request()->routeIs('admin.payments.*')) ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="file" class="text-sky-400" />
                                    <span>Invoices & Payments</span>
                                </a>
                                <a href="{{ route('admin.credit-notes.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.credit-notes.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="receipt" class="text-red-400" />
                                    <span>Credit Notes</span>
                                </a>
                                <a href="{{ route('admin.receipts.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.receipts.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="receipt" class="text-emerald-400" />
                                    <span>Receipts</span>
                                </a>
                                <a href="{{ route('admin.accounts.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.accounts.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="book" class="text-lime-400" />
                                    <span>Chart of Accounts</span>
                                </a>
                                <a href="{{ route('admin.account-mappings.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.account-mappings.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="arrows" class="text-violet-400" />
                                    <span>Account Mappings</span>
                                </a>
                                <a href="{{ route('admin.accounting-periods.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.accounting-periods.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="calendar" class="text-yellow-400" />
                                    <span>Accounting Periods</span>
                                </a>
                                <a href="{{ route('admin.journals.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.journals.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="book-open" class="text-cyan-400" />
                                    <span>Journal Entries</span>
                                </a>
                                <a href="{{ route('admin.bank-accounts.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.bank-accounts.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="bank" class="text-blue-400" />
                                    <span>Bank Accounts</span>
                                </a>
                                <a href="{{ route('admin.cashbook.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.cashbook.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="wallet" class="text-emerald-400" />
                                    <span>Cash Book</span>
                                </a>
                                <a href="{{ route('admin.bank-reconciliations.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.bank-reconciliations.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="scale" class="text-orange-400" />
                                    <span>Bank Reconciliation</span>
                                </a>
                                <a href="{{ route('admin.interbank-transfers.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.interbank-transfers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="arrows" class="text-rose-400" />
                                    <span>Interbank Transfers</span>
                                </a>
                                <a href="{{ route('admin.cash-transfers.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.cash-transfers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="arrows" class="text-purple-400" />
                                    <span>Cash Transfers</span>
                                </a>
                                <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.customers.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="users" class="text-teal-400" />
                                    <span>Customers</span>
                                </a>
                                <a href="{{ route('admin.vendors.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.vendors.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="building" class="text-sky-400" />
                                    <span>Vendors</span>
                                </a>
                                <a href="{{ route('admin.budgets.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.budgets.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-pink-400" />
                                    <span>Budgets</span>
                                </a>
                                <a href="{{ route('admin.projects.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.projects.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="briefcase" class="text-indigo-400" />
                                    <span>School Projects & ERP</span>
                                </a>
                                <a href="{{ route('admin.ai-finance.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.ai-finance.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="sparkles" class="text-violet-400" />
                                    <span>AI Finance Advisor</span>
                                </a>
                            </div>
                        </div>

                        <!-- Communication -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('communication')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="message" class="text-purple-400" />
                                    <span class="text-[10px] font-medium">COMMUNICATION</span>
                                    <span id="sidebarUnreadBadge" class="hidden ml-1 inline-flex items-center justify-center rounded-full bg-red-500 min-w-[16px] h-4 px-1 text-[9px] font-bold text-white">0</span>
                                </div>
                                <svg id="communication-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="communication-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.communication.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.communication.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="message" class="text-indigo-400" />
                                    <span>Chat</span>
                                </a>
                                <a href="{{ route('admin.communication.sms') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.communication.sms') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="message" class="text-green-400" />
                                    <span>SMS</span>
                                </a>
                                <a href="{{ route('admin.communication.email') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.communication.email') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="mail" class="text-blue-400" />
                                    <span>Email</span>
                                </a>
                            </div>
                        </div>

                        <!-- School Development Association (SDA) -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('sda')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="school" class="text-indigo-400" />
                                    <span class="text-[10px] font-medium">SCHOOL DEVELOPMENT</span>
                                </div>
                                <svg id="sda-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="sda-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.sda.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.dashboard') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="dashboard" class="text-indigo-400" />
                                    <span>Dashboard</span>
                                </a>
                                <a href="{{ route('admin.sda.committees.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.committees.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="users" class="text-green-400" />
                                    <span>Committees</span>
                                </a>
                                <a href="{{ route('admin.sda.roles.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.roles.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="shield" class="text-blue-400" />
                                    <span>Roles</span>
                                </a>
                                <a href="{{ route('admin.sda.meetings.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.meetings.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="calendar" class="text-purple-400" />
                                    <span>Meetings</span>
                                </a>
                                <a href="{{ route('admin.sda.minutes.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.minutes.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="file" class="text-yellow-400" />
                                    <span>Minutes</span>
                                </a>
                                <a href="{{ route('admin.sda.resolutions.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.resolutions.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="check-circle" class="text-orange-400" />
                                    <span>Resolutions</span>
                                </a>
                                <a href="{{ route('admin.sda.reports.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.reports.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-red-400" />
                                    <span>Reports</span>
                                </a>
                                <a href="{{ route('admin.sda.tasks.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.sda.tasks.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard-check" class="text-teal-400" />
                                    <span>Tasks</span>
                                </a>
                            </div>
                        </div>

                        <!-- Reports -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('reports')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="chart" class="text-orange-400" />
                                    <span class="text-[10px] font-medium">REPORTS</span>
                                </div>
                                <svg id="reports-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="reports-menu" class="hidden space-y-1 pl-6">
                                <!-- Academic & Operations Intelligence -->
                                <div class="text-xs text-slate-500 font-medium px-3 py-1">ACADEMIC & OPERATIONS</div>
                                <a href="{{ route('admin.reports.academic-dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.academic-dashboard') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="dashboard" class="text-violet-400" />
                                    <span>Academic Dashboard</span>
                                </a>
                                <a href="{{ route('admin.reports.enrollment-summary') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.enrollment-summary') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="user-plus" class="text-indigo-400" />
                                    <span>Enrollment Summary</span>
                                </a>
                                <a href="{{ route('admin.reports.enrollment-by-class') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.enrollment-by-class') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="user-plus" class="text-sky-400" />
                                    <span>Enrollment by Class</span>
                                </a>
                                <a href="{{ route('admin.reports.student-register') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.student-register') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="users" class="text-blue-400" />
                                    <span>Student Master Register</span>
                                </a>
                                <a href="{{ route('admin.reports.academic-performance') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.academic-performance') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-emerald-400" />
                                    <span>Performance Summary</span>
                                </a>
                                <a href="{{ route('admin.reports.subject-performance') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.subject-performance') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-teal-400" />
                                    <span>Subject Performance</span>
                                </a>
                                <a href="{{ route('admin.reports.exam-results') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.exam-results') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard-check" class="text-amber-400" />
                                    <span>Exam Results Register</span>
                                </a>
                                <a href="{{ route('admin.reports.attendance-summary') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.attendance-summary') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard-check" class="text-green-400" />
                                    <span>Attendance Summary</span>
                                </a>
                                <a href="{{ route('admin.reports.attendance-register') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.attendance-register') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard-check" class="text-lime-400" />
                                    <span>Attendance Register</span>
                                </a>

                                <!-- Core Financial & Debtors Reports -->
                                <div class="text-xs text-slate-500 font-medium px-3 py-1 mt-2">FINANCE INTELLIGENCE</div>
                                <a href="{{ route('admin.reports.finance-dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.finance-dashboard') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-blue-400" />
                                    <span>Finance Overview</span>
                                </a>
                                <a href="{{ route('admin.reports.debtors-aging') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.debtors-aging') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clock" class="text-red-400" />
                                    <span>Debtors Aging Matrix</span>
                                </a>
                                <a href="{{ route('admin.reports.outstanding-fees') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.outstanding-fees') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="cash" class="text-amber-400" />
                                    <span>Outstanding Fees</span>
                                </a>
                                <a href="{{ route('admin.reports.fee-collections') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.fee-collections') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="receipt" class="text-emerald-400" />
                                    <span>Fee Collections Register</span>
                                </a>
                                <a href="{{ route('admin.reports.collection-summary') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.collection-summary') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-teal-400" />
                                    <span>Collection Summary & Trends</span>
                                </a>
                                <a href="{{ route('admin.reports.student-statement') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.student-statement') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="file" class="text-indigo-400" />
                                    <span>Student Account Statements</span>
                                </a>
                                <a href="{{ route('admin.reports.cashbook-summary') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.cashbook-summary') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="wallet" class="text-cyan-400" />
                                    <span>Cashbook & Bank Summary</span>
                                </a>
                                <a href="{{ route('admin.reports.daily-banking-handover') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.daily-banking-handover*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="bank" class="text-emerald-400" />
                                    <span>Daily Cash Banking Handover</span>
                                </a>

                                <!-- Accounting Statements -->
                                <div class="text-xs text-slate-500 font-medium px-3 py-1 mt-2">ACCOUNTING STATEMENTS</div>
                                <a href="{{ route('admin.reports.trial-balance') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.trial-balance') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="scale" class="text-amber-400" />
                                    <span>Trial Balance</span>
                                </a>
                                <a href="{{ route('admin.reports.income-statement') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.income-statement') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-emerald-400" />
                                    <span>Income Statement (P&L)</span>
                                </a>
                                <a href="{{ route('admin.reports.profit-and-loss') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.profit-and-loss') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-teal-400" />
                                    <span>Project &amp; Cost Center P&amp;L</span>
                                </a>
                                <a href="{{ route('admin.reports.balance-sheet') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.balance-sheet') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">

                                    <x-nav-icon name="scale" class="text-blue-400" />
                                    <span>Balance Sheet</span>
                                </a>
                                <a href="{{ route('admin.reports.budget-vs-actual') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.budget-vs-actual') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-orange-400" />
                                    <span>Budget vs Actual</span>
                                </a>
                                <a href="{{ route('admin.reports.debtor-creditor') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.reports.debtor-creditor') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="book" class="text-purple-400" />
                                    <span>Debtor / Creditor Ledger</span>
                                </a>
                            </div>
                        </div>

                        <!-- System Administration -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('system')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="settings" class="text-indigo-400" />
                                    <span class="text-[10px] font-medium">SYSTEM</span>
                                </div>
                                <svg id="system-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="system-menu" class="hidden space-y-1 pl-6">
                                <a href="{{ route('admin.school.profile') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.school.profile') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="school" class="text-emerald-400" />
                                    <span>School Profile & Branding</span>
                                </a>
                                @if($user && $user->hasRole('super-admin'))
                                    <a href="{{ route('admin.schools.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.schools.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="building" class="text-indigo-400" />
                                        <span>Schools (Provisioning)</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                        

                        
                        @if($user && $user->hasRole('super-admin'))
                            <!-- Portal Testing -->
                            <div class="space-y-1">
                                <button onclick="toggleMenu('portals')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                    <div class="flex items-center gap-2">
                                        <x-nav-icon name="portal" class="text-purple-400" />
                                        <span class="text-[10px] font-medium">PORTAL TESTING</span>
                                    </div>
                                    <svg id="portals-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="portals-menu" class="hidden space-y-1 pl-6">
                                    <a href="{{ route('teacher.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('teacher.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="user" class="text-purple-400" />
                                        <span>Teacher Portal</span>
                                    </a>
                                    <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('student.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="user" class="text-cyan-400" />
                                        <span>Student Portal</span>
                                    </a>
                                    <a href="{{ route('student.flash-cards.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('student.flash-cards.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="book" class="text-amber-400" />
                                        <span>Student Flash Cards</span>
                                    </a>
                                    <a href="{{ route('parent.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('parent.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="users" class="text-orange-400" />
                                        <span>Parent Portal</span>
                                    </a>
                                    <a href="{{ route('librarian.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('librarian.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="book-open" class="text-pink-400" />
                                        <span>Librarian Portal</span>
                                    </a>
                                    
                                    <!-- SDA Portal Testing -->
                                    <div class="text-xs text-slate-500 font-medium px-3 py-1 mt-2">SDA PORTALS</div>
                                    <a href="{{ route('sda.chairman.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.chairman.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="dashboard" class="text-emerald-400" />
                                        <span>SDA Chairman Dashboard</span>
                                    </a>
                                    <a href="{{ route('sda.secretary.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.secretary.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="dashboard" class="text-blue-400" />
                                        <span>SDA Secretary Dashboard</span>
                                    </a>
                                    <a href="{{ route('sda.finance.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.finance.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="dashboard" class="text-green-400" />
                                        <span>SDA Finance Dashboard</span>
                                    </a>
                                    <a href="{{ route('sda.procurement.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.procurement.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="dashboard" class="text-yellow-400" />
                                        <span>SDA Procurement Dashboard</span>
                                    </a>
                                    <a href="{{ route('sda.member.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('sda.member.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="dashboard" class="text-purple-400" />
                                        <span>SDA Member Dashboard</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if($user && $user->hasRole(['teacher', 'super-admin']))
                        <!-- Teacher Portal -->
                        <div class="space-y-1">
                            <a href="{{ route('teacher.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('teacher.dashboard') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                <x-nav-icon name="dashboard" class="text-violet-400" />
                                <span>Teacher Dashboard</span>
                            </a>
                            <button onclick="toggleMenu('teacher-portal')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition text-slate-300">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="book-open" class="text-violet-400" />
                                    <span class="text-[10px] font-medium">TEACHING</span>
                                </div>
                                <svg id="teacher-portal-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="teacher-portal-menu" class="{{ request()->routeIs('teacher.*') ? '' : 'hidden' }} space-y-1 pl-6">
                                <a href="{{ route('teacher.schemes-of-work.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('teacher.schemes-of-work.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="book" class="text-cyan-400" />
                                    <span>Schemes of Work</span>
                                </a>
                                <a href="{{ route('teacher.timetable.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('teacher.timetable.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="calendar" class="text-pink-400" />
                                    <span>My Timetable</span>
                                </a>
                                <a href="{{ route('teacher.flash-cards.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('teacher.flash-cards.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="book" class="text-amber-400" />
                                    <span>Flash Cards</span>
                                </a>
                                <a href="{{ route('admin.exams.performance-reports.my-subjects') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('admin.exams.performance-reports.my-subjects') || request()->routeIs('admin.exams.performance-reports.teacher-entry') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="chart" class="text-purple-400" />
                                    <span>End-of-Term Reports</span>
                                </a>
                                @if($user->hasRole('ict-coordinator'))
                                    <a href="{{ route('ict.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('ict.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="browser" class="text-cyan-400" />
                                        <span>ICT Coordinator Desk</span>
                                    </a>
                                @endif
                                <a href="{{ route('ict.tickets.create') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('ict.tickets.create') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="headset" class="text-teal-400" />
                                    <span>Request ICT Support</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($user && ($user->hasRole(['ict-coordinator', 'super-admin', 'school-admin']) || $user->hasRole('teacher')))
                        <!-- ICT Coordination & Helpdesk -->
                        <div class="space-y-1">
                            <button onclick="toggleMenu('ict')" class="w-full flex items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('ict.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                <div class="flex items-center gap-2">
                                    <x-nav-icon name="headset" class="text-cyan-400" />
                                    <span class="text-[10px] font-medium">ICT & HELPDESK</span>
                                </div>
                                <svg id="ict-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="ict-menu" class="{{ request()->routeIs('ict.*') ? '' : 'hidden' }} space-y-1 pl-6">
                                @if($user->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin']))
                                    <a href="{{ route('ict.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('ict.dashboard') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="browser" class="text-cyan-400" />
                                        <span>Coordinator Desk</span>
                                    </a>
                                    <a href="{{ route('ict.tickets.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('ict.tickets.index') || request()->routeIs('ict.tickets.show') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="inbox" class="text-amber-400" />
                                        <span>Support Queue</span>
                                    </a>
                                    <a href="{{ route('ict.devices.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('ict.devices.*') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                        <x-nav-icon name="flask" class="text-emerald-400" />
                                        <span>Lab Readiness</span>
                                    </a>
                                @endif
                                <a href="{{ route('ict.tickets.create') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('ict.tickets.create') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="headset" class="text-purple-400" />
                                    <span>Request Support</span>
                                </a>
                                <a href="{{ route('ict.tickets.my-tickets') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-800/80 transition {{ request()->routeIs('ict.tickets.my-tickets') ? 'bg-slate-800/80 text-slate-50' : 'text-slate-300' }}">
                                    <x-nav-icon name="clipboard" class="text-blue-400" />
                                    <span>My Requests</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </nav>
                @if($user)
                    <div class="border-t border-slate-800 px-4 py-4 text-xs text-slate-400 space-y-1">
                        <div class="font-medium text-slate-100">{{ $user->name }}</div>
                        <div>{{ $user->school?->name ?? 'No school linked' }}</div>
                    </div>
                @endif
            </aside>

            <div class="flex-1 flex flex-col min-w-0 min-h-screen">
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
                            <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 flex items-center justify-between">
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200 flex items-center gap-2">
                                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>{{ session('error') }}</span>
                            </div>
                        @endif

                        @if (session('warning'))
                            <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-200 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span>{{ session('warning') }}</span>
                            </div>
                        @endif

                        @if (isset($errors) && $errors->any())
                            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                                <div class="font-semibold flex items-center gap-2 mb-1 text-rose-300">
                                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Please correct the errors below:</span>
                                </div>
                                <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-300 pl-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
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
                    currentRoute.includes('/timetables') || 
                    currentRoute.includes('/attendance') ||
                    currentRoute.includes('/students')) {
                    toggleMenu('academic');
                } else if (currentRoute.includes('/hostels') || currentRoute.includes('/dormitories')) {
                    toggleMenu('hostels');
                } else if (currentRoute.includes('/year-end')) {
                    toggleMenu('yearend');
                } else if (currentRoute.includes('/career-guidance')) {
                    toggleMenu('career');
                } else if (currentRoute.includes('/employees') || currentRoute.includes('/departments') || 
                    currentRoute.includes('/leaves') || currentRoute.includes('/payroll') || 
                    currentRoute.includes('/loans') || currentRoute.includes('/teachers')) {
                    toggleMenu('hr');
                } else if (currentRoute.includes('/fixed-assets') || currentRoute.includes('/inventory/settings')) {
                    toggleMenu('assets');
                } else if (currentRoute.includes('/procurement')) {
                    toggleMenu('procurement');
                } else if (currentRoute.includes('/inventory') || currentRoute.includes('/dashboard/stores-manager')) {
                    toggleMenu('inventory');
                } else if (currentRoute.includes('/fees') || currentRoute.includes('/invoices') || 
                       currentRoute.includes('/receipts') || currentRoute.includes('/customers') || 
                       currentRoute.includes('/vendors') || currentRoute.includes('/budgets') ||
                       currentRoute.includes('/accounts') || currentRoute.includes('/journals') || 
                       currentRoute.includes('/bank-reconciliations') || currentRoute.includes('/interbank-transfers') ||
                       currentRoute.includes('/bank-accounts') || currentRoute.includes('/cashbook') ||
                       currentRoute.includes('/credit-notes') || currentRoute.includes('/ai-finance')) {
                    toggleMenu('finance');
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
                } else if (currentRoute.includes('/schemes-of-work') || currentRoute.includes('/teacher/flash-cards')) {
                    toggleMenu('teacher-portal');
                } else if (currentRoute.includes('/ict')) {
                    toggleMenu('ict');
                }
            });
        </script>

        <script>
            // Sidebar unread badge polling
            (function() {
                function pollUnread() {
                    fetch('/admin/communication/unread-count', {
                        headers: { 
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(r) { 
                        if (!r.ok) return null;
                        return r.json(); 
                    })
                    .then(function(data) {
                        if (!data) return;
                        var badge = document.getElementById('sidebarUnreadBadge');
                        if (!badge) return;
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.classList.remove('hidden');
                            document.title = '(' + data.count + ') ' + document.title.replace(/^\(\d+\)\s*/, '');
                        } else {
                            badge.classList.add('hidden');
                            document.title = document.title.replace(/^\(\d+\)\s*/, '');
                        }
                    })
                    .catch(function(err) {
                        // Suppress network/aborted polling errors quietly
                    });
                }
                pollUnread();
                setInterval(pollUnread, 8000);
            })();
        </script>
    </body>
</html>
