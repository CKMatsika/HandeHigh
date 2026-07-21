@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Timetable Conflicts</h1>
            <p class="text-sm text-gray-600">Review and resolve scheduling conflicts</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('timetables.show') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm">
                View Timetable
            </a>
            <a href="{{ route('timetables.edit') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
                Edit Timetable
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="p-4 border-b">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by:</label>
                        <select id="filterType" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="all">All Conflicts</option>
                            <option value="teacher">Teacher Conflicts</option>
                            <option value="room">Room Conflicts</option>
                            <option value="class">Class Conflicts</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Severity:</label>
                        <select id="filterSeverity" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="all">All Levels</option>
                            <option value="high">High Priority</option>
                            <option value="medium">Medium Priority</option>
                            <option value="low">Low Priority</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Last checked: {{ now()->format('M d, Y H:i') }}</span>
                    <button onclick="refreshConflicts()" class="text-blue-500 hover:text-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="divide-y divide-gray-200">
            <!-- Conflict Item -->
            <div class="p-4 hover:bg-gray-50 conflict-item" data-type="teacher" data-severity="high">
                <div class="flex items-start">
                    <div class="flex-shrink-0 pt-0.5">
                        <div class="h-5 w-5 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="h-3 w-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-red-700">Teacher Double Booking</h3>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                High Priority
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-gray-600">
                            <p>Mr. Smith is scheduled to teach two classes at the same time:</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Mathematics - Grade 10A (Room 101)</li>
                                <li>Physics - Grade 11B (Room 205)</li>
                            </ul>
                        </div>
                        <div class="mt-2 flex space-x-3 text-xs text-gray-500">
                            <span>Monday, 09:00 - 10:00</span>
                            <span>•</span>
                            <span>Term 2, Week 1</span>
                        </div>
                        <div class="mt-2 flex space-x-2">
                            <button type="button" class="inline-flex items-center px-2.5 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                View Details
                            </button>
                            <button type="button" class="inline-flex items-center px-2.5 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Resolve Conflict
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- More conflict examples -->
            <div class="p-4 hover:bg-gray-50 conflict-item" data-type="room" data-severity="medium">
                <div class="flex items-start">
                    <div class="flex-shrink-0 pt-0.5">
                        <div class="h-5 w-5 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="h-3 w-3 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-yellow-700">Room Double Booking</h3>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Medium Priority
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-gray-600">
                            <p>Room 205 is double booked for two different classes:</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Physics - Grade 11B (Mr. Smith)</li>
                                <li>Chemistry - Grade 12A (Mrs. Johnson)</li>
                            </ul>
                        </div>
                        <div class="mt-2 flex space-x-3 text-xs text-gray-500">
                            <span>Wednesday, 11:00 - 12:00</span>
                            <span>•</span>
                            <span>Term 2, Week 1</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 hover:bg-gray-50 conflict-item" data-type="class" data-severity="low">
                <div class="flex items-start">
                    <div class="flex-shrink-0 pt-0.5">
                        <div class="h-5 w-5 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="h-3 w-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-blue-700">Class Without Break</h3>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                Low Priority
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-gray-600">
                            <p>Grade 8A has 4 consecutive classes without a break:</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>08:00 - 09:00: Mathematics</li>
                                <li>09:00 - 10:00: Science</li>
                                <li>10:00 - 11:00: English</li>
                                <li>11:00 - 12:00: History</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflict Resolution Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500">Critical Issues</h3>
                    <p class="text-2xl font-semibold text-gray-900">3</p>
                </div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500">Warnings</h3>
                    <p class="text-2xl font-semibold text-gray-900">7</p>
                </div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500">Resolved</h3>
                    <p class="text-2xl font-semibold text-gray-900">12</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function refreshConflicts() {
        // Simulate loading
        const refreshBtn = event.currentTarget;
        const originalHTML = refreshBtn.innerHTML;
        
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-500 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Checking...
        `;
        
        // Simulate API call
        setTimeout(() => {
            // In a real app, this would fetch new conflicts from the server
            refreshBtn.innerHTML = originalHTML;
            refreshBtn.disabled = false;
            
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg';
            toast.textContent = 'Timetable conflicts refreshed successfully';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }, 1500);
    }
    
    // Filter functionality
    document.addEventListener('DOMContentLoaded', () => {
        const filterType = document.getElementById('filterType');
        const filterSeverity = document.getElementById('filterSeverity');
        const conflictItems = document.querySelectorAll('.conflict-item');
        
        function applyFilters() {
            const typeValue = filterType.value;
            const severityValue = filterSeverity.value;
            
            conflictItems.forEach(item => {
                const itemType = item.dataset.type;
                const itemSeverity = item.dataset.severity;
                
                const typeMatch = typeValue === 'all' || itemType === typeValue;
                const severityMatch = severityValue === 'all' || itemSeverity === severityValue;
                
                if (typeMatch && severityMatch) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        filterType.addEventListener('change', applyFilters);
        filterSeverity.addEventListener('change', applyFilters);
    });
</script>
@endpush

<style>
    .conflict-item {
        transition: background-color 0.2s ease-in-out;
    }
    
    .conflict-item:hover {
        background-color: #f9fafb;
    }
</style>
@endsection
