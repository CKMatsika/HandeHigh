<!-- Library Tab -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
    <h3 class="text-lg font-semibold text-slate-50 mb-6">Library Access</h3>

    <form method="POST" action="{{ route('admin.students.update-library', $student) }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Settings -->
            <div class="space-y-4">
                <h4 class="text-sm font-medium text-slate-300">Membership Settings</h4>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_member" value="1" id="isMember"
                        {{ ($student->libraryAccess->is_member ?? false) ? 'checked' : '' }}
                        class="w-4 h-4 rounded border-slate-600 bg-slate-700 text-indigo-500 focus:ring-indigo-500">
                    <label for="isMember" class="text-sm text-slate-300">Active Library Member</label>
                </div>
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">Max Books Allowed</label>
                    <input type="number" name="max_books" value="{{ $student->libraryAccess->max_books ?? 3 }}" min="1" required
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">Max Borrowing Days</label>
                    <input type="number" name="max_days" value="{{ $student->libraryAccess->max_days ?? 14 }}" min="1" required
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">Fine Per Day (overdue)</label>
                    <input type="number" name="fine_per_day" value="{{ $student->libraryAccess->fine_per_day ?? 0.50 }}" min="0" step="0.01" required
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-400 mb-1 block">Notes</label>
                    <input type="text" name="notes" value="{{ $student->libraryAccess->notes ?? '' }}" placeholder="Optional notes"
                        class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm transition">
                    {{ $student->libraryAccess ? 'Update Settings' : 'Enable Library Access' }}
                </button>
            </div>

            <!-- Status -->
            <div class="space-y-4">
                <h4 class="text-sm font-medium text-slate-300">Current Status</h4>
                @if($student->libraryAccess)
                    <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-slate-400 text-sm">Status</span>
                            <span class="text-sm {{ $student->libraryAccess->is_member ? 'text-green-400' : 'text-red-400' }}">
                                {{ $student->libraryAccess->is_member ? 'Active Member' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 text-sm">Member Since</span>
                            <span class="text-slate-100 text-sm">{{ $student->libraryAccess->membership_date ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 text-sm">Max Books</span>
                            <span class="text-slate-100 text-sm">{{ $student->libraryAccess->max_books }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 text-sm">Borrowing Period</span>
                            <span class="text-slate-100 text-sm">{{ $student->libraryAccess->max_days }} days</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 text-sm">Overdue Fine</span>
                            <span class="text-slate-100 text-sm">${{ number_format($student->libraryAccess->fine_per_day, 2) }}/day</span>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-800/30 rounded-xl p-6 border border-slate-700/30 text-center">
                        <p class="text-slate-500 text-sm">Library access not configured.</p>
                        <p class="text-slate-600 text-xs mt-1">Enable membership above to grant borrowing privileges.</p>
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>
