@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Librarian Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Welcome back, {{ $user->name }}!</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('librarian.books.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Manage Books</a>
            <a href="{{ route('librarian.borrow.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Issue/Return</a>
            <a href="{{ route('librarian.profile') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Profile</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Total Books</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $books->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-violet-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-violet-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Borrowed Books</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $borrowedBooks->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-amber-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Overdue Books</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $overdueBooks->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-red-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-red-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Outstanding Fines</p>
                    <p class="text-lg font-semibold text-slate-50">${{ number_format($fines->sum('amount'), 0) }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-rose-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Overdue Books -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Overdue Books</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($overdueBooks as $borrow)
                        <div class="p-3 rounded-lg border border-red-700/50 bg-red-950/20">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-50">{{ $borrow->book->title }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $borrow->student->first_name }} {{ $borrow->student->last_name }}</p>
                                    <p class="text-[10px] text-red-400 mt-2">Due: {{ $borrow->due_date->format('M j, Y') }}</p>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/20 text-red-400">
                                    {{ $borrow->due_date->diffInDays(now()) }} days late
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No overdue books</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Returns -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Recent Returns</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($recentReturns as $return)
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-50">{{ $return->book }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $return->student }}</p>
                                    <p class="text-[10px] text-slate-500 mt-2">Returned: {{ $return->returned_date->format('M j, Y h:i A') }}</p>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">
                                    {{ $return->condition }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No recent returns</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Library Announcements -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-medium text-slate-50">Library Announcements</h2>
                    <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">Manage</a>
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

    <!-- Popular Books & Current Borrowing -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Popular Books -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Popular Books</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All Books</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($popularBooks as $book)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $book->title }}</p>
                            <p class="text-[10px] text-slate-400">{{ $book->author }} • {{ $book->borrow_count }} borrows</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-slate-50">{{ $book->available }}</p>
                            <p class="text-[10px] text-slate-400">available</p>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No books available</p>
                @endforelse
            </div>
        </div>

        <!-- Current Borrowing -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Current Borrowing</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">View All</a>
            </div>
            <div class="p-4 space-y-2">
                @forelse($borrowedBooks->take(5) as $borrow)
                    <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                        <div>
                            <p class="text-xs font-medium text-slate-50">{{ $borrow->book->title }}</p>
                            <p class="text-[10px] text-slate-400">{{ $borrow->student->first_name }} {{ $borrow->student->last_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-medium text-slate-50">{{ $borrow->due_date->format('M j') }}</p>
                            <p class="text-[10px] text-slate-400">{{ $borrow->due_date->diffInDays(now()) > 0 ? $borrow->due_date->diffInDays(now()) . ' days left' : 'Due today' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No books currently borrowed</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Fines Summary -->
    <div class="mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Outstanding Fines</h2>
                <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300 transition">Manage Fines</a>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Total Outstanding</p>
                        <p class="text-lg font-semibold text-amber-400">${{ number_format($fines->sum('amount'), 2) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400">Unpaid Fines</p>
                        <p class="text-lg font-semibold text-red-400">{{ $fines->count() }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-slate-400">This Week</p>
                        <p class="text-lg font-semibold text-slate-50">${{ number_format($fines->where('date', '>=', now()->startOfWeek())->sum('amount'), 2) }}</p>
                    </div>
                </div>
                
                <div class="space-y-2">
                    @forelse($fines as $fine)
                        <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                            <div>
                                <p class="text-xs font-medium text-slate-50">{{ $fine->student }} • {{ $fine->book }}</p>
                                <p class="text-[10px] text-slate-400">{{ $fine->reason }} • {{ $fine->date->format('M j, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold text-amber-400">${{ number_format($fine->amount, 2) }}</p>
                                </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No outstanding fines</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
