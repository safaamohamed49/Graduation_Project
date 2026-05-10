<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'phone',
        'email',
        'address',
        'notes',
        'account_id',
        'is_active',
        'is_deleted',
        'is_locked',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'account_id' => 'integer',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }
}