<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Models\IctTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class IctTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tenant']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || !$user->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin'])) {
            abort(403);
        }

        $query = IctTicket::where('school_id', $school->id)->with(['requester', 'technician']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->latest()->paginate(15);
        $technicians = User::where('school_id', $school->id)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['ict-coordinator', 'super-admin', 'school-admin']);
            })
            ->get();

        return view('ict.tickets.index', compact('tickets', 'technicians'));
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        return view('ict.tickets.create', compact('school', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'category' => ['required', 'string', 'in:login_account,hardware_lab,printer_scanner,network_wifi,erp_support,other'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);

        $ticketCount = IctTicket::where('school_id', $school->id)->count() + 1;
        $ticketNumber = 'ICT-' . date('Y') . '-' . str_pad($ticketCount, 4, '0', STR_PAD_LEFT);

        // Auto-assign to ICT Coordinator if available
        $ictLead = User::where('school_id', $school->id)
            ->whereHas('roles', function($q) {
                $q->where('name', 'ict-coordinator');
            })
            ->first();

        $ticket = IctTicket::create([
            'school_id' => $school->id,
            'ticket_number' => $ticketNumber,
            'user_id' => $user->id,
            'assigned_to' => $ictLead?->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        return redirect()->route('ict.tickets.my-tickets')
            ->with('status', "Support ticket {$ticket->ticket_number} submitted to ICT Coordinator.");
    }

    public function myTickets()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $tickets = IctTicket::where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->with('technician')
            ->latest()
            ->paginate(15);

        return view('ict.tickets.my-tickets', compact('tickets'));
    }

    public function show(IctTicket $ticket)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $ticket->school_id !== $school->id) {
            abort(403);
        }

        // Must be the requester or an ICT coordinator / admin
        if ($ticket->user_id !== $user->id && !$user->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin'])) {
            abort(403);
        }

        $ticket->load(['requester', 'technician']);
        $technicians = User::where('school_id', $school->id)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['ict-coordinator', 'super-admin', 'school-admin']);
            })
            ->get();

        return view('ict.tickets.show', compact('ticket', 'technicians'));
    }

    public function updateStatus(Request $request, IctTicket $ticket)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $ticket->school_id !== $school->id || !$user->hasAnyRole(['ict-coordinator', 'super-admin', 'school-admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $resolvedAt = in_array($validated['status'], ['resolved', 'closed']) ? ($ticket->resolved_at ?? now()) : null;

        $ticket->update([
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? $ticket->assigned_to,
            'resolution_notes' => $validated['resolution_notes'] ?? $ticket->resolution_notes,
            'resolved_at' => $resolvedAt,
        ]);

        return back()->with('status', "Ticket {$ticket->ticket_number} updated to " . ucfirst(str_replace('_', ' ', $validated['status'])) . ".");
    }
}
