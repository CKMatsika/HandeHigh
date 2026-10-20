<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\GoodsReceivedVoucher;
use App\Models\JournalBatch;
use App\Models\ProcurementSupplierInvoice;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProcurementAccountingService
{
    public function postGoodsReceivedVoucher(GoodsReceivedVoucher $voucher): ?JournalBatch
    {
        $existing = JournalBatch::where('school_id', $voucher->school_id)
            ->where('source_type', 'goods_received_voucher')
            ->where('source_id', $voucher->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $voucher->loadMissing('items');
        $amount = (float) $voucher->items->sum('line_total');
        if ($amount <= 0) {
            return null;
        }

        $accounts = $this->procurementAccounts($voucher->school);

        return app(AccountingService::class)->createJournalBatch([
            'school_id' => $voucher->school_id,
            'transaction_date' => $voucher->received_date->toDateString(),
            'reference_number' => $voucher->voucher_number,
            'description' => 'Inventory received on '.$voucher->voucher_number,
            'source_type' => 'goods_received_voucher',
            'source_id' => $voucher->id,
            'status' => 'posted',
            'created_by' => $voucher->received_by,
            'entries' => [
                [
                    'account_id' => $accounts['inventory']->id,
                    'entry_type' => 'debit',
                    'amount' => $amount,
                    'memo' => 'Inventory received - '.$voucher->voucher_number,
                ],
                [
                    'account_id' => $accounts['received_not_invoiced']->id,
                    'entry_type' => 'credit',
                    'amount' => $amount,
                    'memo' => 'Goods received not invoiced - '.$voucher->voucher_number,
                ],
            ],
        ]);
    }

    public function postSupplierInvoice(Bill $bill, ProcurementSupplierInvoice $supplierInvoice): JournalBatch
    {
        return DB::transaction(function () use ($bill, $supplierInvoice) {
            $existing = JournalBatch::where('school_id', $bill->school_id)
                ->where('source_type', 'bill')
                ->where('source_id', $bill->id)
                ->first();
            if ($existing) {
                return $existing;
            }

            $accounts = $this->procurementAccounts($bill->school);
            $supplierInvoice->loadMissing('items');
            $receivedCostBasis = (float) $supplierInvoice->items->sum('stock_cost_basis');
            $invoiceAmount = (float) $supplierInvoice->total_amount;
            $variance = round($invoiceAmount - $receivedCostBasis, 2);
            $entries = [];

            if ($receivedCostBasis > 0) {
                $entries[] = [
                    'account_id' => $accounts['received_not_invoiced']->id,
                    'entry_type' => 'debit',
                    'amount' => $receivedCostBasis,
                    'memo' => 'Clear received-not-invoiced accrual - '.$supplierInvoice->invoice_number,
                ];
            }

            if (abs($variance) >= 0.01) {
                $entries[] = [
                    'account_id' => $accounts['price_variance']->id,
                    'entry_type' => $variance > 0 ? 'debit' : 'credit',
                    'amount' => abs($variance),
                    'memo' => 'Supplier invoice price variance - '.$supplierInvoice->invoice_number,
                ];
            }

            if ($invoiceAmount > 0) {
                $entries[] = [
                    'account_id' => $accounts['accounts_payable']->id,
                    'entry_type' => 'credit',
                    'amount' => $invoiceAmount,
                    'memo' => 'Supplier invoice - '.$supplierInvoice->invoice_number,
                ];
            }

            if ($entries === []) {
                throw new InvalidArgumentException('A zero-value supplier invoice cannot be posted.');
            }

            return app(AccountingService::class)->createJournalBatch([
                'school_id' => $bill->school_id,
                'transaction_date' => $supplierInvoice->invoice_date->toDateString(),
                'reference_number' => $supplierInvoice->invoice_number,
                'description' => 'Supplier invoice '.$supplierInvoice->invoice_number.' - '.$bill->vendor->name,
                'source_type' => 'bill',
                'source_id' => $bill->id,
                'status' => 'posted',
                'created_by' => $supplierInvoice->approved_by,
                'entries' => $entries,
            ]);
        });
    }

    public function procurementAccounts(School $school): array
    {
        app(AccountingService::class)->ensureChartOfAccountsExist($school);
        $codes = ['1505', '3100', '3103', '6590'];
        if (Account::where('school_id', $school->id)->whereIn('code', $codes)->count() !== count($codes)) {
            app(\Database\Seeders\ChartOfAccountsSeeder::class)->seedAccountsForSchool($school);
        }

        $accounts = Account::where('school_id', $school->id)
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');
        $expectedTypes = [
            '1505' => 'asset',
            '3100' => 'liability',
            '3103' => 'liability',
            '6590' => 'expense',
        ];

        foreach ($expectedTypes as $code => $type) {
            $account = $accounts->get($code);
            if (! $account || ! $account->is_active || ! $account->is_postable || $account->type !== $type) {
                throw new InvalidArgumentException("Procurement account {$code} must exist, be active, and be postable as a {$type} account.");
            }
        }

        return [
            'inventory' => $accounts->get('1505'),
            'accounts_payable' => $accounts->get('3100'),
            'received_not_invoiced' => $accounts->get('3103'),
            'price_variance' => $accounts->get('6590'),
        ];
    }
}
