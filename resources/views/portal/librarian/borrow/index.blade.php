@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Borrow Management</h1>
            <p class="text-xs text-slate-400 mt-1">Issue and return books to students</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('librarian.dashboard') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Dashboard</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 rounded-lg border border-emerald-700/50 bg-emerald-950/20">
            <p class="text-xs text-emerald-400">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 rounded-lg border border-red-700/50 bg-red-950/20">
            <p class="text-xs text-red-400">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Issue Book Form -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800">
                <h2 class="text-sm font-medium text-slate-50">Issue Book</h2>
            </div>
            <form action="{{ route('librarian.borrow.store') }}" method="POST" class="p-4">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="student_id" class="block text-xs font-medium text-slate-300 mb-2">Select Student *</label>
                        <select id="student_id" name="student_id" required
                                class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                            <option value="">Choose a student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }} - {{ $student->grade }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="book_id" class="block text-xs font-medium text-slate-300 mb-2">Select Book *</label>
                        <select id="book_id" name="book_id" required
                                class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                            <option value="">Choose a book</option>
                            @foreach($availableBooks as $book)
                                <option value="{{ $book->id }}">{{ $book->title }} by {{ $book->author }} ({{ $book->available_copies }} available)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="due_date" class="block text-xs font-medium text-slate-300 mb-2">Due Date *</label>
                        <input type="date" id="due_date" name="due_date" required
                               min="{{ now()->addDay()->format('Y-m-d') }}"
                               max="{{ now()->addDays(30)->format('Y-m-d') }}"
                               value="{{ now()->addDays(14)->format('Y-m-d') }}"
                               class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">
                        Issue Book
                    </button>
                </div>
            </form>
        </div>

        <!-- Current Borrowed Books -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-50">Current Borrowed Books</h2>
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400">
                    {{ $currentBorrows->count() }} books
                </span>
            </div>
            <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                @forelse($currentBorrows as $borrow)
                    <div class="p-3 rounded-lg border border-slate-700 bg-slate-800/50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-medium text-slate-50">{{ $borrow->book->title }}</p>
                                <p class="text-[10px] text-slate-400 mt-1">{{ $borrow->student->first_name }} {{ $borrow->student->last_name }}</p>
                                <p class="text-[10px] text-slate-500 mt-2">
                                    Borrowed: {{ $borrow->borrowed_at->format('M j, Y') }} • 
                                    Due: {{ $borrow->due_date->format('M j, Y') }}
                                </p>
                                @if($borrow->due_date->isPast())
                                    <p class="text-[10px] text-red-400 mt-1">
                                        {{ $borrow->due_date->diffInDays(now()) }} days overdue
                                    </p>
                                @endif
                            </div>
                            <form action="{{ route('librarian.borrow.return', $borrow) }}" method="POST" onsubmit="return confirm('Mark this book as returned?')">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-3 py-1 text-[10px] font-medium text-white hover:bg-emerald-600 transition">
                                    Return
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No books currently borrowed</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Available Books</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $availableBooks->sum('available_copies') }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-emerald-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">Currently Borrowed</p>
                    <p class="text-lg font-semibold text-slate-50">{{ $currentBorrows->count() }}</p>
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
                    <p class="text-lg font-semibold text-slate-50">{{ $currentBorrows->where('due_date', '<', now())->count() }}</p>
                </div>
                <span class="h-8 w-8 rounded-full bg-red-500/20 flex items-center justify-center">
                    <span class="h-2 w-2 rounded-full bg-red-400"></span>
                </span>
            </div>
        </div>
    </div>
@endsection
