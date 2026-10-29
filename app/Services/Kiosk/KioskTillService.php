<?php

namespace App\Services\Kiosk;

use App\Models\KioskSale;
use App\Models\KioskTillSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KioskTillService
{
    public function open(int $schoolId, int $cashierId, int $openedBy, string $label, float $float, ?string $notes = null): KioskTillSession
    {
        return DB::transaction(function () use ($schoolId, $cashierId, $openedBy, $label, $float, $notes) {
            $alreadyOpen = KioskTillSession::where('school_id', $schoolId)->where('cashier_id', $cashierId)->where('status', 'open')->lockForUpdate()->exists();
            if ($alreadyOpen) {
                throw ValidationException::withMessages(['till' => 'This cashier already has an open till. Close it before opening another.']);
            }

            return KioskTillSession::create([
                'school_id' => $schoolId, 'cashier_id' => $cashierId, 'opened_by' => $openedBy,
                'till_label' => $label, 'status' => 'open', 'active_key' => 'open',
                'opening_float' => $float, 'opened_at' => now(), 'opening_notes' => $notes,
            ]);
        });
    }

    public function close(KioskTillSession $session, int $closedBy, float $countedCash, ?string $notes = null): KioskTillSession
    {
        return DB::transaction(function () use ($session, $closedBy, $countedCash, $notes) {
            $session = KioskTillSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($session->status !== 'open') {
                throw ValidationException::withMessages(['till' => 'This till has already been closed.']);
            }

            $totals = KioskSale::where('school_id', $session->school_id)->where('kiosk_till_session_id', $session->id)
                ->selectRaw('COUNT(*) as sale_count, COALESCE(SUM(grand_total), 0) as total')
                ->selectRaw("COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN grand_total ELSE 0 END), 0) as cash")
                ->selectRaw("COALESCE(SUM(CASE WHEN payment_method = 'mobile_money' THEN grand_total ELSE 0 END), 0) as mobile")
                ->selectRaw("COALESCE(SUM(CASE WHEN payment_method = 'bank_transfer' THEN grand_total ELSE 0 END), 0) as bank")
                ->selectRaw("COALESCE(SUM(CASE WHEN payment_method = 'card' THEN grand_total ELSE 0 END), 0) as card")
                ->first();

            $expectedCash = (float) $session->opening_float + (float) $totals->cash;
            $session->fill([
                'closed_by' => $closedBy, 'status' => 'closed', 'active_key' => null, 'closed_at' => now(),
                'cash_sales' => $totals->cash, 'mobile_money_sales' => $totals->mobile,
                'bank_transfer_sales' => $totals->bank, 'card_sales' => $totals->card,
                'total_sales' => $totals->total, 'sales_count' => $totals->sale_count,
                'expected_cash' => $expectedCash, 'counted_cash' => $countedCash,
                'cash_variance' => round($countedCash - $expectedCash, 2), 'closing_notes' => $notes,
                'z_reading_number' => 'Z-' . now()->format('Ymd') . '-' . str_pad((string) $session->id, 6, '0', STR_PAD_LEFT),
            ])->save();

            return $session->fresh(['cashier', 'closedBy']);
        });
    }
}
