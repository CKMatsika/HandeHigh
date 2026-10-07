@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.user-management.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Edit User: {{ $user->name }}</h1>
            <p class="text-xs text-slate-400 mt-1">Update user account information, roles, and permissions.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('admin.user-management.update', $user) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Basic Information</h3>
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Email Address *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('email')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Phone Number *</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Account Status</label>
                        <div class="flex items-center gap-3 pt-1">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-xs font-medium text-slate-300">Active Account</span>
                            </label>
                        </div>
                        @error('is_active')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Profile Photo</label>
                        @if($user->profile_photo)
                            <div class="mb-2 flex items-center gap-3">
                                <img src="{{ Storage::url($user->profile_photo) }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover border border-slate-700">
                                <span class="text-xs text-slate-400">Current photo</span>
                            </div>
                        @endif
                        <input type="file" name="profile_photo" accept="image/*"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-600 file:text-white hover:file:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('profile_photo')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-50 border-b border-slate-800 pb-2">Additional Information</h3>
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth) }}"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('date_of_birth')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Gender</label>
                        <select name="gender"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $user->gender) === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $user->gender) === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Address</label>
                        <textarea name="address" rows="3"
                                  class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="123 Main St, City, State">{{ old('address', $user->address) }}</textarea>
                        @error('address')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Emergency Contact</label>
                        <input type="text" name="emergency_contact" value="{{ old('emergency_contact', $user->emergency_contact) }}"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Jane Doe">
                        @error('emergency_contact')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Emergency Phone</label>
                        <input type="text" name="emergency_phone" value="{{ old('emergency_phone', $user->emergency_phone) }}"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="+1234567890">
                        @error('emergency_phone')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Roles and Permissions -->
            <div class="mt-8 pt-6 border-t border-slate-800">
                <h3 class="text-sm font-semibold text-slate-50 mb-4">Roles and Permissions</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Roles *</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @foreach($roles as $role)
                                <label class="flex items-center p-2 bg-slate-800/50 rounded-lg cursor-pointer hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                                           {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }}
                                           class="mr-3 text-blue-600 focus:ring-blue-500 border-slate-600 rounded">
                                    <span class="text-sm text-slate-300">{{ ucfirst($role->name) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('roles')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Additional Permissions</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @foreach($permissions as $permission)
                                <label class="flex items-center p-2 bg-slate-800/50 rounded-lg cursor-pointer hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                           {{ in_array($permission->id, old('permissions', $userPermissions)) ? 'checked' : '' }}
                                           class="np-3 text-blue-600 focus:ring-blue-500 border-slate-600 rounded mr-3">
                                    <span class="text-sm text-slate-300">{{ ucfirst(str_replace('_', ' ', $permission->name)) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Optional: Assign additional permissions beyond role permissions</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Update User
                </button>
                <a href="{{ route('admin.user-management.index') }}" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-medium py-2 px-4 rounded-lg text-center transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
