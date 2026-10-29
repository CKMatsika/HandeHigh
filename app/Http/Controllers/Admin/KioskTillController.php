<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KioskTillSession;
use App\Services\Kiosk\KioskTillService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KioskTillController extends Controller
{
    public function __construct(private KioskTillService $tills) {}

    public function dashboard()
    {
        $user = Auth::user();
        $schoolId = $user?->school_id;
        abort_unless($schoolId, 403);
        $isManager = $user->can('kiosk.backoffice.view');
        $query = KioskTillSession::where('school_id', $schoolId)->with(['cashier', 'openedBy']);
        $sessions = ($isManager ? $query : $query->where('cashier_id', $user->id))->latest('opened_at')->limit(30)->get();
        $openTill = KioskTillSession::where('school_id', $schoolId)->where('cashier_id', $user->id)->where('status', 'open')->first();

        return view('admin.kiosk.cashier-dashboard', compact('sessions', 'openTill', 'isManager'));
    }

    public function open(Request $request)
    {
        $user = Auth::user();
        abort_unless($user?->school_id, 403);
        $data = $request->validate(['till_label' => ['required', 'string', 'max:80'], 'opening_float' => ['required', 'numeric', 'min:0'], 'opening_notes' => ['nullable', 'string', 'max:1000']]);
        $this->tills->open($user->school_id, $user->id, $user->id, $data['till_label'], (float) $data['opening_float'], $data['opening_notes'] ?? null);
        return redirect()->route('admin.kiosk.cashier')->with('success', 'Till opened. Record the opening float before starting sales.');
    }

    public function close(Request $request, KioskTillSession $session)
    {
        $user = Auth::user();
        abort_unless($user && $session->school_id === $user->school_id, 404);
        abort_unless($session->cashier_id === $user->id || $user->can('kiosk.backoffice.view'), 403);
        $data = $request->validate(['counted_cash' => ['required', 'numeric', 'min:0'], 'closing_notes' => ['nullable', 'string', 'max:1000']]);
        $closed = $this->tills->close($session, $user->id, (float) $data['counted_cash'], $data['closing_notes'] ?? null);
        return redirect()->route('admin.kiosk.z-reading', $closed)->with('success', 'Till closed and Z reading generated.');
    }

    public function reading(KioskTillSession $session)
    {
        $user = Auth::user();
        abort_unless($user && $session->school_id === $user->school_id, 404);
        abort_unless($session->cashier_id === $user->id || $user->can('kiosk.z-readings.view'), 403);
        abort_unless($session->status === 'closed', 404);
        $session->load(['cashier', 'openedBy', 'closedBy', 'sales.items.product']);
        return view('admin.kiosk.z-reading', compact('session'));
    }
}
