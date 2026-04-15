<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'phone',
        'notes',
        'is_deleted',
        'is_locked',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'customer_id');
    }

    public function customerPayments()
    {
        return $this->hasMany(CustomerPayment::class, 'customer_id');
    }

    public function returnInvoices()
    {
        return $this->hasMany(ReturnInvoice::class, 'customer_id');
    }
}