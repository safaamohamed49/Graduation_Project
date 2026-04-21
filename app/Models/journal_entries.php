<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number',
        'entry_date',
        'branch_id',
        'description',
        'source_type',
        'source_id',
        'created_by_user_id',
        'status',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'source_id' => 'integer',
        'created_by_user_id' => 'integer',
        'entry_date' => 'datetime',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function lines(): HasMany { return $this->hasMany(JournalEntryLine::class); }
}