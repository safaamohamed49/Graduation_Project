<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'phone',
        'email',
        'address',
        'salary',
        'account_id',
        'hire_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'salary' => 'decimal:2',
        'account_id' => 'integer',
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}