@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Timetable</h1>
        <div class="flex space-x-2">
            <a href="{{ route('timetables.show') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                View Timetable
            </a>
            <button type="button" onclick="saveTimetable()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">
                Save Changes
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="p-4 border-b">
            <div class="flex flex-wrap gap-4">
                <div class="w-full md:w-auto">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Class</label>
                    <select id="classSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">-- Select Class --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-auto">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Week</label>
                    <select id="weekSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="1">Week 1</option>
                        <option value="2">Week 2</option>
                    </select>
                </div>
                <div class="w-full md:w-auto">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Actions</label>
                    <button type="button" onclick="addNewRow()" class="w-full md:w-auto bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-sm">
                        Add Time Slot
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="timetableEdit" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $day }}
                            </th>
                        @endforeach
                        <th class="px-2 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="timeSlotsContainer">
                    <!-- Time slots will be added here dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Subject/Teacher Selection Modal -->
    <div id="subjectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg w-full max-w-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Select Subject & Teacher</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <select id="subjectSelect" class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- Select Subject --</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                    <select id="teacherSelect" class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- Select Teacher --</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                    <select id="roomSelect" class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- Select Room --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->capacity }} seats)</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" onclick="saveCell()" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentCell = null;
    
    function openModal(cell) {
        currentCell = cell;
        document.getElementById('subjectModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('subjectModal').classList.add('hidden');
        currentCell = null;
    }
    
    function saveCell() {
        if (!currentCell) return;
        
        const subjectSelect = document.getElementById('subjectSelect');
        const teacherSelect = document.getElementById('teacherSelect');
        const roomSelect = document.getElementById('roomSelect');
        
        if (subjectSelect.value && teacherSelect.value) {
            currentCell.innerHTML = `
                <div class="text-center cursor-pointer" onclick="openModal(this.parentElement)">
                    <div class="font-medium">${subjectSelect.options[subjectSelect.selectedIndex].text}</div>
                    <div class="text-xs text-gray-500">${roomSelect.options[roomSelect.selectedIndex].text.split(' (')[0]}</div>
                    <div class="text-xs text-gray-400">${teacherSelect.options[teacherSelect.selectedIndex].text}</div>
                </div>
            `;
            
            // Add data attributes for saving
            currentCell.dataset.subjectId = subjectSelect.value;
            currentCell.dataset.teacherId = teacherSelect.value;
            currentCell.dataset.roomId = roomSelect.value;
            
            // Mark as modified
            currentCell.classList.add('bg-blue-50');
        }
        
        closeModal();
    }
    
    function addNewRow() {
        const container = document.getElementById('timeSlotsContainer');
        const timeSlots = container.querySelectorAll('.time-slot');
        const newId = timeSlots.length;
        
        const row = document.createElement('tr');
        row.className = 'time-slot';
        row.innerHTML = `
            <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center">
                    <input type="time" class="border rounded px-2 py-1 w-24" value="08:00">
                    <span class="mx-1">-</span>
                    <input type="time" class="border rounded px-2 py-1 w-24" value="09:00">
                </div>
            </td>
            ${Array(5).fill().map((_, i) => `
                <td class="border p-1 text-center cursor-pointer hover:bg-gray-50 min-w-[120px]" 
                    onclick="openModal(this)">
                    <div class="h-16 flex items-center justify-center text-gray-400">
                        <span>Click to add</span>
                    </div>
                </td>
            `).join('')}
            <td class="px-2 py-3 whitespace-nowrap text-right text-sm font-medium">
                <button type="button" onclick="removeRow(this)" class="text-red-600 hover:text-red-900">
                    Remove
                </button>
            </td>
        `;
        
        container.appendChild(row);
    }
    
    function removeRow(button) {
        if (confirm('Are you sure you want to remove this time slot?')) {
            button.closest('tr').remove();
        }
    }
    
    function saveTimetable() {
        const timetableData = [];
        const rows = document.querySelectorAll('#timeSlotsContainer .time-slot');
        
        rows.forEach((row, rowIndex) => {
            const timeInputs = row.querySelectorAll('input[type="time"]');
            const cells = row.querySelectorAll('td:not(:first-child):not(:last-child)');
            
            cells.forEach((cell, dayIndex) => {
                if (cell.dataset.subjectId) {
                    timetableData.push({
                        day: dayIndex + 1, // 1=Monday, 2=Tuesday, etc.
                        start_time: timeInputs[0].value,
                        end_time: timeInputs[1].value,
                        subject_id: cell.dataset.subjectId,
                        teacher_id: cell.dataset.teacherId,
                        room_id: cell.dataset.roomId
                    });
                }
            });
        });
        
        // Here you would typically send this data to the server
        console.log('Saving timetable data:', timetableData);
        
        // Show success message
        alert('Timetable saved successfully!');
    }
    
    // Initialize with one empty row
    document.addEventListener('DOMContentLoaded', () => {
        addNewRow();
    });
</script>
@endpush

<style>
    [x-cloak] { display: none !important; }
    .time-slot td { vertical-align: top; }
    .time-slot input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(0.5);
    }
</style>
@endsection
