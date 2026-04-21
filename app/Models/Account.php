<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'type',
        'nature',
        'level',
        'is_group',
        'is_active',
        'is_system',
        'notes',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'level' => 'integer',
        'is_group' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}