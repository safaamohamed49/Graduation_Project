<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Role;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\PaymentMethod;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        =========================================
        1. طرق الدفع
        =========================================
        */
        $paymentMethods = [
            1 => 'كاش',
            2 => 'بطاقة',
            3 => 'شيك',
            4 => 'تحويل بنكي',
        ];

        foreach ($paymentMethods as $id => $name) {
            PaymentMethod::updateOrCreate(
                ['id' => $id],
                ['name' => $name]
            );
        }

        /*
        =========================================
        2. العميل النقدي
        =========================================
        */
        Customer::updateOrCreate(
            ['name' => 'عميل نقدي'],
            [
                'phone' => '',
                'notes' => null,
                'is_deleted' => false,
                'is_locked' => true,
            ]
        );

        /*
        =========================================
        3. مورد المصروفات العامة
        =========================================
        */
        Supplier::updateOrCreate(
            ['name' => 'مصروفات عامة'],
            [
                'phone' => '',
                'is_deleted' => false,
                'is_locked' => true,
            ]
        );

        /*
        =========================================
        4. الأدوار (Roles)
        =========================================
        */
        $roles = [
            [
                'name' => 'Admin',
                'permissions' => [
                    'canManageUsers' => true,
                    'canAddSalesInvoice' => true,
                    'canEditSalesInvoice' => true,
                    'canAddReceipt' => true,
                    'canEditReceipt' => true,
                    'canAddPurchaseInvoice' => true,
                    'canEditPurchaseInvoice' => true,
                    'canDeletePurchaseInvoice' => true,
                    'canAddSupplierReceipt' => true,
                    'canViewSuppliersInfo' => true,
                    'canViewCustomerLedger' => true,
                    'canProcessReturns' => true,
                    'canViewLowStock' => true,
                ],
            ],
            [
                'name' => 'Employee 1',
                'permissions' => [
                    'canManageUsers' => false,
                    'canAddSalesInvoice' => true,
                    'canEditSalesInvoice' => true,
                    'canAddReceipt' => true,
                    'canEditReceipt' => true,
                    'canAddPurchaseInvoice' => true,
                    'canEditPurchaseInvoice' => true,
                    'canDeletePurchaseInvoice' => true,
                    'canAddSupplierReceipt' => true,
                    'canViewSuppliersInfo' => true,
                    'canViewCustomerLedger' => true,
                    'canViewLowStock' => true,
                ],
            ],
            [
                'name' => 'Employee 2',
                'permissions' => [
                    'canManageUsers' => false,
                    'canAddSalesInvoice' => true,
                    'canEditSalesInvoice' => true,
                    'canAddReceipt' => true,
                    'canEditReceipt' => true,
                    'canAddPurchaseInvoice' => true,
                    'canEditPurchaseInvoice' => true,
                    'canDeletePurchaseInvoice' => false,
                    'canAddSupplierReceipt' => false,
                    'canViewSuppliersInfo' => false,
                    'canViewCustomerLedger' => false,
                    'canViewLowStock' => true,
                ],
            ],
            [
                'name' => 'Employee 3',
                'permissions' => [
                    'canManageUsers' => false,
                    'canAddSalesInvoice' => true,
                    'canEditSalesInvoice' => false,
                    'canAddReceipt' => true,
                    'canEditReceipt' => false,
                    'canAddPurchaseInvoice' => false,
                    'canEditPurchaseInvoice' => false,
                    'canDeletePurchaseInvoice' => false,
                    'canAddSupplierReceipt' => false,
                    'canViewSuppliersInfo' => false,
                    'canViewCustomerLedger' => false,
                    'canViewLowStock' => true,
                ],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['permissions' => $role['permissions']]
            );
        }

        /*
        =========================================
        5. المستخدم الأدمن
        =========================================
        */
        $adminRole = Role::where('name', 'Admin')->first();

        User::updateOrCreate(
            ['username' => 'hedaya@bennis'],
            [
                'name' => 'هدى',
                'password' => Hash::make('12345678'), // 🔥 مهم جدًا
                'phone' => null,
                'salary' => null,
                'email' => null,
                'role_id' => $adminRole?->id,
                'is_user' => true,
                'is_deleted' => false,
                'is_locked' => false,
            ]
        );
    }
}