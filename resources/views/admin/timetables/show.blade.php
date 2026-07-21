@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Class Timetable</h1>
        <div class="flex space-x-2">
            <a href="{{ route('timetables.edit') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                Edit Timetable
            </a>
            <a href="{{ route('timetables.conflicts') }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md">
                View Conflicts
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <div class="flex justify-between items-center">
                <div class="flex space-x-4">
                    <select class="border rounded px-3 py-1">
                        <option>Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <select class="border rounded px-3 py-1">
                        <option>Week 1</option>
                        <option>Week 2</option>
                    </select>
                </div>
                <div class="text-sm text-gray-600">
                    Last updated: {{ now()->format('M d, Y H:i') }}
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $day }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $timeSlots = [
                            '08:00 - 09:00',
                            '09:00 - 10:00',
                            '10:00 - 11:00',
                            '11:00 - 12:00',
                            '12:00 - 13:00', // Lunch
                            '13:00 - 14:00',
                            '14:00 - 15:00',
                            '15:00 - 16:00',
                        ];
                    @endphp

                    @foreach($timeSlots as $timeSlot)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $timeSlot }}
                            </td>
                            @foreach(range(1, 5) as $day)
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 border">
                                    <div class="text-center">
                                        <div class="font-medium">Mathematics</div>
                                        <div class="text-xs text-gray-400">Room 101</div>
                                        <div class="text-xs text-gray-400">Mr. Smith</div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
        <h3 class="font-medium text-blue-800">Legend</h3>
        <div class="flex flex-wrap gap-4 mt-2">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-green-100 border border-green-300 mr-2"></div>
                <span class="text-sm text-gray-700">Regular Class</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-yellow-100 border border-yellow-300 mr-2"></div>
                <span class="text-sm text-gray-700">Lab Session</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-red-100 border border-red-300 mr-2"></div>
                <span class="text-sm text-gray-700">Conflict</span>
            </div>
        </div>
    </div>
</div>
@endsection
