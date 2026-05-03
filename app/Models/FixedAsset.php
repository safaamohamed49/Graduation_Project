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
        'asset_account_id',
        'depreciation_account_id',
        'depreciation_expense_account_id',
        'journal_entry_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'branch_id' => 'integer',
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

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function depreciations(): HasMany { return $this->hasMany(FixedAssetDepreciation::class); }
}