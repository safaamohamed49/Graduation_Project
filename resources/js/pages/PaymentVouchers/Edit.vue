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
  supplierOpenPurchaseInvoices: Array,
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
  reference_type: props.paymentVoucher.reference_type ?? '',
  reference_id: props.paymentVoucher.reference_id ?? '',
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

const showPurchaseInvoiceLink = computed(() => {
  return form.beneficiary_type === 'supplier' && !!form.beneficiary_id
})

const supplierInvoices = computed(() => {
  if (!showPurchaseInvoiceLink.value) return []

  const openInvoices = props.supplierOpenPurchaseInvoices ?? []

  const currentLinked = props.paymentVoucher.reference_type === 'purchase_invoice'
    ? {
        id: props.paymentVoucher.reference_id,
        invoice_number: props.paymentVoucher.linked_purchase_invoice?.invoice_number ?? `#${props.paymentVoucher.reference_id}`,
        branch_id: props.paymentVoucher.branch_id,
        supplier_id: props.paymentVoucher.beneficiary_id,
        supplier_name: '',
        invoice_date: props.paymentVoucher.linked_purchase_invoice?.invoice_date ?? '',
        total_price: Number(props.paymentVoucher.linked_purchase_invoice?.total_price || 0),
        paid_amount: Math.max(
          0,
          Number(props.paymentVoucher.linked_purchase_invoice?.paid_amount || 0) -
          Number(props.paymentVoucher.amount || 0)
        ),
        remaining_amount:
          Number(props.paymentVoucher.linked_purchase_invoice?.remaining_amount || 0) +
          Number(props.paymentVoucher.amount || 0),
        payment_status: props.paymentVoucher.linked_purchase_invoice?.payment_status ?? 'partial',
      }
    : null

  const merged = [...openInvoices]

  if (currentLinked?.id && !merged.some((item) => Number(item.id) === Number(currentLinked.id))) {
    merged.unshift(currentLinked)
  }

  return merged.filter((invoice) => {
    return Number(invoice.supplier_id) === Number(form.beneficiary_id)
  })
})

