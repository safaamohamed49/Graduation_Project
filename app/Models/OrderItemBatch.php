<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    protected $table = 'inventory_batches';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'invoice_id',
        'quantity',
        'base_price',
        'purchase_price',
        'remaining_quantity',
        'entry_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'base_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'remaining_quantity' => 'integer',
        'entry_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }
}