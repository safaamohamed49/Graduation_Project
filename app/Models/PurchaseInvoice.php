<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'invoice_date',
        'subtotal',
        'discount_amount',
        'total_expenses',
        'total_price',
        'total_base_price',
        'paid_amount',
        'payment_status',
        'journal_entry_id',
        'user_id',
        'updated_by_user_id',
        'is_deleted',
        'notes',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'supplier_id' => 'integer',
        'journal_entry_id' => 'integer',
        'user_id' => 'integer',
        'updated_by_user_id' => 'integer',
        'invoice_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_base_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'invoice_id');
    }

    public function paymentVouchers(): HasMany
    {
        return $this->hasMany(PaymentVoucher::class, 'reference_id')
            ->where('reference_type', 'purchase_invoice');
    }
}