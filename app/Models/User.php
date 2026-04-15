<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'username',
        'password',
        'phone',
        'salary',
        'email',
        'role_id',
        'is_user',
        'is_deleted',
        'is_locked',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'is_user' => 'boolean',
        'is_deleted' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'user_id');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'user_id');
    }

    public function customerPayments()
    {
        return $this->hasMany(CustomerPayment::class, 'user_id');
    }

    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class, 'user_id');
    }

    public function returnInvoices()
    {
        return $this->hasMany(ReturnInvoice::class, 'user_id');
    }
}