<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'order_number',
        'customer_id',
        'order_date',
        'total_price',
        'total_cost',
        'total_profit',
        'discount_amount',
        'is_deleted',
        'user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'total_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}