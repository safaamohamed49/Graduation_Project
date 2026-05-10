<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptVoucher extends Model
{
    protected $fillable = [
        'voucher_number',
        'voucher_date',
        'branch_id',
        'financial_account_id',
        'payment_method_id',
        'received_from_type',
        'received_from_id',
        'partner_transaction_type',
        'account_id',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'journal_entry_id',
        'created_by_user_id',
        'status',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'financial_account_id' => 'integer',
        'payment_method_id' => 'integer',
        'received_from_id' => 'integer',
        'account_id' => 'integer',
        'reference_id' => 'integer',
        'journal_entry_id' => 'integer',
        'created_by_user_id' => 'integer',
        'voucher_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getReceivedFromLabelAttribute(): string
    {
        return match ($this->received_from_type) {
            'customer' => 'عميل',
            'supplier' => 'مورد',
            'employee' => 'موظف',
            'partner' => 'شريك',
            'other' => 'أخرى',
            default => '-',
        };
    }

    public function getPartnerTransactionTypeLabelAttribute(): string
    {
        return match ($this->partner_transaction_type) {
            'capital' => 'رأس مال شريك',
            'current' => 'إيداع جاري شريك',
            default => '-',
        };
    }
}