<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetDepreciation extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'depreciation_date',
        'period_year',
        'period_month',
        'amount',
        'journal_entry_id',
        'notes',
    ];

    protected $casts = [
        'fixed_asset_id' => 'integer',
        'journal_entry_id' => 'integer',
        'period_year' => 'integer',
        'period_month' => 'integer',
        'depreciation_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}