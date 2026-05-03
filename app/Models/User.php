<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'branch_id',
        'role_id',
        'name',
        'username',
        'password',
        'phone',
        'email',
        'address',
        'salary',
        'image_path',
        'extra_permissions',
        'denied_permissions',
        'is_active',
        'is_deleted',
        'is_locked',
        'last_login_at',
        'notes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'role_id' => 'integer',
        'salary' => 'decimal:2',
        'extra_permissions' => 'array',
        'denied_permissions' => 'array',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'is_locked' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function managedBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'manager_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'user_id');
    }

    public function returnInvoices(): HasMany
    {
        return $this->hasMany(ReturnInvoice::class, 'user_id');
    }

    public function paymentVouchers(): HasMany
    {
        return $this->hasMany(PaymentVoucher::class, 'created_by_user_id');
    }

    public function receiptVouchers(): HasMany
    {
        return $this->hasMany(ReceiptVoucher::class, 'created_by_user_id');
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'user_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'created_by_user_id');
    }

    public function hasPermission(string $permission): bool
    {
        $rolePermissions = $this->role?->permissions ?? [];
        $extraPermissions = $this->extra_permissions ?? [];
        $deniedPermissions = $this->denied_permissions ?? [];

        if (in_array($permission, $deniedPermissions, true)) {
            return false;
        }

        if (in_array('*', $rolePermissions, true) || in_array('*', $extraPermissions, true)) {
            return true;
        }

        return in_array($permission, $rolePermissions, true)
            || in_array($permission, $extraPermissions, true);
    }

    public function permissionsList(): array
    {
        $rolePermissions = $this->role?->permissions ?? [];
        $extraPermissions = $this->extra_permissions ?? [];
        $deniedPermissions = $this->denied_permissions ?? [];

        if (in_array('*', $rolePermissions, true) || in_array('*', $extraPermissions, true)) {
            return ['*'];
        }

        return array_values(array_diff(
            array_unique(array_merge($rolePermissions, $extraPermissions)),
            $deniedPermissions
        ));
    }
}