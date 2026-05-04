<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentVoucher extends Model
{
    protected $fillable = [
        'voucher_number',
        'voucher_date',
        'branch_id',
        'financial_account_id',
        'payment_method_id',
        'beneficiary_type',
        'beneficiary_id',
        'expense_category_id',
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
        'beneficiary_id' => 'integer',
        'expense_category_id' => 'integer',
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

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
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

    public function getBeneficiaryLabelAttribute(): string
    {
        return match ($this->beneficiary_type) {
            'supplier' => 'مورد',
            'customer' => 'عميل',
            'employee' => 'موظف',
            'salary' => 'راتب',
            'partner' => 'شريك',
            'expense' => 'مصروف عام',
            default => '-',
        };
    }
}