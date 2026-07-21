@extends('layouts.app')

@section('content')
    <h1 class="text-lg font-semibold text-slate-50 mb-4">Schools</h1>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-6">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Create new school</h2>
        <form method="POST" action="{{ route('admin.schools.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-100">
            @csrf
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="name">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="code">Code</label>
                <input id="code" type="text" name="code" value="{{ old('code') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="phone">Phone</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="address">Address</label>
                <input id="address" type="text" name="address" value="{{ old('address') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="timezone">Timezone</label>
                <input id="timezone" type="text" name="timezone" value="{{ old('timezone', 'Africa/Harare') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div>
                <label class="block text-[11px] font-medium mb-1 text-slate-300" for="currency">Currency</label>
                <input id="currency" type="text" name="currency" value="{{ old('currency', 'USD') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-xs">
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                    Create School
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Existing schools</h2>

        @if($schools->count())
            <table class="min-w-full text-xs text-slate-100">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400">
                        <th class="text-left py-2 font-medium">Name</th>
                        <th class="text-left py-2 font-medium">Code</th>
                        <th class="text-left py-2 font-medium">Email</th>
                        <th class="text-left py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schools as $school)
                        <tr class="border-b border-slate-800/70">
                            <td class="py-2 align-middle">{{ $school->name }}</td>
                            <td class="py-2 align-middle">{{ $school->code }}</td>
                            <td class="py-2 align-middle">{{ $school->email }}</td>
                            <td class="py-2 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.schools.update', $school) }}" class="flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $school->name }}" class="rounded border border-slate-700 bg-slate-950/70 px-2 py-1 text-[11px]">
                                    <input type="text" name="code" value="{{ $school->code }}" class="rounded border border-slate-700 bg-slate-950/70 px-2 py-1 text-[11px] w-20">
                                    <input type="email" name="email" value="{{ $school->email }}" class="rounded border border-slate-700 bg-slate-950/70 px-2 py-1 text-[11px]">
                                    <button type="submit" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">Save</button>
                                </form>
                                <form method="POST" action="{{ route('admin.schools.destroy', $school) }}" onsubmit="return confirm('Delete this school?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full bg-rose-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-rose-600 transition">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $schools->links() }}
            </div>
        @else
            <p class="text-xs text-slate-400">No schools found.</p>
        @endif
    </div>
@endsection
