<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnInvoiceItem extends Model
{
    protected $fillable = [
        'return_invoice_id',
        'order_id',
        'product_id',
        'quantity',
        'refund_amount',
        'unit_refund_price',
        'notes',
    ];

    protected $casts = [
        'return_invoice_id' => 'integer',
        'order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'unit_refund_price' => 'decimal:2',
    ];

    public function returnInvoice(): BelongsTo { return $this->belongsTo(ReturnInvoice::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}