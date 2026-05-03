<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  invoice: Object,
  branches: Array,
  warehouses: Array,
  suppliers: Array,
  products: Array,
})

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
  label: item.name,
})))

const productOptions = computed(() => props.products.map((item) => ({
  value: item.id,
  label: `${item.name} - ${item.product_code}`,
})))

const form = useForm({
  invoice_number: props.invoice.invoice_number ?? '',
  branch_id: props.invoice.branch_id ?? '',
  warehouse_id: props.invoice.warehouse_id ?? '',
  supplier_id: props.invoice.supplier_id ?? '',
  invoice_date: props.invoice.invoice_date
    ? String(props.invoice.invoice_date).slice(0, 10)
    : new Date().toISOString().slice(0, 10),
  discount_amount: props.invoice.discount_amount ?? 0,
  total_expenses: props.invoice.total_expenses ?? 0,
  notes: props.invoice.notes ?? '',
  items: (props.invoice.items ?? []).map((item) => ({
    product_id: item.product_id ?? '',
    quantity: item.quantity ?? 1,
    price: item.price ?? 0,
    notes: item.notes ?? '',
  })),
})

const subtotal = computed(() => {
  return form.items.reduce((sum, item) => {
    return sum + (Number(item.quantity || 0) * Number(item.price || 0))
  }, 0)
})

const total = computed(() => {
  return subtotal.value - Number(form.discount_amount || 0) + Number(form.total_expenses || 0)
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
  form.put(`/purchase-invoices/${props.invoice.id}`)
}
</script>

<template>
  <EntityFormShell
    page-title="تعديل فاتورة شراء"
    hero-badge="المشتريات / تعديل"
    hero-title="تعديل فاتورة شراء"
    :hero-description="`تعديل بيانات فاتورة الشراء رقم ${props.invoice.invoice_number}. لا يمكن تعديل الفاتورة إذا استُخدمت كمياتها في فواتير بيع.`"
    hero-back-href="/purchase-invoices"
    hero-gradient-class="bg-gradient-to-br from-amber-500 via-orange-600 to-rose-700"
    card-title="بيانات فاتورة الشراء"
    @submit="submit"
  >
    <div class="space-y-6 p-6">
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
          <label class="mb-2 block text-sm font-black text-slate-700">مصاريف إضافية</label>
          <FormControl v-model="form.total_expenses" type="number" />
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">الخصم</label>
          <FormControl v-model="form.discount_amount" type="number" />
        </div>

        <div class="md:col-span-2">
          <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
          <FormControl v-model="form.notes" type="textarea" />
        </div>
      </section>

      <section class="rounded-[28px] bg-white ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <div>
            <h3 class="text-lg font-black text-slate-800">بنود الفاتورة</h3>
            <p class="mt-1 text-xs text-slate-400">تعديل المنتجات والكميات وأسعار الشراء</p>
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
                  <FormControl v-model="item.quantity" type="number" />
                  <div v-if="form.errors[`items.${index}.quantity`]" class="mt-1 text-xs text-red-600">
                    {{ form.errors[`items.${index}.quantity`] }}
                  </div>
                </td>

                <td class="min-w-[140px] px-4 py-3">
                  <FormControl v-model="item.price" type="number" />
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

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[24px] bg-slate-50 p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الإجمالي قبل الخصم</div>
          <div class="mt-2 text-2xl font-black text-slate-800">{{ subtotal.toFixed(2) }}</div>
        </div>

        <div class="rounded-[24px] bg-slate-50 p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المصاريف والخصم</div>
          <div class="mt-2 text-2xl font-black text-amber-600">
            +{{ Number(form.total_expenses || 0).toFixed(2) }} / -{{ Number(form.discount_amount || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-emerald-50 p-5 ring-1 ring-emerald-200">
          <div class="text-sm font-bold text-emerald-700">الإجمالي النهائي</div>
          <div class="mt-2 text-2xl font-black text-emerald-700">{{ total.toFixed(2) }}</div>
        </div>
      </section>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/purchase-invoices">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ التعديل"
          color="warning"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>