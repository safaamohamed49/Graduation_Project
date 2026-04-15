<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $table = 'receipts';

    public $timestamps = false;

    protected $fillable = [
        'receipt_number',
        'customer_id',
        'payment_method',
        'amount_paid',
        'receipt_date',
        'receipt_desc',
        'is_deleted',
        'user_id',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'receipt_date' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}