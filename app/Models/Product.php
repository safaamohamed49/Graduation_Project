<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'product_code',
        'stock',
        'current_price',
        'last_purchase_price',
        'is_deleted',
        'category_id',
    ];

    protected $casts = [
        'stock' => 'integer',
        'current_price' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function priceHistory()
    {
        return $this->hasMany(ProductPriceHistory::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function purchaseInvoiceItems()
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'product_id');
    }

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class, 'product_id');
    }

    public function returnInvoiceItems()
    {
        return $this->hasMany(ReturnInvoiceItem::class, 'product_id');
    }
}