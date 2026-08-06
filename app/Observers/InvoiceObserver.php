<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    /**
     * Handle the Invoice "saved" event.
     */
    public function saved(Invoice $invoice): void
    {
        // Only post if the invoice has a total amount and has not been cancelled/draft
        if ($invoice->total_amount > 0 && $invoice->status !== 'cancelled') {
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
