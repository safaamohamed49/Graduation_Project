<script setup>
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  warehouses: Array,
  warehouseProductStocks: Array,
})

const form = useForm({
  from_warehouse_id: '',
  to_warehouse_id: '',
  transfer_date: new Date().toISOString().slice(0, 10),
  notes: '',
  items: [],
})

const fromWarehouse = computed(() => {
  return props.warehouses.find((warehouse) => Number(warehouse.id) === Number(form.from_warehouse_id))
})

const toWarehouse = computed(() => {
  return props.warehouses.find((warehouse) => Number(warehouse.id) === Number(form.to_warehouse_id))
})

const availableDestinationWarehouses = computed(() => {
  return props.warehouses.filter((warehouse) => Number(warehouse.id) !== Number(form.from_warehouse_id))
})

const availableProducts = computed(() => {
  if (!form.from_warehouse_id) return []

  return props.warehouseProductStocks.filter((item) => {
    return Number(item.warehouse_id) === Number(form.from_warehouse_id)
  })
})

const canAddItem = computed(() => {
  return Boolean(form.from_warehouse_id) && availableProducts.value.length > 0
})

const totalQuantity = computed(() => {
  return form.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
})

const selectedProductIds = computed(() => {
  return form.items
    .map((item) => Number(item.product_id))
    .filter((id) => id > 0)
})

const productOptionsForRow = (rowIndex) => {
  const currentProductId = Number(form.items[rowIndex]?.product_id || 0)

  return availableProducts.value.filter((product) => {
    const productId = Number(product.product_id)

    return productId === currentProductId || !selectedProductIds.value.includes(productId)
  })
}

const productStock = (productId) => {
  const product = availableProducts.value.find((item) => {
    return Number(item.product_id) === Number(productId)
  })

  return Number(product?.available_quantity || 0)
}

const productName = (productId) => {
  const product = availableProducts.value.find((item) => {
    return Number(item.product_id) === Number(productId)
  })

  return product?.product_name || '-'
}

const addItem = () => {
  if (!canAddItem.value) return

  form.items.push({
    product_id: '',
    quantity: '',
    notes: '',
  })
}

const removeItem = (index) => {
  form.items.splice(index, 1)
}

const fillMaxQuantity = (index) => {
  const productId = form.items[index]?.product_id

  if (!productId) return

  form.items[index].quantity = productStock(productId)
}

const rowHasQuantityWarning = (item) => {
  if (!item.product_id || !item.quantity) return false

  return Number(item.quantity) > productStock(item.product_id)
}

watch(
  () => form.from_warehouse_id,
  () => {
    form.items = []

    if (Number(form.to_warehouse_id) === Number(form.from_warehouse_id)) {
      form.to_warehouse_id = ''
    }
  }
)

