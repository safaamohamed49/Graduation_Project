<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'product_code',
        'barcode',
        'unit_name',
        'image_path',
        'current_price',
        'last_purchase_price',
        'minimum_stock',
        'is_service',
        'is_active',
        'is_deleted',
        'notes',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'current_price' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'is_service' => 'boolean',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function returnInvoiceItems(): HasMany
    {
        return $this->hasMany(ReturnInvoiceItem::class);
    }

    public function stockTransferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}