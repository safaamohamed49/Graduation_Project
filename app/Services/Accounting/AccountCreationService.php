<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Partner;
use App\Models\Supplier;
use Illuminate\Support\Str;

class AccountCreationService
{
    public function createCustomerAccount(Customer $customer): Account
    {
        $parent = $this->requiredParentAccount('1130', 'حساب العملاء الرئيسي غير موجود.');

        return $this->createChildAccount(
            parent: $parent,
            codePrefix: '113',
            name: 'عميل - ' . $customer->name,
            type: 'asset',
            nature: 'debit',
            notes: 'حساب تلقائي للعميل رقم ' . $customer->id
        );
    }

    public function createSupplierAccount(Supplier $supplier): Account
    {
        $parent = $this->requiredParentAccount('2100', 'حساب الموردين الرئيسي غير موجود.');

        return $this->createChildAccount(
            parent: $parent,
            codePrefix: '210',
            name: 'مورد - ' . $supplier->name,
            type: 'liability',
            nature: 'credit',
            notes: 'حساب تلقائي للمورد رقم ' . $supplier->id
        );
    }

    public function createPartnerCapitalAccount(Partner $partner): Account
    {
        $parent = $this->requiredParentAccount('3100', 'حساب رؤوس أموال الشركاء غير موجود.');

        return $this->createChildAccount(
            parent: $parent,
            codePrefix: '31',
            name: 'رأس مال - ' . $partner->name,
            type: 'equity',
            nature: 'credit',
            notes: 'حساب رأس مال تلقائي للشريك رقم ' . $partner->id
        );
    }

    public function createPartnerCurrentAccount(Partner $partner): Account
    {
        $parent = $this->requiredParentAccount('3200', 'حساب جاري الشركاء غير موجود.');

        return $this->createChildAccount(
            parent: $parent,
            codePrefix: '32',
            name: 'جاري - ' . $partner->name,
            type: 'equity',
            nature: 'debit',
            notes: 'حساب جاري تلقائي للشريك رقم ' . $partner->id
        );
    }

    public function createEmployeeAccount(Employee $employee): Account
    {
        $parent = $this->firstOrCreateEmployeeParentAccount();

        return $this->createChildAccount(
            parent: $parent,
            codePrefix: '115',
            name: 'موظف - ' . $employee->name,
            type: 'asset',
            nature: 'debit',
            notes: 'حساب تلقائي للموظف رقم ' . $employee->id
        );
    }

    private function requiredParentAccount(string $code, string $errorMessage): Account
    {
        $account = Account::where('code', $code)->first();

        if (!$account) {
            abort(422, $errorMessage);
        }

        return $account;
    }

    private function firstOrCreateEmployeeParentAccount(): Account
    {
        $currentAssets = $this->requiredParentAccount('1100', 'حساب الأصول المتداولة غير موجود.');

        return Account::updateOrCreate(
            ['code' => '1150'],
            [
                'parent_id' => $currentAssets->id,
                'name' => 'عهد وسلف الموظفين',
                'type' => 'asset',
                'nature' => 'debit',
                'level' => 3,
                'is_group' => true,
                'is_active' => true,
                'is_system' => true,
                'notes' => 'حساب رئيسي لعهد وسلف الموظفين.',
            ]
        );
    }

    private function createChildAccount(
        Account $parent,
        string $codePrefix,
        string $name,
        string $type,
        string $nature,
        ?string $notes = null
    ): Account {
        return Account::create([
            'parent_id' => $parent->id,
            'code' => $this->nextCode($parent, $codePrefix),
            'name' => $this->uniqueName($name),
            'type' => $type,
            'nature' => $nature,
            'level' => ((int) $parent->level) + 1,
            'is_group' => false,
            'is_active' => true,
            'is_system' => false,
            'notes' => $notes,
        ]);
    }

    private function nextCode(Account $parent, string $codePrefix): string
    {
        $lastCode = Account::query()
            ->where('parent_id', $parent->id)
            ->where('code', 'like', $codePrefix . '%')
            ->orderByRaw('CAST(code AS UNSIGNED) DESC')
            ->value('code');

        if (!$lastCode) {
            return $codePrefix . '001';
        }

        $next = ((int) $lastCode) + 1;

        return (string) $next;
    }

    private function uniqueName(string $name): string
    {
        $cleanName = trim($name);

        if (!Account::where('name', $cleanName)->exists()) {
            return $cleanName;
        }

        return $cleanName . ' - ' . Str::upper(Str::random(5));
    }
}