<script setup>
import { computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  order: Object,
  warehouses: Array,
  customers: Array,
  products: Array,
  financialAccounts: Array,
  paymentMethods: Array,
})

const warehouseOptions = computed(() =>
  props.warehouses.map((w) => ({
    value: w.id,
    label: `${w.name} - ${w.code ?? ''}`,
  }))
)

const customerOptions = computed(() =>
  props.customers.map((c) => ({
    value: c.id,
    label: `${c.name} - ${c.phone ?? c.code ?? ''}`,
  }))
)

const productOptions = computed(() =>
  props.products.map((p) => ({
    value: p.id,
    label: `${p.name} - ${p.product_code ?? ''}`,
  }))
)

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
  order_number: props.order.order_number ?? '',
  warehouse_id: props.order.warehouse_id ?? '',
  customer_id: props.order.customer_id ?? '',
  financial_account_id: props.order.financial_account_id ?? '',
  payment_method_id: props.order.payment_method_id ?? '',
  order_date: props.order.order_date
    ? String(props.order.order_date).slice(0, 10)
    : new Date().toISOString().slice(0, 10),
  discount_amount: props.order.discount_amount ?? 0,
  paid_amount: props.order.paid_amount ?? 0,
  payment_status: props.order.payment_status ?? 'due',
  status: props.order.status ?? 'posted',
  notes: props.order.notes ?? '',
  items: (props.order.items ?? []).map((item) => ({
    product_id: item.product_id ?? '',
    quantity: item.quantity ?? 1,
    unit_price: item.unit_price ?? 0,
    notes: item.notes ?? '',
  })),
})

const subtotal = computed(() => {
  return form.items.reduce((sum, item) => {
    return sum + Number(item.quantity || 0) * Number(item.unit_price || 0)
  }, 0)
})

