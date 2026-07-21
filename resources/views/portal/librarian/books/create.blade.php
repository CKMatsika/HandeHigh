@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Add New Book</h1>
            <p class="text-xs text-slate-400 mt-1">Add a new book to the library inventory</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('librarian.books.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back to Books</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800">
            <h2 class="text-sm font-medium text-slate-50">Book Information</h2>
        </div>
        <form action="{{ route('librarian.books.store') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-xs font-medium text-slate-300 mb-2">Title *</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required
                           class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                           placeholder="Enter book title">
                    @error('title')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="author" class="block text-xs font-medium text-slate-300 mb-2">Author *</label>
                    <input type="text" id="author" name="author" value="{{ old('author') }}" required
                           class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                           placeholder="Enter author name">
                    @error('author')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="isbn" class="block text-xs font-medium text-slate-300 mb-2">ISBN *</label>
                    <input type="text" id="isbn" name="isbn" value="{{ old('isbn') }}" required
                           class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                           placeholder="Enter ISBN number">
                    @error('isbn')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="block text-xs font-medium text-slate-300 mb-2">Category *</label>
                    <select id="category" name="category" required
                            class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                        <option value="">Select category</option>
                        <option value="Fiction" {{ old('category') == 'Fiction' ? 'selected' : '' }}>Fiction</option>
                        <option value="Non-Fiction" {{ old('category') == 'Non-Fiction' ? 'selected' : '' }}>Non-Fiction</option>
                        <option value="Science" {{ old('category') == 'Science' ? 'selected' : '' }}>Science</option>
                        <option value="Mathematics" {{ old('category') == 'Mathematics' ? 'selected' : '' }}>Mathematics</option>
                        <option value="History" {{ old('category') == 'History' ? 'selected' : '' }}>History</option>
                        <option value="Geography" {{ old('category') == 'Geography' ? 'selected' : '' }}>Geography</option>
                        <option value="Literature" {{ old('category') == 'Literature' ? 'selected' : '' }}>Literature</option>
                        <option value="Reference" {{ old('category') == 'Reference' ? 'selected' : '' }}>Reference</option>
                        <option value="Biography" {{ old('category') == 'Biography' ? 'selected' : '' }}>Biography</option>
                        <option value="Technology" {{ old('category') == 'Technology' ? 'selected' : '' }}>Technology</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="publisher" class="block text-xs font-medium text-slate-300 mb-2">Publisher</label>
                    <input type="text" id="publisher" name="publisher" value="{{ old('publisher') }}"
                           class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                           placeholder="Enter publisher name">
                    @error('publisher')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="publication_year" class="block text-xs font-medium text-slate-300 mb-2">Publication Year</label>
                    <input type="number" id="publication_year" name="publication_year" value="{{ old('publication_year') }}" min="1900" max="{{ date('Y') }}"
                           class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                           placeholder="Enter publication year">
                    @error('publication_year')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="total_copies" class="block text-xs font-medium text-slate-300 mb-2">Total Copies *</label>
                    <input type="number" id="total_copies" name="total_copies" value="{{ old('total_copies', 1) }}" required min="1"
                           class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                           placeholder="Enter total copies">
                    @error('total_copies')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full text-xs px-3 py-2 rounded-lg border border-slate-700 bg-slate-800/50 text-slate-50 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                              placeholder="Enter book description (optional)">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <a href="{{ route('librarian.books.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                    Add Book
                </button>
            </div>
        </form>
    </div>
@endsection
