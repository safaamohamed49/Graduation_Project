<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $table = 'supplier_payments';

    public $timestamps = false;

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'amount_paid',
        'payment_date',
        'payment_method',
        'notes',
        'is_deleted',
        'user_id',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_date' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
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