const submit = () => {
  form.post('/stock-transfers')
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة تحويل مخزني"
    hero-badge="المخازن / تحويل"
    hero-title="تحويل منتجات بين المخازن"
    hero-description="اختاري المخزن المصدر والمخزن المستلم، ثم أضيفي المنتجات والكميات المراد نقلها. سيتم الخصم والإضافة بنظام FIFO."
    hero-back-href="/stock-transfers"
    hero-gradient-class="bg-gradient-to-br from-cyan-900 via-slate-900 to-blue-900"
    card-title="بيانات التحويل"
    @submit="submit"
  >
    <div class="space-y-6 p-6">
      <div class="grid gap-5 md:grid-cols-2">
        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">تاريخ التحويل</label>
          <FormControl v-model="form.transfer_date" type="date" />
          <div v-if="form.errors.transfer_date" class="mt-1 text-sm text-red-600">
            {{ form.errors.transfer_date }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">المخزن المصدر</label>
          <select
            v-model="form.from_warehouse_id"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
          >
            <option value="">اختاري المخزن المصدر</option>
            <option v-for="warehouse in props.warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }} - {{ warehouse.code }} / {{ warehouse.branch?.name || 'بدون فرع' }}
            </option>
          </select>
          <div v-if="form.errors.from_warehouse_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.from_warehouse_id }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">المخزن المستلم</label>
          <select
            v-model="form.to_warehouse_id"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
          >
            <option value="">اختاري المخزن المستلم</option>
            <option
              v-for="warehouse in availableDestinationWarehouses"
              :key="warehouse.id"
              :value="warehouse.id"
            >
              {{ warehouse.name }} - {{ warehouse.code }} / {{ warehouse.branch?.name || 'بدون فرع' }}
            </option>
          </select>
          <div v-if="form.errors.to_warehouse_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.to_warehouse_id }}
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">ملخص التحويل</label>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 ring-1 ring-slate-200">
            <div>من: {{ fromWarehouse?.name || '-' }}</div>
            <div class="mt-1">إلى: {{ toWarehouse?.name || '-' }}</div>
          </div>
        </div>

        <div class="md:col-span-2">
          <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            placeholder="مثال: تحويل بضاعة من المخزن الرئيسي إلى مخزن الفرع..."
          />
          <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
            {{ form.errors.notes }}
          </div>
        </div>
      </div>

      <div class="rounded-[28px] bg-slate-50 p-5 ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <div class="text-base font-black text-slate-800">المنتجات المراد تحويلها</div>
            <div class="mt-1 text-sm text-slate-500">
              لا يمكن إضافة كمية أكبر من المتاح في المخزن المصدر.
            </div>
          </div>

          <BaseButton
            label="إضافة منتج"
            color="info"
            :disabled="!canAddItem"
            @click="addItem"
          />
        </div>

        <div v-if="!form.from_warehouse_id" class="mt-5 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">
          اختاري المخزن المصدر أولاً حتى تظهر المنتجات المتاحة.
        </div>

        <div v-else-if="!availableProducts.length" class="mt-5 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
          لا توجد كميات متاحة داخل هذا المخزن.
        </div>

        <div v-if="form.errors.items" class="mt-4 text-sm font-bold text-red-600">
          {{ form.errors.items }}
        </div>

        <div class="mt-5 overflow-x-auto">
          <table class="min-w-full text-right">
            <thead>
              <tr class="text-sm text-slate-600">
                <th class="px-3 py-3 font-black">المنتج</th>
                <th class="px-3 py-3 font-black">المتاح</th>
                <th class="px-3 py-3 font-black">الكمية</th>
                <th class="px-3 py-3 font-black">ملاحظات</th>
                <th class="px-3 py-3 font-black">إجراء</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(item, index) in form.items"
                :key="index"
                class="border-t border-slate-200"
              >
                <td class="px-3 py-3">
                  <select
                    v-model="item.product_id"
                    class="w-72 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                  >
                    <option value="">اختاري المنتج</option>
                    <option
                      v-for="product in productOptionsForRow(index)"
                      :key="product.product_id"
                      :value="product.product_id"
                    >
                      {{ product.product_name }} - {{ product.product_code || '' }}
                    </option>
                  </select>

                  <div v-if="form.errors[`items.${index}.product_id`]" class="mt-1 text-sm text-red-600">
                    {{ form.errors[`items.${index}.product_id`] }}
                  </div>
                </td>

                <td class="px-3 py-3 font-black text-emerald-700">
                  {{ productStock(item.product_id).toFixed(2) }}
                </td>

                <td class="px-3 py-3">
                  <div class="flex items-center gap-2">
                    <input
                      v-model="item.quantity"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-32 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                      placeholder="0.00"
                    />

                    <button
                      type="button"
                      class="rounded-xl bg-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-300"
                      @click="fillMaxQuantity(index)"
                    >
                      الكل
                    </button>
                  </div>

                  <div v-if="rowHasQuantityWarning(item)" class="mt-1 text-sm font-bold text-red-600">
                    الكمية أكبر من المتاح للمنتج: {{ productName(item.product_id) }}
                  </div>

                  <div v-if="form.errors[`items.${index}.quantity`]" class="mt-1 text-sm text-red-600">
                    {{ form.errors[`items.${index}.quantity`] }}
                  </div>
                </td>

                <td class="px-3 py-3">
                  <input
                    v-model="item.notes"
                    type="text"
                    class="w-64 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                    placeholder="ملاحظات للمنتج"
                  />
                </td>

                <td class="px-3 py-3">
                  <BaseButton
                    label="حذف"
                    color="danger"
                    small
                    @click="removeItem(index)"
                  />
                </td>
              </tr>

              <tr v-if="!form.items.length">
                <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">
                  لم يتم إضافة منتجات للتحويل بعد.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد المنتجات</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ form.items.length }}</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الكميات</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">{{ totalQuantity.toFixed(2) }}</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">طريقة الخصم</div>
          <div class="mt-3 text-2xl font-black text-emerald-700">FIFO</div>
          <div class="mt-2 text-xs text-slate-400">الأقدم يخرج أولاً</div>
        </div>
      </section>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/stock-transfers">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ وتنفيذ التحويل"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>