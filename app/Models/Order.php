<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'branch_id',
        'customer_id',
        'order_date',
        'subtotal',
        'discount_amount',
        'total_price',
        'total_cost',
        'total_profit',
        'status',
        'payment_status',
        'journal_entry_id',
        'user_id',
        'updated_by_user_id',
        'is_deleted',
        'notes',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'customer_id' => 'integer',
        'journal_entry_id' => 'integer',
        'user_id' => 'integer',
        'updated_by_user_id' => 'integer',
        'order_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
}