const total = computed(() => {
  return Math.max(0, subtotal.value - Number(form.discount_amount || 0))
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
    unit_price: 0,
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

  form.items[index].unit_price = product.current_price ?? 0
}

const submit = () => {
  form.put(`/orders/${props.order.id}`)
}
</script>

<template>
  <EntityFormShell
    page-title="تعديل فاتورة بيع"
    hero-badge="المبيعات / تعديل"
    hero-title="تعديل فاتورة بيع"
    hero-description="عند حفظ التعديل سيقوم النظام بإرجاع الكميات القديمة للمخزون ثم إعادة السحب بنظام FIFO وإعادة بناء القيد المحاسبي وإعادة إنشاء إيصال القبض التلقائي حسب الدفعة الحالية."
    hero-back-href="/orders"
    hero-gradient-class="bg-gradient-to-br from-indigo-800 via-purple-800 to-slate-900"
    card-title="بيانات فاتورة البيع"
    @submit="submit"
  >
    <div class="space-y-6 p-6">
      <section class="rounded-[28px] bg-amber-50 p-5 ring-1 ring-amber-200">
        <h3 class="text-lg font-black text-amber-800">تنبيه مهم</h3>
        <p class="mt-2 text-sm font-bold leading-7 text-amber-700">
          تعديل فاتورة البيع يعيد احتساب المخزون والتكلفة والربح بالكامل. لو تغيرت المنتجات أو الكميات، سيتم تطبيق FIFO من جديد وتحديث القيود المحاسبية وإعادة إنشاء إيصال القبض التلقائي حسب الدفعة الحالية.
        </p>
      </section>

      <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">رقم الفاتورة</label>
          <FormControl v-model="form.order_number" type="text" />
          <div v-if="form.errors.order_number" class="mt-1 text-sm text-red-600">
            {{ form.errors.order_number }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">تاريخ الفاتورة</label>
          <FormControl v-model="form.order_date" type="date" />
          <div v-if="form.errors.order_date" class="mt-1 text-sm text-red-600">
            {{ form.errors.order_date }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">العميل</label>
          <FormControl v-model="form.customer_id" :options="customerOptions" />
          <p class="mt-1 text-xs font-bold text-slate-400">
            اتـركيه فارغًا لو الفاتورة نقدية لعميل نقدي.
          </p>
          <div v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.customer_id }}
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
            حالة التحصيل الأولي
          </label>

          <select
            v-model="form.payment_status"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
          >
            <option value="paid">تحصيل كامل وإنشاء إيصال قبض</option>
            <option value="due">بدون تحصيل الآن</option>
            <option value="partial">تحصيل جزئي وإنشاء إيصال قبض</option>
          </select>

          <p class="mt-1 text-xs font-bold text-indigo-500">
            تعديل الدفعة سيقوم بإعادة إنشاء إيصال القبض التلقائي المرتبط بالفاتورة.
          </p>

          <div v-if="form.errors.payment_status" class="mt-1 text-sm text-red-600">
            {{ form.errors.payment_status }}
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
          <label class="mb-2 block text-sm font-black text-slate-700">طريقة الدفع</label>
          <FormControl v-model="form.payment_method_id" :options="paymentMethodOptions" />
          <div v-if="form.errors.payment_method_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.payment_method_id }}
          </div>
        </div>

        <div v-if="form.payment_status === 'partial'">
          <label class="mb-2 block text-sm font-black text-slate-700">
            الدفعة الأولى
          </label>

          <FormControl v-model="form.paid_amount" type="number" step="0.01" />

          <p class="mt-1 text-xs font-bold text-emerald-600">
            سيتم إنشاء إيصال قبض تلقائي بهذه القيمة بعد حفظ التعديل.
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
              اختاري المنتج والكمية وسعر البيع لكل بند. سيتم خصم الكمية من المخزون بنظام FIFO.
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
                <th class="px-4 py-3">سعر البيع</th>
                <th class="px-4 py-3">الإجمالي</th>
                <th class="px-4 py-3">ملاحظات</th>
                <th class="px-4 py-3">حذف</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(item, index) in form.items" :key="index" class="border-t">
                <td class="min-w-[280px] px-4 py-3">
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
                  <FormControl v-model="item.unit_price" type="number" step="0.01" />

                  <div v-if="form.errors[`items.${index}.unit_price`]" class="mt-1 text-xs text-red-600">
                    {{ form.errors[`items.${index}.unit_price`] }}
                  </div>
                </td>

                <td class="px-4 py-3 font-black text-slate-800">
                  {{ (Number(item.quantity || 0) * Number(item.unit_price || 0)).toFixed(2) }}
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
          <div class="mt-2 text-2xl font-black text-slate-800">
            {{ subtotal.toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-rose-50 p-5 ring-1 ring-rose-200">
          <div class="text-sm font-bold text-rose-700">الخصم</div>
          <div class="mt-2 text-2xl font-black text-rose-700">
            {{ Number(form.discount_amount || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-indigo-50 p-5 ring-1 ring-indigo-200">
          <div class="text-sm font-bold text-indigo-700">الصافي</div>
          <div class="mt-2 text-2xl font-black text-indigo-700">
            {{ total.toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-emerald-50 p-5 ring-1 ring-emerald-200">
          <div class="text-sm font-bold text-emerald-700">المتبقي دين</div>
          <div class="mt-2 text-2xl font-black text-emerald-700">
            {{ remainingAmount.toFixed(2) }}
          </div>
        </div>
      </section>

      <section
        v-if="needsPaymentInfo"
        class="rounded-[28px] bg-white p-5 ring-1 ring-emerald-200"
      >
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 class="text-lg font-black text-emerald-700">
              إيصال القبض التلقائي
            </h3>

            <p class="mt-1 text-sm font-bold text-slate-500">
              سيتم إعادة إنشاء إيصال قبض تلقائي بعد حفظ التعديل.
            </p>
          </div>

          <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-center ring-1 ring-emerald-200">
            <div class="text-xs font-bold text-emerald-600">
              قيمة التحصيل
            </div>

            <div class="mt-1 text-2xl font-black text-emerald-700">
              {{ Number(form.paid_amount || 0).toFixed(2) }}
            </div>
          </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
          <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
            <div class="text-xs font-bold text-slate-400">
              قيد المبيعات
            </div>

            <div class="mt-2 font-black text-indigo-700">
              حساب العميل
            </div>

            <div class="mt-1 text-sm text-slate-500">
              {{ total.toFixed(2) }}
            </div>
          </div>

          <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
            <div class="text-xs font-bold text-emerald-600">
              قيد التحصيل
            </div>

            <div class="mt-2 font-black text-emerald-700">
              خزينة / بنك ← عميل
            </div>

            <div class="mt-1 text-sm text-slate-500">
              {{ Number(form.paid_amount || 0).toFixed(2) }}
            </div>
          </div>
        </div>
      </section>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link :href="`/orders/${props.order.id}`">
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