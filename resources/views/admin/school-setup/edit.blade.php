@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.school-setup.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Edit School Information</h1>
            <p class="text-xs text-slate-400 mt-1">Update your school's basic information and details.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('admin.school-setup.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Basic Information</h3>
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">School Name *</label>
                        <input type="text" name="name" value="{{ $school->name }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Email *</label>
                        <input type="email" name="email" value="{{ $school->email }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('email')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Phone *</label>
                        <input type="text" name="phone" value="{{ $school->phone }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Website</label>
                        <input type="url" name="website" value="{{ $school->website }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="https://www.example.com">
                        @error('website')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">School Motto</label>
                        <input type="text" name="motto" value="{{ $school->motto }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Excellence in Education">
                        @error('motto')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Brief description of your school...">{{ $school->description }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Address and Additional Info -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Address & Additional Info</h3>
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Address *</label>
                        <input type="text" name="address" value="{{ $school->address }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('address')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">City *</label>
                            <input type="text" name="city" value="{{ $school->city }}" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('city')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">State *</label>
                            <input type="text" name="state" value="{{ $school->state }}" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('state')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Country *</label>
                            <input type="text" name="country" value="{{ $school->country }}" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('country')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Postal Code *</label>
                            <input type="text" name="postal_code" value="{{ $school->postal_code }}" required
                                   class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('postal_code')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">School Type *</label>
                        <select name="school_type" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="primary" {{ $school->school_type === 'primary' ? 'selected' : '' }}>Primary School</option>
                            <option value="secondary" {{ $school->school_type === 'secondary' ? 'selected' : '' }}>Secondary School</option>
                            <option value="tertiary" {{ $school->school_type === 'tertiary' ? 'selected' : '' }}>Tertiary Institution</option>
                            <option value="mixed" {{ $school->school_type === 'mixed' ? 'selected' : '' }}>Mixed Levels</option>
                        </select>
                        @error('school_type')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Established Year</label>
                        <input type="number" name="established_year" value="{{ $school->established_year }}"
                               min="1900" max="{{ date('Y') }}"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="1990">
                        @error('established_year')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">School Logo</label>
                        <input type="file" name="logo" accept="image/*"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-600 file:text-white hover:file:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('logo')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                        @if($school->logo)
                            <div class="mt-2 flex items-center gap-2">
                                <img src="{{ Storage::url($school->logo) }}" alt="Current logo" class="w-12 h-12 rounded object-cover">
                                <span class="text-xs text-slate-400">Current logo</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Academic Settings -->
            <div class="mt-8 pt-6 border-t border-slate-800">
                <h3 class="text-sm font-semibold text-slate-50 mb-4">Academic Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Academic Year Start *</label>
                        <input type="date" name="academic_year_start" value="{{ $settings['academic_year_start'] ?? '' }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('academic_year_start')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Academic Year End *</label>
                        <input type="date" name="academic_year_end" value="{{ $settings['academic_year_end'] ?? '' }}" required
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('academic_year_end')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Terms per Year *</label>
                        <select name="terms_per_year" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="1" {{ ($settings['terms_per_year'] ?? 3) == 1 ? 'selected' : '' }}>1 Term</option>
                            <option value="2" {{ ($settings['terms_per_year'] ?? 3) == 2 ? 'selected' : '' }}>2 Terms</option>
                            <option value="3" {{ ($settings['terms_per_year'] ?? 3) == 3 ? 'selected' : '' }}>3 Terms</option>
                            <option value="4" {{ ($settings['terms_per_year'] ?? 3) == 4 ? 'selected' : '' }}>4 Terms</option>
                        </select>
                        @error('terms_per_year')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Attendance Threshold (%) *</label>
                        <input type="number" name="attendance_threshold" value="{{ $settings['attendance_threshold'] ?? 75 }}" required
                               min="50" max="100"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('attendance_threshold')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Max Class Size *</label>
                        <input type="number" name="max_class_size" value="{{ $settings['max_class_size'] ?? 30 }}" required
                               min="10" max="100"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('max_class_size')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Update School Information
                </button>
                <a href="{{ route('admin.school-setup.index') }}" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium py-2 px-4 rounded-lg text-center transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
