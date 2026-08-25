<?php

namespace App\Services\Kiosk;

use App\Models\KioskProduct;
use App\Models\KioskSale;
use App\Models\KioskSaleItem;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KioskService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Create or update a Kiosk Product.
     */
    public function createProduct(array $data): KioskProduct
    {
        return KioskProduct::create($data);
    }

    /**
     * Record a Kiosk Sale transactionally with stock reduction and accounting integration.
     */
    public function recordSale(array $data): KioskSale
    {
        return DB::transaction(function () use ($data) {
            $schoolId = $data['school_id'];
            $items = $data['items'];
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $saleDate = $data['sale_date'] ?? now()->toDateString();
            $cashierId = $data['cashier_id'] ?? auth()->id() ?? 1;

            if (empty($items)) {
                throw new InvalidArgumentException('Kiosk sale must contain at least one item.');
            }

            // Calculate totals and validate stock
            $subtotal = 0.0;
            $itemsToCreate = [];

            foreach ($items as $itemData) {
                $productId = $itemData['kiosk_product_id'];
                $quantity = (int) ($itemData['quantity'] ?? 1);

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Quantity must be greater than zero.');
                }

                // Lock product for update to prevent race conditions on stock
                $product = KioskProduct::where('id', $productId)
                    ->where('school_id', $schoolId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $product->is_active) {
                    throw new InvalidArgumentException("Product [{$product->name}] is not active for sales.");
                }

                if (! $product->hasStock($quantity)) {
                    throw new InvalidArgumentException("Insufficient stock for product [{$product->name}]. Available: {$product->stock_quantity}, Requested: {$quantity}.");
                }

                $unitPrice = isset($itemData['unit_price']) ? (float) $itemData['unit_price'] : (float) $product->unit_price;
                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;

                // Decrement stock if stock tracking is enabled
                if ($product->track_stock) {
                    $product->decrement('stock_quantity', $quantity);
                }

                $itemsToCreate[] = [
                    'kiosk_product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $product->cost_price ?? 0.00,
                    'line_total' => $lineTotal,
                ];
            }

            $taxAmount = (float) ($data['tax_amount'] ?? 0.00);
            $grandTotal = $subtotal + $taxAmount;

            $count = KioskSale::where('school_id', $schoolId)->whereDate('created_at', now()->toDateString())->count() + 1;
            $receiptNumber = 'KS-' . now()->format('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $sale = KioskSale::create([
                'school_id' => $schoolId,
                'receipt_number' => $receiptNumber,
                'sale_date' => $saleDate,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'cashier_id' => $cashierId,
                'customer_name' => $data['customer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsToCreate as $item) {
                $item['kiosk_sale_id'] = $sale->id;
                KioskSaleItem::create($item);
            }

            // Post to Accounting
            $this->accountingService->postKioskSale($sale);

            return $sale->load(['items.product', 'cashier']);
        });
    }
}
