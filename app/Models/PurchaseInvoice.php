<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $table = 'purchase_invoices';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'date',
        'total_price',
        'total_base_price',
        'total_expenses',
        'discount_amount',
        'is_deleted',
        'user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'total_price' => 'decimal:2',
        'total_base_price' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
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
        return $this->hasMany(PurchaseInvoiceItem::class, 'invoice_id');
    }
}