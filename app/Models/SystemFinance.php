<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemFinance extends Model
{
    protected $table = 'system_finance';

    const CREATED_AT = null;
    const UPDATED_AT = 'last_update';

    protected $fillable = [
        'account_name',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'last_update' => 'datetime',
    ];
}