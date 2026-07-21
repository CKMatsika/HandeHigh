@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Book Management</h1>
            <p class="text-xs text-slate-400 mt-1">Manage your school library inventory</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('librarian.books.create') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Add New Book
            </a>
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

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-medium text-slate-50">Books Inventory</h2>
            <div class="flex items-center gap-2">
                <input type="search" placeholder="Search books..." class="text-xs px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">Author</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">ISBN</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">Copies</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">Available</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($books as $book)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-xs font-medium text-slate-50">{{ $book->title }}</p>
                                    @if($book->publisher)
                                        <p class="text-[10px] text-slate-400">{{ $book->publisher }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-slate-50">{{ $book->author }}</p>
                                @if($book->publication_year)
                                    <p class="text-[10px] text-slate-400">{{ $book->publication_year }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-violet-500/20 text-violet-400">
                                    {{ $book->category }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-slate-50 font-mono">{{ $book->isbn }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-slate-50">{{ $book->total_copies }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-slate-50">{{ $book->available_copies }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($book->available_copies === $book->total_copies)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">Available</span>
                                @elseif($book->available_copies > 0)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400">Some Borrowed</span>
                                @else
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-500/20 text-red-400">All Borrowed</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('librarian.books.show', $book) }}" class="text-[10px] text-indigo-400 hover:text-indigo-300 transition">View</a>
                                    <span class="text-[10px] text-slate-600">•</span>
                                    <a href="{{ route('librarian.books.edit', $book) }}" class="text-[10px] text-indigo-400 hover:text-indigo-300 transition">Edit</a>
                                    @if($book->available_copies === $book->total_copies)
                                        <span class="text-[10px] text-slate-600">•</span>
                                        <form action="{{ route('librarian.books.destroy', $book) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this book?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[10px] text-red-400 hover:text-red-300 transition">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center">
                                <p class="text-sm text-slate-500">No books found</p>
                                <a href="{{ route('librarian.books.create') }}" class="inline-flex items-center mt-2 rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                                    Add Your First Book
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($books->hasPages())
            <div class="px-4 py-3 border-t border-slate-800">
                {{ $books->links() }}
            </div>
        @endif
    </div>
@endsection
