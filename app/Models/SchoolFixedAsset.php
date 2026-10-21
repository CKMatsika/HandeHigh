<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolFixedAsset extends Model
{
    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'goods_received_voucher_item_id',
        'supplier_invoice_item_id',
        'inventory_warehouse_id',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'custodian_user_id',
        'created_by',
        'asset_number',
        'serial_number',
        'status',
        'is_capitalized',
        'purchase_cost',
        'capitalized_cost',
        'depreciation_method',
        'useful_life_years',
        'salvage_value',
        'depreciation_rate',
        'accumulated_depreciation',
        'book_value',
        'last_depreciation_date',
        'acquired_date',
        'location',
        'notes',
        'disposal_date',
        'disposal_type',
        'disposal_proceeds',
        'disposal_notes',
        'disposal_journal_batch_id',
        'disposed_by',
    ];

    protected $casts = [
        'is_capitalized' => 'boolean',
        'purchase_cost' => 'decimal:2',
        'capitalized_cost' => 'decimal:2',
        'useful_life_years' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'disposal_proceeds' => 'decimal:2',
        'acquired_date' => 'date',
        'last_depreciation_date' => 'date',
        'disposal_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function goodsReceivedItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedVoucherItem::class, 'goods_received_voucher_item_id');
    }

    public function supplierInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplierInvoiceItem::class, 'supplier_invoice_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function getWarehouseIdAttribute(): ?int
    {
        return $this->inventory_warehouse_id;
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function disposalJournalBatch(): BelongsTo
    {
        return $this->belongsTo(JournalBatch::class, 'disposal_journal_batch_id');
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disposedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SchoolFixedAssetAssignment::class)->orderBy('occurred_at');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(SchoolFixedAssetDepreciation::class)->latest('depreciation_date');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(SchoolFixedAssetMaintenance::class)->latest('scheduled_date');
    }

    public function getNetBookValueAttribute(): float
    {
        $cost = (float) $this->capitalized_cost;
        $acc = (float) $this->accumulated_depreciation;
        $salvage = (float) $this->salvage_value;

        return max($salvage, round($cost - $acc, 2));
    }

    public function getCanDepreciateAttribute(): bool
    {
        return $this->is_capitalized
            && ! in_array($this->status, ['disposed', 'written_off'])
            && $this->net_book_value > (float) $this->salvage_value;
    }
}
