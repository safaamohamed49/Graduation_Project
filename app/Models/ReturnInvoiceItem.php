<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnInvoice extends Model
{
    protected $table = 'return_invoices';

    public $timestamps = false;

    protected $fillable = [
        'return_number',
        'return_date',
        'customer_id',
        'total_refund_amount',
        'discount_amount',
        'user_id',
        'is_deleted',
        'deleted_by_user_id',
        'deleted_at',
    ];

    protected $casts = [
        'return_date' => 'datetime',
        'total_refund_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(ReturnInvoiceItem::class, 'return_invoice_id');
    }
}