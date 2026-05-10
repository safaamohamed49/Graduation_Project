<script setup>
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  branches: Array,
  warehouses: Array,
  suppliers: Array,
  products: Array,
  financialAccounts: Array,
  paymentMethods: Array,
})

const imageLoading = ref(false)
const imageMessage = ref('')
const imageError = ref('')

const branchOptions = computed(() => props.branches.map((item) => ({
  value: item.id,
  label: item.name,
})))

const warehouseOptions = computed(() => props.warehouses.map((item) => ({
  value: item.id,
  label: item.name,
})))

const supplierOptions = computed(() => props.suppliers.map((item) => ({
  value: item.id,
  label: `${item.name} - ${item.code ?? ''}`,
})))

const productOptions = computed(() => props.products.map((item) => ({
  value: item.id,
  label: `${item.name} - ${item.product_code ?? ''}`,
})))

const financialAccountOptions = computed(() =>
  props.financialAccounts.map((account) => ({
    value: account.id,
    label: `${account.name} - ${account.code ?? ''}`,
  }))
)

const paymentMethodOptions = computed(() =>
  props.paymentMethods.map((method) => ({
    value: method.id,
    label: method.name,
  }))
)

const form = useForm({
  invoice_number: '',
  branch_id: props.branches[0]?.id ?? '',
  warehouse_id: props.warehouses[0]?.id ?? '',
  supplier_id: '',
  invoice_date: new Date().toISOString().slice(0, 10),
  discount_amount: 0,
  total_expenses: 0,
  paid_amount: 0,
  payment_status: 'due',
  financial_account_id: '',
  payment_method_id: '',
  notes: '',
  items: [
    {
      product_id: '',
      quantity: 1,
      price: 0,
      notes: '',
    },
  ],
})

const subtotal = computed(() => {
  return form.items.reduce((sum, item) => {
    return sum + Number(item.quantity || 0) * Number(item.price || 0)
  }, 0)
})

const total = computed(() => {
  return Math.max(
    0,
    subtotal.value - Number(form.discount_amount || 0) + Number(form.total_expenses || 0)
  )
})

const remainingAmount = computed(() => {
  return Math.max(0, total.value - Number(form.paid_amount || 0))
})

const needsPaymentInfo = computed(() => {
  return ['paid', 'partial'].includes(form.payment_status)
})

watch(
  () => form.payment_status,
  (value) => {
    if (value === 'paid') {
      form.paid_amount = total.value
    }

    if (value === 'due') {
      form.paid_amount = 0
      form.financial_account_id = ''
      form.payment_method_id = ''
    }

    if (value === 'partial' && Number(form.paid_amount || 0) <= 0) {
      form.paid_amount = ''
    }
  }
)

watch(total, () => {
  if (form.payment_status === 'paid') {
    form.paid_amount = total.value
  }

  if (Number(form.paid_amount || 0) > total.value) {
    form.paid_amount = total.value
  }
})

const addItem = () => {
  form.items.push({
    product_id: '',
    quantity: 1,
    price: 0,
    notes: '',
  })
}

const removeItem = (index) => {
  if (form.items.length === 1) return
  form.items.splice(index, 1)
}

const productSelected = (index) => {
  const product = props.products.find((item) => Number(item.id) === Number(form.items[index].product_id))
  if (!product) return

  form.items[index].price = product.last_purchase_price ?? 0
}

const submit = () => {
  form.post('/purchase-invoices')
}

