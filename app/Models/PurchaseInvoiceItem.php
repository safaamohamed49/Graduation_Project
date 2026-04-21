<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'price',
        'cost_price',
        'notes',
    ];

    protected $casts = [
        'invoice_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class, 'invoice_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}