<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'purchase_invoice_id',
        'quantity',
        'remaining_quantity',
        'base_price',
        'purchase_price',
        'entry_date',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'purchase_invoice_id' => 'integer',
        'quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'base_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'entry_date' => 'datetime',
        'expiry_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function orderItemBatches(): HasMany
    {
        return $this->hasMany(OrderItemBatch::class, 'batch_id');
    }
}