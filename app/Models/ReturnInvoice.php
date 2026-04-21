<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnInvoice extends Model
{
    protected $fillable = [
        'return_number',
        'branch_id',
        'warehouse_id',
        'customer_id',
        'return_date',
        'total_refund_amount',
        'discount_amount',
        'journal_entry_id',
        'user_id',
        'deleted_by_user_id',
        'deleted_at',
        'is_deleted',
        'notes',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'customer_id' => 'integer',
        'journal_entry_id' => 'integer',
        'user_id' => 'integer',
        'deleted_by_user_id' => 'integer',
        'return_date' => 'datetime',
        'deleted_at' => 'datetime',
        'total_refund_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(ReturnInvoiceItem::class); }
}