<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Models\IctLabItem;
use App\Models\IctTicket;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IctCoordinatorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tenant']);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || !$user->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin'])) {
            abort(403, 'Unauthorized access to ICT Coordinator portal.');
        }

        // 1. Support ticket statistics
        $openTicketsCount = IctTicket::where('school_id', $school->id)->where('status', 'open')->count();
        $inProgressTicketsCount = IctTicket::where('school_id', $school->id)->where('status', 'in_progress')->count();
        $resolvedTicketsCount = IctTicket::where('school_id', $school->id)->where('status', 'resolved')->count();
        $urgentTicketsCount = IctTicket::where('school_id', $school->id)->whereIn('status', ['open', 'in_progress'])->where('priority', 'urgent')->count();
        
        $recentTickets = IctTicket::where('school_id', $school->id)
            ->with(['requester', 'technician'])
            ->latest()
            ->limit(6)
            ->get();

        // 2. Computer Lab & ICT Equipment statistics
        $totalDevices = IctLabItem::where('school_id', $school->id)->count();
        $workingDevices = IctLabItem::where('school_id', $school->id)->where('condition_status', 'working')->count();
        $faultyDevices = IctLabItem::where('school_id', $school->id)->whereIn('condition_status', ['faulty', 'under_repair'])->count();
        $attentionDevices = IctLabItem::where('school_id', $school->id)->where('condition_status', 'needs_attention')->count();
        $readinessRate = $totalDevices > 0 ? round(($workingDevices / $totalDevices) * 100) : 100;

        $devices = IctLabItem::where('school_id', $school->id)->latest()->limit(8)->get();

        // 3. ERP Implementation Project progress
        $erpProject = Project::where('school_id', $school->id)
            ->where(function($q) {
                $q->where('code', 'PRJ-ERP-2026')
                  ->orWhere('project_type', 'technology');
            })
            ->with(['tasks.assignee'])
            ->first();

        $erpTasksCount = $erpProject?->tasks_count ?? 0;
        $erpCompletedCount = $erpProject?->completed_tasks_count ?? 0;
        $erpProgressPercentage = $erpProject?->progress_percentage ?? 0;
        $recentErpTasks = $erpProject ? $erpProject->tasks()->latest()->limit(5)->get() : collect();

        return view('ict.dashboard', compact(
            'school',
            'user',
            'openTicketsCount',
            'inProgressTicketsCount',
            'resolvedTicketsCount',
            'urgentTicketsCount',
            'recentTickets',
            'totalDevices',
            'workingDevices',
            'faultyDevices',
            'attentionDevices',
            'readinessRate',
            'devices',
            'erpProject',
            'erpTasksCount',
            'erpCompletedCount',
            'erpProgressPercentage',
            'recentErpTasks'
        ));
    }

    public function devices(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || !$user->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin'])) {
            abort(403);
        }

        $query = IctLabItem::where('school_id', $school->id)->with('inspector');

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }
        if ($request->filled('condition')) {
            $query->where('condition_status', $request->condition);
        }

        $devices = $query->orderBy('location')->orderBy('device_name')->paginate(15);
        $locations = IctLabItem::where('school_id', $school->id)->distinct()->pluck('location')->filter()->values();

        return view('ict.devices.index', compact('devices', 'locations'));
    }

    public function storeDevice(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || !$user->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'device_code' => ['nullable', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:100'],
            'condition_status' => ['required', 'in:working,needs_attention,faulty,under_repair'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        IctLabItem::create([
            'school_id' => $school->id,
            'device_name' => $validated['device_name'],
            'device_code' => $validated['device_code'] ?? null,
            'location' => $validated['location'],
            'condition_status' => $validated['condition_status'],
            'last_checked_at' => now(),
            'last_checked_by' => $user->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('ict.devices.index')->with('status', 'Device added to ICT inventory successfully.');
    }

    public function updateDeviceCondition(Request $request, IctLabItem $device)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $device->school_id !== $school->id || !$user->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'condition_status' => ['required', 'in:working,needs_attention,faulty,under_repair'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $device->update([
            'condition_status' => $validated['condition_status'],
            'last_checked_at' => now(),
            'last_checked_by' => $user->id,
            'notes' => $validated['notes'] ?? $device->notes,
        ]);

        return back()->with('status', "Device {$device->device_name} condition updated to {$device->condition_label}.");
    }
}
