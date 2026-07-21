@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Classes</h1>
            <p class="text-xs text-slate-400 mt-1">Manage classes by grade and academic year.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.classes.create') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Add Class</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Grade</th>
                        <th class="px-4 py-3 text-left font-medium">Academic Year</th>
                        <th class="px-4 py-3 text-left font-medium">Term</th>
                        <th class="px-4 py-3 text-left font-medium">Teacher</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($classes as $class)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $class->name }}</td>
                            <td class="px-4 py-3">{{ $class->grade }}</td>
                            <td class="px-4 py-3">{{ $class->academic_year }}</td>
                            <td class="px-4 py-3">{{ $class->term ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $class->teacher?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.classes.show', $class) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">View</a>
                                    <a href="{{ route('admin.classes.edit', $class) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">Edit</a>
                                    <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" onsubmit="return confirm('Delete this class?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-full bg-red-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-red-600 transition">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No classes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $classes->links() }}
@endsection
