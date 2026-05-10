<script setup>
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  receiptVoucher: Object,
  financialAccounts: Array,
  paymentMethods: Array,
  customers: Array,
  customerOpenOrders: Array,
  suppliers: Array,
  employees: Array,
  partners: Array,
  accounts: Array,
})

const form = useForm({
  voucher_date: props.receiptVoucher.voucher_date
    ? props.receiptVoucher.voucher_date.slice(0, 10)
    : new Date().toISOString().slice(0, 10),
  financial_account_id: props.receiptVoucher.financial_account_id ?? '',
  payment_method_id: props.receiptVoucher.payment_method_id ?? '',
  received_from_type: props.receiptVoucher.received_from_type ?? '',
  received_from_id: props.receiptVoucher.received_from_id ?? '',
  partner_transaction_type: props.receiptVoucher.partner_transaction_type ?? 'current',
  account_id: props.receiptVoucher.account_id ?? '',
  reference_type: props.receiptVoucher.reference_type ?? '',
  reference_id: props.receiptVoucher.reference_id ?? '',
  amount: props.receiptVoucher.amount ?? '',
  description: props.receiptVoucher.description ?? '',
})

const receivedFromTypes = [
  { value: 'customer', label: 'قبض من عميل' },
  { value: 'supplier', label: 'قبض من مورد' },
  { value: 'employee', label: 'قبض من موظف' },
  { value: 'partner', label: 'إيداع / تسوية شريك' },
  { value: 'other', label: 'قبض آخر' },
]

const selectedFinancialAccount = computed(() => {
  return props.financialAccounts.find(
    (item) => Number(item.id) === Number(form.financial_account_id)
  )
})

const selectedManualAccount = computed(() => {
  return props.accounts.find(
    (item) => Number(item.id) === Number(form.account_id)
  )
})

const receivedFromOptions = computed(() => {
  if (form.received_from_type === 'customer') return props.customers
  if (form.received_from_type === 'supplier') return props.suppliers
  if (form.received_from_type === 'employee') return props.employees
  if (form.received_from_type === 'partner') return props.partners

  return []
})

const customerOrders = computed(() => {
  if (form.received_from_type !== 'customer' || !form.received_from_id) {
    return []
  }

  return (props.customerOpenOrders || []).filter((order) => {
    return Number(order.customer_id) === Number(form.received_from_id)
      || Number(order.id) === Number(form.reference_id)
  })
})

const selectedOrder = computed(() => {
  return customerOrders.value.find((order) => Number(order.id) === Number(form.reference_id))
})

const showReceivedFromSelect = computed(() => {
  return ['customer', 'supplier', 'employee', 'partner'].includes(form.received_from_type)
})

const showManualAccountSelect = computed(() => {
  return form.received_from_type === 'other'
})

const showPartnerTransactionType = computed(() => {
  return form.received_from_type === 'partner'
})

const showCustomerOpenOrders = computed(() => {
  return form.received_from_type === 'customer' && Number(form.received_from_id || 0) > 0
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

  if (form.received_from_type === 'partner') {
    return form.partner_transaction_type === 'capital'
      ? 'رأس مال الشريك'
      : 'جاري الشريك'
  }

  if (form.received_from_type === 'other') {
    return selectedManualAccount.value?.name || 'الحساب المختار'
  }

  return '-'
})

const maxAllowedAmount = computed(() => {
  if (!selectedOrder.value) return null

  const currentVoucherAmount = Number(props.receiptVoucher.amount || 0)
  const sameOrder = Number(props.receiptVoucher.reference_id || 0) === Number(selectedOrder.value.id)
  const remaining = Number(selectedOrder.value.remaining_amount || 0)

  return sameOrder ? remaining + currentVoucherAmount : remaining
})

watch(
  () => form.received_from_type,
  (newValue, oldValue) => {
    if (oldValue !== undefined && newValue !== oldValue) {
      form.received_from_id = ''
      form.account_id = ''
      form.reference_type = ''
      form.reference_id = ''

      if (newValue === 'partner') {
        form.partner_transaction_type = 'current'
      }
    }
  }
)

watch(
  () => form.received_from_id,
  (newValue, oldValue) => {
    if (oldValue !== undefined && newValue !== oldValue) {
      form.reference_type = ''
      form.reference_id = ''
    }
  }
)

watch(
  () => form.reference_id,
  () => {
    if (selectedOrder.value) {
      form.reference_type = 'order'

      if (!form.amount || Number(form.amount || 0) <= 0) {
        form.amount = maxAllowedAmount.value
      }

      if (!form.description) {
        form.description = `قبض من العميل مقابل فاتورة بيع رقم ${selectedOrder.value.order_number}`
      }
    } else {
      form.reference_type = ''
    }
  }
)

