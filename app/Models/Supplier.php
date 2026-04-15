<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'phone',
        'is_deleted',
        'is_locked',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_id');
    }
}