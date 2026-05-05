<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'asset_code',
        'purchase_date',
        'purchase_value',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'financial_account_id',
        'asset_account_id',
        'depreciation_account_id',
        'depreciation_expense_account_id',
        'journal_entry_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'financial_account_id' => 'integer',
        'asset_account_id' => 'integer',
        'depreciation_account_id' => 'integer',
        'depreciation_expense_account_id' => 'integer',
        'journal_entry_id' => 'integer',
        'purchase_date' => 'date',
        'purchase_value' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'useful_life_years' => 'integer',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciation::class);
    }

    public function getDepreciableValueAttribute(): float
    {
        return max(0, (float) $this->purchase_value - (float) $this->salvage_value);
    }

    public function getTotalDepreciationAttribute(): float
    {
        if ($this->relationLoaded('depreciations')) {
            return (float) $this->depreciations->sum('amount');
        }

        return (float) $this->depreciations()->sum('amount');
    }

    public function getBookValueAttribute(): float
    {
        return max(0, (float) $this->purchase_value - (float) $this->total_depreciation);
    }

    public function getMonthlyDepreciationAttribute(): float
    {
        if ((int) $this->useful_life_years <= 0) {
            return 0;
        }

        return round($this->depreciable_value / ((int) $this->useful_life_years * 12), 2);
    }

    public function getYearlyDepreciationAttribute(): float
    {
        if ((int) $this->useful_life_years <= 0) {
            return 0;
        }

        return round($this->depreciable_value / (int) $this->useful_life_years, 2);
    }
}