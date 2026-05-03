<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Role;
use App\Models\User;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Partner;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceHistory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1) الأدوار
        |--------------------------------------------------------------------------
        */
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('code', 'admin')->first();

        /*
        |--------------------------------------------------------------------------
        | Helper لإنشاء حساب محاسبي
        |--------------------------------------------------------------------------
        */
        $createAccount = function (
            ?int $parentId,
            string $code,
            string $name,
            string $type,
            string $nature,
            int $level = 1,
            bool $isGroup = false,
            bool $isSystem = false,
            ?string $notes = null
        ) {
            return Account::updateOrCreate(
                ['code' => $code],
                [
                    'parent_id' => $parentId,
                    'name' => $name,
                    'type' => $type,
                    'nature' => $nature,
                    'level' => $level,
                    'is_group' => $isGroup,
                    'is_active' => true,
                    'is_system' => $isSystem,
                    'notes' => $notes,
                ]
            );
        };

        /*
        |--------------------------------------------------------------------------
        | 2) الفرع الرئيسي
        |--------------------------------------------------------------------------
        */
        $mainBranch = Branch::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'الفرع الرئيسي',
                'address' => 'الفرع الرئيسي',
                'phone' => null,
                'manager_user_id' => null,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3) شجرة الحسابات الأساسية
        |--------------------------------------------------------------------------
        */
        $assets = $createAccount(null, '1000', 'الأصول', 'asset', 'debit', 1, true, true);
        $currentAssets = $createAccount($assets->id, '1100', 'الأصول المتداولة', 'asset', 'debit', 2, true, true);
        $cashAccount = $createAccount($currentAssets->id, '1110', 'الصندوق', 'asset', 'debit', 3, false, true);
        $bankAccount = $createAccount($currentAssets->id, '1120', 'البنك', 'asset', 'debit', 3, false, true);
        $customersParent = $createAccount($currentAssets->id, '1130', 'العملاء', 'asset', 'debit', 3, true, true);
        $inventoryAccount = $createAccount($currentAssets->id, '1140', 'المخزون', 'asset', 'debit', 3, false, true);

        $fixedAssetsParent = $createAccount($assets->id, '1200', 'الأصول الثابتة', 'asset', 'debit', 2, true, true);
        $equipmentAccount = $createAccount($fixedAssetsParent->id, '1210', 'معدات وأصول ثابتة', 'asset', 'debit', 3, false, true);
        $accDepAccount = $createAccount($fixedAssetsParent->id, '1220', 'مجمع الإهلاك', 'asset', 'credit', 3, false, true);

        $liabilities = $createAccount(null, '2000', 'الخصوم', 'liability', 'credit', 1, true, true);
        $suppliersParent = $createAccount($liabilities->id, '2100', 'الموردون', 'liability', 'credit', 2, true, true);

        $equity = $createAccount(null, '3000', 'حقوق الملكية', 'equity', 'credit', 1, true, true);
        $partnersCapitalParent = $createAccount($equity->id, '3100', 'رؤوس أموال الشركاء', 'equity', 'credit', 2, true, true);
        $partnersCurrentParent = $createAccount($equity->id, '3200', 'جاري الشركاء', 'equity', 'debit', 2, true, true);

        $revenues = $createAccount(null, '4000', 'الإيرادات', 'revenue', 'credit', 1, true, true);
        $salesRevenue = $createAccount($revenues->id, '4100', 'إيرادات المبيعات', 'revenue', 'credit', 2, false, true);

        $expenses = $createAccount(null, '5000', 'المصروفات', 'expense', 'debit', 1, true, true);
        $salaryExpense = $createAccount($expenses->id, '5100', 'مصروف الرواتب', 'expense', 'debit', 2, false, true);
        $rentExpense = $createAccount($expenses->id, '5110', 'مصروف الإيجار', 'expense', 'debit', 2, false, true);
        $electricityExpense = $createAccount($expenses->id, '5120', 'مصروف الكهرباء', 'expense', 'debit', 2, false, true);
        $waterExpense = $createAccount($expenses->id, '5130', 'مصروف المياه', 'expense', 'debit', 2, false, true);
        $internetExpense = $createAccount($expenses->id, '5140', 'مصروف الإنترنت', 'expense', 'debit', 2, false, true);
        $maintenanceExpense = $createAccount($expenses->id, '5150', 'مصروف الصيانة', 'expense', 'debit', 2, false, true);
        $officeExpense = $createAccount($expenses->id, '5160', 'مصروف أدوات مكتبية', 'expense', 'debit', 2, false, true);
        $fuelExpense = $createAccount($expenses->id, '5170', 'مصروف الوقود', 'expense', 'debit', 2, false, true);
        $depreciationExpense = $createAccount($expenses->id, '5180', 'مصروف الإهلاك', 'expense', 'debit', 2, false, true);

        /*
        |--------------------------------------------------------------------------
        | 4) الشركاء + حساباتهم
        |--------------------------------------------------------------------------
        */
        $partner1Capital = $createAccount($partnersCapitalParent->id, '3110', 'رأس مال الشريك 1', 'equity', 'credit', 3, false, true);
        $partner1Current = $createAccount($partnersCurrentParent->id, '3210', 'جاري الشريك 1', 'equity', 'debit', 3, false, true);

        $partner2Capital = $createAccount($partnersCapitalParent->id, '3120', 'رأس مال الشريك 2', 'equity', 'credit', 3, false, true);
        $partner2Current = $createAccount($partnersCurrentParent->id, '3220', 'جاري الشريك 2', 'equity', 'debit', 3, false, true);

        Partner::updateOrCreate(
            ['name' => 'الشريك الأول'],
            [
                'phone' => null,
                'email' => null,
                'address' => null,
                'capital_amount' => 50000,
                'ownership_percentage' => 50,
                'capital_account_id' => $partner1Capital->id,
                'current_account_id' => $partner1Current->id,
                'start_date' => now()->toDateString(),
                'is_active' => true,
                'notes' => null,
            ]
        );

        Partner::updateOrCreate(
            ['name' => 'الشريك الثاني'],
            [
                'phone' => null,
                'email' => null,
                'address' => null,
                'capital_amount' => 50000,
                'ownership_percentage' => 50,
                'capital_account_id' => $partner2Capital->id,
                'current_account_id' => $partner2Current->id,
                'start_date' => now()->toDateString(),
                'is_active' => true,
                'notes' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 5) الحسابات المالية (الخزائن والبنوك)
        |--------------------------------------------------------------------------
        */
        FinancialAccount::updateOrCreate(
            ['code' => 'CASH-MAIN'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'الصندوق الرئيسي',
                'type' => 'cash',
                'account_id' => $cashAccount->id,
                'currency' => 'LYD',
                'opening_balance' => 0,
                'is_active' => true,
                'notes' => null,
            ]
        );

        FinancialAccount::updateOrCreate(
            ['code' => 'BANK-MAIN'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'الحساب البنكي الرئيسي',
                'type' => 'bank',
                'account_id' => $bankAccount->id,
                'currency' => 'LYD',
                'opening_balance' => 0,
                'is_active' => true,
                'notes' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 6) المخزن الرئيسي
        |--------------------------------------------------------------------------
        */
        Warehouse::updateOrCreate(
            ['code' => 'MAIN-WH'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'المخزن الرئيسي',
                'type' => 'main',
                'address' => null,
                'phone' => null,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 7) طرق الدفع
        |--------------------------------------------------------------------------
        */
        $paymentMethods = [
            ['code' => 'CASH', 'name' => 'نقدي'],
            ['code' => 'CARD', 'name' => 'بطاقة مصرفية'],
            ['code' => 'BANK_TRANSFER', 'name' => 'تحويل مصرفي'],
            ['code' => 'CHEQUE', 'name' => 'شيك'],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'is_active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 8) تصنيفات المصروفات
        |--------------------------------------------------------------------------
        */
        $expenseCategories = [
            ['code' => 'RENT', 'name' => 'إيجار', 'account_id' => $rentExpense->id],
            ['code' => 'ELECTRICITY', 'name' => 'كهرباء', 'account_id' => $electricityExpense->id],
            ['code' => 'WATER', 'name' => 'مياه', 'account_id' => $waterExpense->id],
            ['code' => 'INTERNET', 'name' => 'إنترنت', 'account_id' => $internetExpense->id],
            ['code' => 'MAINTENANCE', 'name' => 'صيانة', 'account_id' => $maintenanceExpense->id],
            ['code' => 'SALARY', 'name' => 'رواتب', 'account_id' => $salaryExpense->id],
            ['code' => 'OFFICE', 'name' => 'أدوات مكتبية', 'account_id' => $officeExpense->id],
            ['code' => 'FUEL', 'name' => 'وقود', 'account_id' => $fuelExpense->id],
        ];

        foreach ($expenseCategories as $expenseCategory) {
            ExpenseCategory::updateOrCreate(
                ['code' => $expenseCategory['code']],
                [
                    'name' => $expenseCategory['name'],
                    'expense_account_id' => $expenseCategory['account_id'],
                    'is_active' => true,
                    'notes' => null,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 9) مستخدم الأدمن
        |--------------------------------------------------------------------------
        */
        $adminUser = User::updateOrCreate(
            ['username' => 'hedaya@bennis'],
            [
                'branch_id' => $mainBranch->id,
                'role_id' => $adminRole?->id,
                'name' => 'هدى',
                'password' => Hash::make('12345678'),
                'phone' => null,
                'email' => null,
                'address' => null,
                'salary' => null,
                'image_path' => null,
                'extra_permissions' => [],
                'denied_permissions' => [],
                'is_active' => true,
                'is_deleted' => false,
                'is_locked' => false,
                'last_login_at' => null,
                'notes' => 'مستخدم الأدمن الافتراضي',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 10) ربط مدير الفرع
        |--------------------------------------------------------------------------
        */
        $mainBranch->update([
            'manager_user_id' => $adminUser->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 11) عملاء
        |--------------------------------------------------------------------------
        */
        $cashCustomerAccount = $createAccount($customersParent->id, '1131', 'العميل النقدي', 'asset', 'debit', 4, false, true);
        $sampleCustomerAccount = $createAccount($customersParent->id, '1132', 'عميل تجريبي', 'asset', 'debit', 4, false, true);

        Customer::updateOrCreate(
            ['code' => 'CASH-CUST'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'عميل نقدي',
                'phone' => null,
                'email' => null,
                'address' => null,
                'notes' => null,
                'account_id' => $cashCustomerAccount->id,
                'is_active' => true,
                'is_deleted' => false,
                'is_locked' => true,
            ]
        );

        Customer::updateOrCreate(
            ['code' => 'CUST-001'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'عميل تجريبي',
                'phone' => '0910000000',
                'email' => null,
                'address' => null,
                'notes' => null,
                'account_id' => $sampleCustomerAccount->id,
                'is_active' => true,
                'is_deleted' => false,
                'is_locked' => false,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 12) موردون
        |--------------------------------------------------------------------------
        */
        $sampleSupplierAccount = $createAccount($suppliersParent->id, '2101', 'مورد تجريبي', 'liability', 'credit', 3, false, true);

        Supplier::updateOrCreate(
            ['code' => 'SUP-001'],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'مورد تجريبي',
                'phone' => '0920000000',
                'email' => null,
                'address' => null,
                'notes' => null,
                'account_id' => $sampleSupplierAccount->id,
                'is_active' => true,
                'is_deleted' => false,
                'is_locked' => false,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 13) تصنيفات المنتجات
        |--------------------------------------------------------------------------
        */
        $metalCategory = Category::updateOrCreate(
            ['code' => 'METAL'],
            [
                'name' => 'حديد ومعادن',
                'description' => 'تصنيف خاص بمنتجات الحديد والمعادن',
                'is_active' => true,
                'notes' => null,
            ]
        );

        $toolsCategory = Category::updateOrCreate(
            ['code' => 'TOOLS'],
            [
                'name' => 'أدوات',
                'description' => 'تصنيف الأدوات العامة',
                'is_active' => true,
                'notes' => null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 14) منتجات تجريبية
        |--------------------------------------------------------------------------
        */
        $products = [
            [
                'category_id' => $metalCategory->id,
                'name' => 'حديد 6 لينيه',
                'product_code' => 'PRD-0001',
                'barcode' => '100000000001',
                'unit_name' => 'قطعة',
                'image_path' => null,
                'current_price' => 120,
                'last_purchase_price' => 90,
                'minimum_stock' => 10,
                'is_service' => false,
            ],
            [
                'category_id' => $metalCategory->id,
                'name' => 'حديد 8 لينيه',
                'product_code' => 'PRD-0002',
                'barcode' => '100000000002',
                'unit_name' => 'قطعة',
                'image_path' => null,
                'current_price' => 150,
                'last_purchase_price' => 110,
                'minimum_stock' => 8,
                'is_service' => false,
            ],
            [
                'category_id' => $toolsCategory->id,
                'name' => 'مقص حديد',
                'product_code' => 'PRD-0003',
                'barcode' => '100000000003',
                'unit_name' => 'قطعة',
                'image_path' => null,
                'current_price' => 35,
                'last_purchase_price' => 22,
                'minimum_stock' => 5,
                'is_service' => false,
            ],
        ];

        foreach ($products as $item) {
            $product = Product::updateOrCreate(
                ['product_code' => $item['product_code']],
                [
                    'category_id' => $item['category_id'],
                    'name' => $item['name'],
                    'barcode' => $item['barcode'],
                    'unit_name' => $item['unit_name'],
                    'image_path' => $item['image_path'],
                    'current_price' => $item['current_price'],
                    'last_purchase_price' => $item['last_purchase_price'],
                    'minimum_stock' => $item['minimum_stock'],
                    'is_service' => $item['is_service'],
                    'is_active' => true,
                    'is_deleted' => false,
                    'notes' => null,
                ]
            );

            ProductPriceHistory::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'start_date' => now()->startOfDay(),
                ],
                [
                    'price' => $item['current_price'],
                    'end_date' => null,
                    'notes' => 'سعر افتتاحي',
                ]
            );
        }
    }
}