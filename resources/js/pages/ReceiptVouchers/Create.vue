<script setup>
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  financialAccounts: Array,
  paymentMethods: Array,
  customers: Array,
  suppliers: Array,
  employees: Array,
  partners: Array,
  accounts: Array,
})

const form = useForm({
  voucher_date: new Date().toISOString().slice(0, 10),
  financial_account_id: '',
  payment_method_id: '',
  received_from_type: '',
  received_from_id: '',
  account_id: '',
  amount: '',
  description: '',
})

const receivedFromTypes = [
  { value: 'customer', label: 'قبض من عميل' },
  { value: 'supplier', label: 'قبض من مورد' },
  { value: 'employee', label: 'قبض من موظف' },
  { value: 'partner', label: 'إيداع / تسوية شريك' },
  { value: 'other', label: 'قبض آخر' },
]

const selectedFinancialAccount = computed(() => {
  return props.financialAccounts.find((item) => Number(item.id) === Number(form.financial_account_id))
})

const selectedManualAccount = computed(() => {
  return props.accounts.find((item) => Number(item.id) === Number(form.account_id))
})

const receivedFromOptions = computed(() => {
  if (form.received_from_type === 'customer') return props.customers
  if (form.received_from_type === 'supplier') return props.suppliers
  if (form.received_from_type === 'employee') return props.employees
  if (form.received_from_type === 'partner') return props.partners

  return []
})

const showReceivedFromSelect = computed(() => {
  return ['customer', 'supplier', 'employee', 'partner'].includes(form.received_from_type)
})

const showManualAccountSelect = computed(() => {
  return form.received_from_type === 'other'
})

const receivedFromLabel = computed(() => {
  if (form.received_from_type === 'customer') return 'العميل'
  if (form.received_from_type === 'supplier') return 'المورد'
  if (form.received_from_type === 'employee') return 'الموظف'
  if (form.received_from_type === 'partner') return 'الشريك'

  return 'المقبوض منه'
})

const debitSideLabel = computed(() => {
  return selectedFinancialAccount.value?.name || 'الخزينة / البنك'
})

const creditSideLabel = computed(() => {
  if (form.received_from_type === 'customer') return 'حساب العميل'
  if (form.received_from_type === 'supplier') return 'حساب المورد'
  if (form.received_from_type === 'employee') return 'حساب الموظف'
  if (form.received_from_type === 'partner') return 'جاري الشريك'
  if (form.received_from_type === 'other') return selectedManualAccount.value?.name || 'الحساب المختار'

  return '-'
})

watch(
  () => form.received_from_type,
  () => {
    form.received_from_id = ''
    form.account_id = ''
  }
)

const submit = () => {
  form.post('/receipt-vouchers')
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة إيصال قبض"
    hero-badge="الخزينة / إيصال قبض"
    hero-title="إضافة إيصال قبض جديد"
    hero-description="اختاري الخزينة أو البنك، وحددي الطرف المقبوض منه حتى يتم توليد القيد المحاسبي المزدوج تلقائياً."
    hero-back-href="/receipt-vouchers"
    hero-gradient-class="bg-gradient-to-br from-emerald-900 via-slate-900 to-cyan-800"
    card-title="بيانات إيصال القبض"
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
        <label class="mb-2 block text-sm font-black text-slate-700">الخزينة / البنك المقبوض فيه</label>
        <select
          v-model="form.financial_account_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
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
        <label class="mb-2 block text-sm font-black text-slate-700">طريقة القبض</label>
        <select
          v-model="form.payment_method_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
        >
          <option value="">اختاري طريقة القبض</option>
          <option v-for="method in props.paymentMethods" :key="method.id" :value="method.id">
            {{ method.name }}
          </option>
        </select>
        <div v-if="form.errors.payment_method_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.payment_method_id }}
        </div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">نوع المقبوض منه</label>
        <select
          v-model="form.received_from_type"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
        >
          <option value="">اختاري نوع المقبوض منه</option>
          <option v-for="type in receivedFromTypes" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>
        <div v-if="form.errors.received_from_type" class="mt-1 text-sm text-red-600">
          {{ form.errors.received_from_type }}
        </div>
      </div>

      <div v-if="showReceivedFromSelect">
        <label class="mb-2 block text-sm font-black text-slate-700">{{ receivedFromLabel }}</label>
        <select
          v-model="form.received_from_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
        >
          <option value="">اختاري {{ receivedFromLabel }}</option>
          <option v-for="item in receivedFromOptions" :key="item.id" :value="item.id">
            {{ item.name }}
          </option>
        </select>
        <div v-if="form.errors.received_from_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.received_from_id }}
        </div>
      </div>

      <div v-if="showManualAccountSelect">
        <label class="mb-2 block text-sm font-black text-slate-700">الحساب الدائن</label>
        <select
          v-model="form.account_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
        >
          <option value="">اختاري الحساب</option>
          <option v-for="account in props.accounts" :key="account.id" :value="account.id">
            {{ account.code }} - {{ account.name }}
          </option>
        </select>
        <div v-if="form.errors.account_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.account_id }}
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
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
          placeholder="مثال: قبض دفعة من عميل / إيداع شريك / تسوية مالية..."
        />
        <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
          {{ form.errors.description }}
        </div>
      </div>

      <div class="md:col-span-2 rounded-[28px] bg-slate-50 p-5 ring-1 ring-slate-200">
        <div class="mb-4 text-sm font-black text-slate-700">معاينة القيد المحاسبي</div>

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

        <div class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
          في إيصال القبض: الخزينة أو البنك دائماً مدين.
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/receipt-vouchers">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ وترحيل الإيصال"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>