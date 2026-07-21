@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Teachers</h1>
            <p class="text-xs text-slate-400 mt-1">Manage teaching staff and their assignments.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Add Teacher</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Phone</th>
                        <th class="px-4 py-3 text-left font-medium">Specialization</th>
                        <th class="px-4 py-3 text-left font-medium">Classes</th>
                        <th class="px-4 py-3 text-left font-medium">Subjects</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($teachers as $teacher)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-medium">{{ $teacher->name }}</td>
                            <td class="px-4 py-3">{{ $teacher->email }}</td>
                            <td class="px-4 py-3">{{ $teacher->phone ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $teacher->metadata['specialization'] ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $classCount = $school->classes()->where('teacher_id', $teacher->id)->count();
                                @endphp
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-700 text-slate-300">{{ $classCount }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $subjectCount = $school->curricula()->where('teacher_id', $teacher->id)->count();
                                @endphp
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-700 text-slate-300">{{ $subjectCount }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.teachers.show', $teacher) }}" class="text-indigo-400 hover:text-indigo-300 transition">View</a>
                                    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-emerald-400 hover:text-emerald-300 transition">Edit</a>
                                    <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-300 transition">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No teachers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $teachers->links() }}
@endsection
