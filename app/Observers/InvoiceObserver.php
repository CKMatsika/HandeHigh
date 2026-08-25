<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    /**
     * Handle the Invoice "creating" event to snapshot school identity at issuance.
     */
    public function creating(Invoice $invoice): void
    {
        if (empty($invoice->school_snapshot) && $invoice->school_id) {
            $school = $invoice->school ?: \App\Models\School::find($invoice->school_id);
            if ($school) {
                $invoice->school_snapshot = app(\App\Services\Branding\SchoolDocumentBrandingService::class)->generateSnapshot($school);
            }
        }
    }

    /**
     * Handle the Invoice "saved" event.
     */
    public function saved(Invoice $invoice): void
    {
        // Only post if the invoice has a total amount, has not been cancelled/draft, and has items
        if ($invoice->total_amount > 0 && $invoice->status !== 'cancelled' && $invoice->items()->exists()) {
            try {
                // Resolve from container
                $accountingService = app(AccountingService::class);
                $accountingService->postInvoice($invoice);
            } catch (\Exception $e) {
                Log::error('Failed to auto-post invoice ' . $invoice->number . ' to accounting ledger: ' . $e->getMessage());
            }
        }
    }
}
