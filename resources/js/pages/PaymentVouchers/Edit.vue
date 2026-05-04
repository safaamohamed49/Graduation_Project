<script setup>
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  paymentVoucher: Object,
  financialAccounts: Array,
  paymentMethods: Array,
  expenseCategories: Array,
  suppliers: Array,
  customers: Array,
  employees: Array,
  partners: Array,
})

const form = useForm({
  voucher_date: props.paymentVoucher.voucher_date
    ? props.paymentVoucher.voucher_date.slice(0, 10)
    : new Date().toISOString().slice(0, 10),
  financial_account_id: props.paymentVoucher.financial_account_id ?? '',
  payment_method_id: props.paymentVoucher.payment_method_id ?? '',
  beneficiary_type: props.paymentVoucher.beneficiary_type ?? '',
  beneficiary_id: props.paymentVoucher.beneficiary_id ?? '',
  expense_category_id: props.paymentVoucher.expense_category_id ?? '',
  amount: props.paymentVoucher.amount ?? '',
  description: props.paymentVoucher.description ?? '',
})

const beneficiaryTypes = [
  { value: 'supplier', label: 'صرف لمورد' },
  { value: 'customer', label: 'صرف لعميل' },
  { value: 'employee', label: 'صرف لموظف / سلفة' },
  { value: 'salary', label: 'صرف راتب' },
  { value: 'partner', label: 'سحب شريك' },
  { value: 'expense', label: 'مصروف عام' },
]

const selectedFinancialAccount = computed(() => {
  return props.financialAccounts.find((item) => Number(item.id) === Number(form.financial_account_id))
})

const selectedExpenseCategory = computed(() => {
  return props.expenseCategories.find((item) => Number(item.id) === Number(form.expense_category_id))
})

const beneficiaryOptions = computed(() => {
  if (form.beneficiary_type === 'supplier') return props.suppliers
  if (form.beneficiary_type === 'customer') return props.customers
  if (form.beneficiary_type === 'employee') return props.employees
  if (form.beneficiary_type === 'salary') return props.employees
  if (form.beneficiary_type === 'partner') return props.partners

  return []
})

const showBeneficiarySelect = computed(() => {
  return ['supplier', 'customer', 'employee', 'salary', 'partner'].includes(form.beneficiary_type)
})

const showExpenseCategorySelect = computed(() => {
  return ['expense', 'salary'].includes(form.beneficiary_type)
})

const beneficiaryLabel = computed(() => {
  if (form.beneficiary_type === 'supplier') return 'المورد'
  if (form.beneficiary_type === 'customer') return 'العميل'
  if (form.beneficiary_type === 'employee') return 'الموظف'
  if (form.beneficiary_type === 'salary') return 'الموظف'
  if (form.beneficiary_type === 'partner') return 'الشريك'

  return 'المستفيد'
})

const debitSideLabel = computed(() => {
  if (form.beneficiary_type === 'supplier') return 'حساب المورد'
  if (form.beneficiary_type === 'customer') return 'حساب العميل'
  if (form.beneficiary_type === 'employee') return 'حساب الموظف'
  if (form.beneficiary_type === 'salary') return selectedExpenseCategory.value?.name || 'مصروف الرواتب'
  if (form.beneficiary_type === 'partner') return 'جاري الشريك'
  if (form.beneficiary_type === 'expense') return selectedExpenseCategory.value?.name || 'حساب المصروف'

  return '-'
})

const creditSideLabel = computed(() => {
  return selectedFinancialAccount.value?.name || 'الخزينة / البنك'
})

watch(
  () => form.beneficiary_type,
  (newValue, oldValue) => {
    if (oldValue !== undefined && newValue !== oldValue) {
      form.beneficiary_id = ''
      form.expense_category_id = ''

      if (newValue === 'salary') {
        const salaryCategory = props.expenseCategories.find((item) => item.code === 'SALARY')
        form.expense_category_id = salaryCategory?.id || ''
      }
    }
  }
)

const submit = () => {
  form.put(`/payment-vouchers/${props.paymentVoucher.id}`)
}
</script>