watch(
  () => form.amount,
  () => {
    if (!selectedOrder.value || maxAllowedAmount.value === null) return

    if (Number(form.amount || 0) > Number(maxAllowedAmount.value || 0)) {
      form.amount = maxAllowedAmount.value
    }
  }
)

const submit = () => {
  form.put(`/receipt-vouchers/${props.receiptVoucher.id}`)
}
</script>

<template>
  <EntityFormShell
    page-title="تعديل إيصال قبض"
    hero-badge="الخزينة / تعديل إيصال قبض"
    hero-title="تعديل إيصال قبض"
    hero-description="تعديل الإيصال سيعيد تحديث القيد المحاسبي والفاتورة المرتبطة به تلقائياً. هذا الإجراء مسموح للأدمن فقط."
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

      <div v-if="showCustomerOpenOrders" class="md:col-span-2 rounded-[28px] bg-emerald-50 p-5 ring-1 ring-emerald-200">
        <label class="mb-2 block text-sm font-black text-emerald-800">
          ربط الإيصال بفاتورة بيع مفتوحة
        </label>

        <select
          v-model="form.reference_id"
          class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
        >
          <option value="">بدون ربط بفاتورة محددة</option>

          <option v-for="order in customerOrders" :key="order.id" :value="order.id">
            {{ order.order_number }} |
            التاريخ: {{ order.order_date }} |
            الإجمالي: {{ Number(order.total_price || 0).toFixed(2) }} |
            المدفوع: {{ Number(order.paid_amount || 0).toFixed(2) }} |
            المتبقي: {{ Number(order.remaining_amount || 0).toFixed(2) }}
          </option>
        </select>

        <div v-if="form.errors.reference_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.reference_id }}
        </div>

        <div v-if="!customerOrders.length" class="mt-3 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-slate-500">
          لا توجد فواتير بيع مفتوحة لهذا العميل.
        </div>

        <div v-if="selectedOrder" class="mt-4 grid gap-3 md:grid-cols-5">
          <div class="rounded-2xl bg-white p-4 ring-1 ring-emerald-100">
            <div class="text-xs font-bold text-slate-400">رقم الفاتورة</div>
            <div class="mt-2 font-black text-slate-800">{{ selectedOrder.order_number }}</div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-emerald-100">
            <div class="text-xs font-bold text-slate-400">إجمالي الفاتورة</div>
            <div class="mt-2 font-black text-slate-800">{{ Number(selectedOrder.total_price || 0).toFixed(2) }}</div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-emerald-100">
            <div class="text-xs font-bold text-slate-400">المدفوع حاليًا</div>
            <div class="mt-2 font-black text-emerald-700">{{ Number(selectedOrder.paid_amount || 0).toFixed(2) }}</div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-emerald-100">
            <div class="text-xs font-bold text-slate-400">المتاح قبضه</div>
            <div class="mt-2 font-black text-rose-700">{{ Number(maxAllowedAmount || 0).toFixed(2) }}</div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-emerald-100">
            <div class="text-xs font-bold text-slate-400">قيمة هذا الإيصال</div>
            <div class="mt-2 font-black text-indigo-700">{{ Number(form.amount || 0).toFixed(2) }}</div>
          </div>
        </div>

        <p class="mt-3 text-xs font-bold leading-6 text-emerald-700">
          عند تعديل إيصال مربوط بفاتورة، سيخصم النظام أثر الإيصال القديم أولًا ثم يطبق القيمة الجديدة ويمنع تجاوز المتبقي.
        </p>
      </div>

      <div v-if="showPartnerTransactionType">
        <label class="mb-2 block text-sm font-black text-slate-700">نوع حركة الشريك</label>
        <select
          v-model="form.partner_transaction_type"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
        >
          <option value="capital">إضافة رأس مال</option>
          <option value="current">إيداع في الجاري</option>
        </select>
        <div v-if="form.errors.partner_transaction_type" class="mt-1 text-sm text-red-600">
          {{ form.errors.partner_transaction_type }}
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
          placeholder="مثال: قبض دفعة من عميل / إضافة رأس مال شريك / تسوية مالية..."
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
            <div class="mt-2 font-black text-emerald-700">{{ debitSideLabel }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ Number(form.amount || 0).toFixed(2) }}</div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
            <div class="text-xs font-bold text-slate-400">دائن Credit</div>
            <div class="mt-2 font-black text-rose-700">{{ creditSideLabel }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ Number(form.amount || 0).toFixed(2) }}</div>
          </div>
        </div>

        <div class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">
          سيتم تحديث القيد المحاسبي والفاتورة المرتبطة بهذا الإيصال تلقائياً عند الحفظ.
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link :href="`/receipt-vouchers/${props.receiptVoucher.id}`">
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