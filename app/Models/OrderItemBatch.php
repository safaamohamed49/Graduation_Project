<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemBatch extends Model
{
    protected $fillable = [
        'order_item_id',
        'batch_id',
        'quantity_used',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'order_item_id' => 'integer',
        'batch_id' => 'integer',
        'quantity_used' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }
}