<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  order: Object,
  warehouses: Array,
  customers: Array,
  products: Array,
})

const warehouseOptions = computed(() =>
  props.warehouses.map((w) => ({ value: w.id, label: w.name }))
)

const customerOptions = computed(() =>
  props.customers.map((c) => ({ value: c.id, label: `${c.name} - ${c.phone ?? ''}` }))
)

const productOptions = computed(() =>
  props.products.map((p) => ({ value: p.id, label: `${p.name} - ${p.product_code}` }))
)

const form = useForm({
  order_number: props.order.order_number ?? '',
  warehouse_id: props.order.warehouse_id ?? '',
  customer_id: props.order.customer_id ?? '',
  order_date: props.order.order_date ? String(props.order.order_date).slice(0, 10) : new Date().toISOString().slice(0, 10),
  discount_amount: props.order.discount_amount ?? 0,
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

const subtotal = computed(() =>
  form.items.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.unit_price || 0)), 0)
)

const total = computed(() => subtotal.value - Number(form.discount_amount || 0))

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
    hero-description="عند حفظ التعديل سيقوم النظام بإرجاع الكميات القديمة للمخزون ثم إعادة السحب بنظام FIFO من جديد."
    hero-back-href="/orders"
    hero-gradient-class="bg-gradient-to-br from-indigo-700 via-purple-700 to-slate-900"
    card-title="بيانات الفاتورة"
    @submit="submit"
  >
    <div class="space-y-6 p-6">
      <section class="rounded-[28px] bg-amber-50 p-5 ring-1 ring-amber-200">
        <h3 class="text-lg font-black text-amber-800">تنبيه مهم</h3>
        <p class="mt-2 text-sm font-bold leading-7 text-amber-700">
          تعديل فاتورة البيع يعيد احتساب المخزون والتكلفة والربح بالكامل. لو تغيرت الكميات أو المنتجات، سيتم تطبيق FIFO من جديد.
        </p>
      </section>

      <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">رقم الفاتورة</label>
          <FormControl v-model="form.order_number" type="text" />
          <div v-if="form.errors.order_number" class="mt-1 text-sm text-red-600">{{ form.errors.order_number }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">تاريخ الفاتورة</label>
          <FormControl v-model="form.order_date" type="date" />
          <div v-if="form.errors.order_date" class="mt-1 text-sm text-red-600">{{ form.errors.order_date }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">العميل</label>
          <FormControl v-model="form.customer_id" :options="customerOptions" />
          <div v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">{{ form.errors.customer_id }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">المخزن</label>
          <FormControl v-model="form.warehouse_id" :options="warehouseOptions" />
          <div v-if="form.errors.warehouse_id" class="mt-1 text-sm text-red-600">{{ form.errors.warehouse_id }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">حالة الدفع</label>
          <select
            v-model="form.payment_status"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
          >
            <option value="paid">مدفوعة</option>
            <option value="due">دين</option>
            <option value="partial">مدفوعة جزئيًا</option>
          </select>
          <div v-if="form.errors.payment_status" class="mt-1 text-sm text-red-600">{{ form.errors.payment_status }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">الخصم</label>
          <FormControl v-model="form.discount_amount" type="number" />
          <div v-if="form.errors.discount_amount" class="mt-1 text-sm text-red-600">{{ form.errors.discount_amount }}</div>
        </div>

        <div class="md:col-span-2 xl:col-span-3">
          <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
          <FormControl v-model="form.notes" type="textarea" />
        </div>
      </section>

      <section class="rounded-[28px] bg-white ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <div>
            <h3 class="text-lg font-black text-slate-800">بنود الفاتورة</h3>
            <p class="mt-1 text-xs text-slate-400">اختاري المنتج والكمية وسعر البيع لكل بند</p>
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
                  <FormControl v-model="item.quantity" type="number" />
                  <div v-if="form.errors[`items.${index}.quantity`]" class="mt-1 text-xs text-red-600">
                    {{ form.errors[`items.${index}.quantity`] }}
                  </div>
                </td>

                <td class="min-w-[140px] px-4 py-3">
                  <FormControl v-model="item.unit_price" type="number" />
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

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[24px] bg-slate-50 p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الإجمالي قبل الخصم</div>
          <div class="mt-2 text-2xl font-black text-slate-800">{{ subtotal.toFixed(2) }}</div>
        </div>

        <div class="rounded-[24px] bg-rose-50 p-5 ring-1 ring-rose-200">
          <div class="text-sm font-bold text-rose-700">الخصم</div>
          <div class="mt-2 text-2xl font-black text-rose-700">
            {{ Number(form.discount_amount || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-indigo-50 p-5 ring-1 ring-indigo-200">
          <div class="text-sm font-bold text-indigo-700">الإجمالي النهائي</div>
          <div class="mt-2 text-2xl font-black text-indigo-700">{{ total.toFixed(2) }}</div>
        </div>
      </section>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
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