<template>
  <EntityFormShell
    page-title="تعديل إيصال صرف"
    hero-badge="الخزينة / تعديل إيصال صرف"
    hero-title="تعديل إيصال صرف"
    hero-description="تعديل الإيصال سيعيد تحديث القيد المحاسبي المرتبط به تلقائياً. هذا الإجراء مسموح للأدمن فقط."
    hero-back-href="/payment-vouchers"
    hero-gradient-class="bg-gradient-to-br from-rose-900 via-slate-900 to-orange-800"
    card-title="بيانات إيصال الصرف"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">تاريخ الإيصال</label>
        <FormControl v-model="form.voucher_date" type="date" />
        <div v-if="form.errors.voucher_date" class="mt-1 text-sm text-red-600">
          {{ form.errors.voucher_date }}
        </div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الخزينة / البنك المصروف منه</label>
        <select
          v-model="form.financial_account_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
        >
          <option value="">اختاري الحساب المالي</option>
          <option v-for="account in props.financialAccounts" :key="account.id" :value="account.id">
            {{ account.name }} - {{ account.code }}
          </option>
        </select>
        <div v-if="form.errors.financial_account_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.financial_account_id }}
        </div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">طريقة الدفع</label>
        <select
          v-model="form.payment_method_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
        >
          <option value="">اختاري طريقة الدفع</option>
          <option v-for="method in props.paymentMethods" :key="method.id" :value="method.id">
            {{ method.name }}
          </option>
        </select>
        <div v-if="form.errors.payment_method_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.payment_method_id }}
        </div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">نوع الصرف</label>
        <select
          v-model="form.beneficiary_type"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
        >
          <option value="">اختاري نوع الصرف</option>
          <option v-for="type in beneficiaryTypes" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>
        <div v-if="form.errors.beneficiary_type" class="mt-1 text-sm text-red-600">
          {{ form.errors.beneficiary_type }}
        </div>
      </div>

      <div v-if="showBeneficiarySelect">
        <label class="mb-2 block text-sm font-black text-slate-700">{{ beneficiaryLabel }}</label>
        <select
          v-model="form.beneficiary_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
        >
          <option value="">اختاري {{ beneficiaryLabel }}</option>
          <option v-for="item in beneficiaryOptions" :key="item.id" :value="item.id">
            {{ item.name }}
          </option>
        </select>
        <div v-if="form.errors.beneficiary_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.beneficiary_id }}
        </div>
      </div>

      <div v-if="showExpenseCategorySelect">
        <label class="mb-2 block text-sm font-black text-slate-700">تصنيف المصروف</label>
        <select
          v-model="form.expense_category_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
        >
          <option value="">اختاري تصنيف المصروف</option>
          <option v-for="category in props.expenseCategories" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
        <div v-if="form.errors.expense_category_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.expense_category_id }}
        </div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">المبلغ</label>
        <FormControl v-model="form.amount" type="number" step="0.01" placeholder="0.00" />
        <div v-if="form.errors.amount" class="mt-1 text-sm text-red-600">
          {{ form.errors.amount }}
        </div>
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">البيان / الوصف</label>
        <textarea
          v-model="form.description"
          rows="3"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
          placeholder="مثال: صرف دفعة للمورد / صرف راتب شهر / مصروف كهرباء..."
        />
        <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
          {{ form.errors.description }}
        </div>
      </div>

      <div class="md:col-span-2 rounded-[28px] bg-slate-50 p-5 ring-1 ring-slate-200">
        <div class="mb-4 text-sm font-black text-slate-700">معاينة القيد المحاسبي بعد التعديل</div>

        <div class="grid gap-3 md:grid-cols-2">
          <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
            <div class="text-xs font-bold text-slate-400">مدين Debit</div>
            <div class="mt-2 font-black text-emerald-700">
              {{ debitSideLabel }}
            </div>
            <div class="mt-1 text-sm text-slate-500">
              {{ Number(form.amount || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
            <div class="text-xs font-bold text-slate-400">دائن Credit</div>
            <div class="mt-2 font-black text-rose-700">
              {{ creditSideLabel }}
            </div>
            <div class="mt-1 text-sm text-slate-500">
              {{ Number(form.amount || 0).toFixed(2) }}
            </div>
          </div>
        </div>

        <div class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">
          سيتم تحديث القيد المحاسبي المرتبط بهذا الإيصال تلقائياً عند الحفظ.
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link :href="`/payment-vouchers/${props.paymentVoucher.id}`">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ التعديل"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>