<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'capital_amount',
        'ownership_percentage',
        'capital_account_id',
        'current_account_id',
        'start_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'capital_amount' => 'decimal:2',
        'ownership_percentage' => 'decimal:2',
        'capital_account_id' => 'integer',
        'current_account_id' => 'integer',
        'start_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function paymentVouchers(): HasMany
    {
        return $this->hasMany(PaymentVoucher::class);
    }

    public function receiptVouchers(): HasMany
    {
        return $this->hasMany(ReceiptVoucher::class);
    }
}