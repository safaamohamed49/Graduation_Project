<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['code' => 'admin'],
            [
                'name' => 'مدير النظام',
                'permissions' => ['*'],
                'is_active' => true,
                'notes' => 'صلاحيات كاملة على النظام.',
            ]
        );

        Role::updateOrCreate(
            ['code' => 'manager'],
            [
                'name' => 'مدير فرع',
                'permissions' => [
                    'dashboard.view',

                    'branches.view',
                    'branches.create',
                    'branches.update',

                    'categories.view',
                    'categories.create',
                    'categories.update',

                    'products.view',
                    'products.create',
                    'products.update',

                    'warehouses.view',
                    'warehouses.transfer',

                    'customers.view',
                    'customers.create',
                    'customers.update',

                    'suppliers.view',
                    'suppliers.create',
                    'suppliers.update',

                    'purchase_invoices.view',
                    'purchase_invoices.create',
                    'purchase_invoices.update',

                    'orders.view',
                    'orders.create',
                    'orders.update',

                    'returns.view',
                    'returns.create',

                    'payments.view',
                    'payments.create',

                    'receipts.view',
                    'receipts.create',

                    'reports.view',
                    'financial_accounts.view',
                    'financial_accounts.create',
                    'financial_accounts.update',

                    'fixed_assets.view',
                   'fixed_assets.create',
                   'fixed_assets.update',
                   'fixed_assets.depreciate',
                   'fixed_assets.report',
                ],
                'is_active' => true,
                'notes' => 'صلاحيات تشغيل وإدارة فرع بدون حذف حساس.',
            ]
        );

        Role::updateOrCreate(
            ['code' => 'employee'],
            [
                'name' => 'موظف',
                'permissions' => [
                    'dashboard.view',

                    'branches.view',

                    'categories.view',
                    'products.view',
                    'warehouses.view',

                    'customers.view',
                    'customers.create',

                    'orders.view',
                    'orders.create',

                    'receipts.view',
                    'receipts.create',
                ],
                'is_active' => true,
                'notes' => 'صلاحيات موظف يومية محدودة.',
            ]
        );
    }
}