const handleInvoiceImage = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  imageLoading.value = true
  imageMessage.value = ''
  imageError.value = ''

  const formData = new FormData()
  formData.append('image', file)

  try {
    const response = await fetch('/purchase-invoices/extract-image', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': window.Laravel.csrfToken,
        Accept: 'application/json',
      },
      body: formData,
    })

    const data = await response.json()

    if (!response.ok || !data.success) {
      imageError.value = data.message || 'تعذر تحليل الصورة.'
      return
    }

    imageMessage.value = data.message || 'تم تحليل الصورة بنجاح.'

    if (data.draft?.invoice_number) {
      form.invoice_number = data.draft.invoice_number
    }

    if (data.draft?.invoice_date) {
      form.invoice_date = data.draft.invoice_date
    }

    if (Array.isArray(data.draft?.items) && data.draft.items.length) {
      form.items = data.draft.items.map((item) => ({
        product_id: item.product_id ?? '',
        quantity: item.quantity ?? 1,
        price: item.price ?? 0,
        notes: item.notes ?? '',
      }))
    }
  } catch (error) {
    imageError.value = 'حدث خطأ أثناء رفع الصورة.'
  } finally {
    imageLoading.value = false
  }
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة فاتورة شراء"
    hero-badge="المشتريات / إضافة"
    hero-title="إضافة فاتورة شراء جديدة"
    hero-description="أدخلي بيانات فاتورة الشراء يدويًا أو ارفعي صورة واضحة للفاتورة. الفاتورة تثبت المخزون وذمة المورد، وأي دفعة سيتم تحويلها إلى إيصال صرف تلقائي."
    hero-back-href="/purchase-invoices"
    hero-gradient-class="bg-gradient-to-br from-emerald-700 via-teal-700 to-slate-900"
    card-title="بيانات فاتورة الشراء"
    @submit="submit"
  >
    <div class="space-y-6 p-6">
      <section class="rounded-[28px] border border-dashed border-cyan-300 bg-cyan-50 p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h3 class="text-lg font-black text-slate-800">الإدخال الذكي من صورة</h3>
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">
              يرجى رفع صورة واضحة ومطبوعة للفاتورة. يجب أن تكون أسماء المنتجات والكميات والأسعار ظاهرة بالكامل.
              أي بند يحتوي على بيانات ناقصة أو غير واضحة لن يتم اعتماده تلقائيًا، ويمكن تعديله يدويًا قبل الحفظ.
            </p>
          </div>

          <label class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-800">
            {{ imageLoading ? 'جاري التحليل...' : 'رفع صورة فاتورة' }}
            <input
              type="file"
              class="hidden"
              accept=".jpg,.jpeg,.png,.webp"
              :disabled="imageLoading"
              @change="handleInvoiceImage"
            />
          </label>
        </div>

        <div v-if="imageMessage" class="mt-3 rounded-2xl bg-emerald-100 px-4 py-3 text-sm font-bold text-emerald-700">
          {{ imageMessage }}
        </div>

        <div v-if="imageError" class="mt-3 rounded-2xl bg-rose-100 px-4 py-3 text-sm font-bold text-rose-700">
          {{ imageError }}
        </div>
      </section>

      <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">رقم الفاتورة</label>
          <FormControl v-model="form.invoice_number" type="text" />
          <div v-if="form.errors.invoice_number" class="mt-1 text-sm text-red-600">
            {{ form.errors.invoice_number }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">تاريخ الفاتورة</label>
          <FormControl v-model="form.invoice_date" type="date" />
          <div v-if="form.errors.invoice_date" class="mt-1 text-sm text-red-600">
            {{ form.errors.invoice_date }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">المورد</label>
          <FormControl v-model="form.supplier_id" :options="supplierOptions" />
          <div v-if="form.errors.supplier_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.supplier_id }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
          <FormControl v-model="form.branch_id" :options="branchOptions" />
          <div v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.branch_id }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">المخزن</label>
          <FormControl v-model="form.warehouse_id" :options="warehouseOptions" />
          <div v-if="form.errors.warehouse_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.warehouse_id }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">
            حالة الصرف الأولي
          </label>

          <select
            v-model="form.payment_status"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
          >
            <option value="paid">صرف كامل وإنشاء إيصال صرف</option>
            <option value="due">بدون صرف الآن</option>
            <option value="partial">صرف جزئي وإنشاء إيصال صرف</option>
          </select>

          <p class="mt-1 text-xs font-bold text-emerald-600">
            عند إدخال مبلغ مدفوع سيتم إنشاء إيصال صرف تلقائي وربطه بالفاتورة.
          </p>

          <div v-if="form.errors.payment_status" class="mt-1 text-sm text-red-600">
            {{ form.errors.payment_status }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">مصاريف إضافية</label>
          <FormControl v-model="form.total_expenses" type="number" step="0.01" />
          <div v-if="form.errors.total_expenses" class="mt-1 text-sm text-red-600">
            {{ form.errors.total_expenses }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">الخصم</label>
          <FormControl v-model="form.discount_amount" type="number" step="0.01" />
          <div v-if="form.errors.discount_amount" class="mt-1 text-sm text-red-600">
            {{ form.errors.discount_amount }}
          </div>
        </div>

        <div v-if="needsPaymentInfo">
          <label class="mb-2 block text-sm font-black text-slate-700">الخزينة / البنك</label>
          <FormControl v-model="form.financial_account_id" :options="financialAccountOptions" />
          <div v-if="form.errors.financial_account_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.financial_account_id }}
          </div>
        </div>

        <div v-if="needsPaymentInfo">
          <label class="mb-2 block text-sm font-black text-slate-700">طريقة الصرف</label>
          <FormControl v-model="form.payment_method_id" :options="paymentMethodOptions" />
          <div v-if="form.errors.payment_method_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.payment_method_id }}
          </div>
        </div>

        <div v-if="form.payment_status === 'partial'">
          <label class="mb-2 block text-sm font-black text-slate-700">الدفعة الأولى للمورد</label>
          <FormControl v-model="form.paid_amount" type="number" step="0.01" />
          <p class="mt-1 text-xs font-bold text-emerald-600">
            سيتم إنشاء إيصال صرف تلقائي بهذه القيمة.
          </p>
          <div v-if="form.errors.paid_amount" class="mt-1 text-sm text-red-600">
            {{ form.errors.paid_amount }}
          </div>
        </div>

        <div class="md:col-span-2 xl:col-span-3">
          <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
          <FormControl v-model="form.notes" type="textarea" />
          <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
            {{ form.errors.notes }}
          </div>
        </div>
      </section>

      <section class="rounded-[28px] bg-white ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <div>
            <h3 class="text-lg font-black text-slate-800">بنود الفاتورة</h3>
            <p class="mt-1 text-xs text-slate-400">
              اختاري المنتج والكمية وسعر الشراء لكل بند. سيتم إنشاء دفعات مخزون مرتبطة بالفاتورة.
            </p>
          </div>

          <BaseButton label="إضافة بند" color="primary" @click="addItem" />
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-3">المنتج</th>
                <th class="px-4 py-3">الكمية</th>
                <th class="px-4 py-3">سعر الشراء</th>
                <th class="px-4 py-3">الإجمالي</th>
                <th class="px-4 py-3">ملاحظات</th>
                <th class="px-4 py-3">حذف</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(item, index) in form.items" :key="index" class="border-t">
                <td class="min-w-[260px] px-4 py-3">
                  <FormControl
                    v-model="item.product_id"
                    :options="productOptions"
                    @change="productSelected(index)"
                  />
                  <div v-if="form.errors[`items.${index}.product_id`]" class="mt-1 text-xs text-red-600">
                    {{ form.errors[`items.${index}.product_id`] }}
                  </div>
                </td>

                <td class="min-w-[120px] px-4 py-3">
                  <FormControl v-model="item.quantity" type="number" step="0.01" />
                  <div v-if="form.errors[`items.${index}.quantity`]" class="mt-1 text-xs text-red-600">
                    {{ form.errors[`items.${index}.quantity`] }}
                  </div>
                </td>

                <td class="min-w-[140px] px-4 py-3">
                  <FormControl v-model="item.price" type="number" step="0.01" />
                  <div v-if="form.errors[`items.${index}.price`]" class="mt-1 text-xs text-red-600">
                    {{ form.errors[`items.${index}.price`] }}
                  </div>
                </td>

                <td class="px-4 py-3 font-black text-slate-800">
                  {{ (Number(item.quantity || 0) * Number(item.price || 0)).toFixed(2) }}
                </td>

                <td class="min-w-[180px] px-4 py-3">
                  <FormControl v-model="item.notes" type="text" />
                </td>

                <td class="px-4 py-3">
                  <BaseButton label="حذف" color="danger" small @click="removeItem(index)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[24px] bg-slate-50 p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الإجمالي قبل الخصم</div>
          <div class="mt-2 text-2xl font-black text-slate-800">{{ subtotal.toFixed(2) }}</div>
        </div>

        <div class="rounded-[24px] bg-amber-50 p-5 ring-1 ring-amber-200">
          <div class="text-sm font-bold text-amber-700">المصاريف / الخصم</div>
          <div class="mt-2 text-2xl font-black text-amber-700">
            +{{ Number(form.total_expenses || 0).toFixed(2) }} / -{{ Number(form.discount_amount || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-emerald-50 p-5 ring-1 ring-emerald-200">
          <div class="text-sm font-bold text-emerald-700">الإجمالي النهائي</div>
          <div class="mt-2 text-2xl font-black text-emerald-700">{{ total.toFixed(2) }}</div>
        </div>

        <div class="rounded-[24px] bg-rose-50 p-5 ring-1 ring-rose-200">
          <div class="text-sm font-bold text-rose-700">المتبقي للمورد</div>
          <div class="mt-2 text-2xl font-black text-rose-700">{{ remainingAmount.toFixed(2) }}</div>
        </div>
      </section>

      <section
        v-if="needsPaymentInfo"
        class="rounded-[28px] bg-white p-5 ring-1 ring-emerald-200"
      >
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 class="text-lg font-black text-emerald-700">إيصال الصرف التلقائي</h3>
            <p class="mt-1 text-sm font-bold text-slate-500">
              سيتم إنشاء إيصال صرف تلقائي بعد حفظ الفاتورة وربطه بها.
            </p>
          </div>

          <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-center ring-1 ring-emerald-200">
            <div class="text-xs font-bold text-emerald-600">قيمة الصرف</div>
            <div class="mt-1 text-2xl font-black text-emerald-700">
              {{ Number(form.paid_amount || 0).toFixed(2) }}
            </div>
          </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
          <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
            <div class="text-xs font-bold text-slate-400">قيد فاتورة الشراء</div>
            <div class="mt-2 font-black text-emerald-700">المخزون ← المورد</div>
            <div class="mt-1 text-sm text-slate-500">{{ total.toFixed(2) }}</div>
          </div>

          <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
            <div class="text-xs font-bold text-emerald-600">قيد الصرف</div>
            <div class="mt-2 font-black text-emerald-700">المورد ← خزينة / بنك</div>
            <div class="mt-1 text-sm text-slate-500">{{ Number(form.paid_amount || 0).toFixed(2) }}</div>
          </div>
        </div>
      </section>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <BaseButton
          label="حفظ فاتورة الشراء"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>