const selectedPurchaseInvoice = computed(() => {
  if (form.reference_type !== 'purchase_invoice' || !form.reference_id) return null

  return supplierInvoices.value.find((invoice) => Number(invoice.id) === Number(form.reference_id)) ?? null
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

const remainingPurchaseInvoiceAmount = computed(() => {
  return Number(selectedPurchaseInvoice.value?.remaining_amount || 0)
})

watch(
  () => form.beneficiary_type,
  (newValue, oldValue) => {
    if (oldValue !== undefined && newValue !== oldValue) {
      form.beneficiary_id = ''
      form.expense_category_id = ''
      form.reference_type = ''
      form.reference_id = ''

      if (newValue === 'salary') {
        const salaryCategory = props.expenseCategories.find((item) => item.code === 'SALARY')
        form.expense_category_id = salaryCategory?.id || ''
      }
    }
  }
)

watch(
  () => form.beneficiary_id,
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
    if (
      form.reference_type === 'purchase_invoice' &&
      selectedPurchaseInvoice.value &&
      Number(form.reference_id) !== Number(props.paymentVoucher.reference_id)
    ) {
      form.amount = remainingPurchaseInvoiceAmount.value
      form.description = `صرف على فاتورة شراء رقم ${selectedPurchaseInvoice.value.invoice_number}`
    }
  }
)

watch(
  () => form.reference_type,
  (value) => {
    if (value !== 'purchase_invoice') {
      form.reference_id = ''
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
    hero-description="تعديل الإيصال سيعيد تحديث القيد المحاسبي وفاتورة الشراء المرتبطة به تلقائيًا. هذا الإجراء مسموح للأدمن فقط."
    hero-back-href="/payment-vouchers"
    hero-gradient-class="bg-gradient-to-br from-rose-900 via-slate-900 to-orange-800"
    card-title="بيانات إيصال الصرف"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div class="md:col-span-2 rounded-[28px] bg-amber-50 p-5 ring-1 ring-amber-200">
        <h3 class="text-lg font-black text-amber-800">تنبيه مهم</h3>
        <p class="mt-2 text-sm font-bold leading-7 text-amber-700">
          عند تعديل إيصال صرف مرتبط بفاتورة شراء، سيُعاد احتساب المصروف والمتبقي على الفاتورة تلقائيًا.
          أما إلغاء الإيصال فيتم من صفحة العرض بقيد عكسي.
        </p>
      </div>

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
        <label class="mb-2 block text-sm font-black text-slate-700">طريقة الصرف</label>
        <select
          v-model="form.payment_method_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
        >
          <option value="">اختاري طريقة الصرف</option>
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

      <div v-if="showPurchaseInvoiceLink" class="md:col-span-2 rounded-[28px] bg-rose-50 p-5 ring-1 ring-rose-200">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 class="text-lg font-black text-rose-800">ربط الصرف بفاتورة شراء</h3>
            <p class="mt-1 text-sm font-bold text-rose-600">
              يمكن ربط الإيصال بفاتورة شراء مفتوحة للمورد، وسيتم تحديث حالة الصرف تلقائيًا.
            </p>
          </div>

          <div class="rounded-2xl bg-white px-4 py-3 text-center ring-1 ring-rose-200">
            <div class="text-xs font-bold text-rose-500">الفواتير المتاحة</div>
            <div class="mt-1 text-2xl font-black text-rose-700">
              {{ supplierInvoices.length }}
            </div>
          </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">نوع المرجع</label>
            <select
              v-model="form.reference_type"
              class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
            >
              <option value="">صرف غير مربوط بفاتورة</option>
              <option value="purchase_invoice">صرف على فاتورة شراء</option>
            </select>
            <div v-if="form.errors.reference_type" class="mt-1 text-sm text-red-600">
              {{ form.errors.reference_type }}
            </div>
          </div>

          <div v-if="form.reference_type === 'purchase_invoice'">
            <label class="mb-2 block text-sm font-black text-slate-700">فاتورة الشراء</label>
            <select
              v-model="form.reference_id"
              class="w-full rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"
            >
              <option value="">اختاري الفاتورة</option>
              <option v-for="invoice in supplierInvoices" :key="invoice.id" :value="invoice.id">
                {{ invoice.invoice_number }} - المتاح {{ Number(invoice.remaining_amount || 0).toFixed(2) }}
              </option>
            </select>
            <div v-if="form.errors.reference_id" class="mt-1 text-sm text-red-600">
              {{ form.errors.reference_id }}
            </div>
          </div>
        </div>

        <div
          v-if="selectedPurchaseInvoice"
          class="mt-4 grid gap-3 md:grid-cols-4"
        >
          <div class="rounded-2xl bg-white p-4 ring-1 ring-rose-100">
            <div class="text-xs font-bold text-slate-400">إجمالي الفاتورة</div>
            <div class="mt-1 text-xl font-black text-slate-800">
              {{ Number(selectedPurchaseInvoice.total_price || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-rose-100">
            <div class="text-xs font-bold text-slate-400">مصروف سابقًا</div>
            <div class="mt-1 text-xl font-black text-indigo-700">
              {{ Number(selectedPurchaseInvoice.paid_amount || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-rose-100">
            <div class="text-xs font-bold text-slate-400">المتاح للصرف</div>
            <div class="mt-1 text-xl font-black text-rose-700">
              {{ Number(selectedPurchaseInvoice.remaining_amount || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-rose-100">
            <div class="text-xs font-bold text-slate-400">تاريخ الفاتورة</div>
            <div class="mt-1 text-xl font-black text-slate-800">
              {{ selectedPurchaseInvoice.invoice_date || '-' }}
            </div>
          </div>
        </div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">المبلغ</label>
        <FormControl v-model="form.amount" type="number" step="0.01" placeholder="0.00" />
        <p v-if="selectedPurchaseInvoice" class="mt-1 text-xs font-bold text-rose-600">
          لا يمكن أن يتجاوز الصرف المتاح على الفاتورة:
          {{ remainingPurchaseInvoiceAmount.toFixed(2) }}
        </p>
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
          سيتم تحديث القيد المحاسبي وفاتورة الشراء المرتبطة بهذا الإيصال تلقائيًا عند الحفظ.
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