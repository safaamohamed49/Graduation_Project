<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  orderNumber: String,
  warehouses: Array,
  customers: Array,
  products: Array,
})

const imageLoading = ref(false)
const imageMessage = ref('')
const imageError = ref('')

const warehouseOptions = computed(() =>
  props.warehouses.map(w => ({
    value: w.id,
    label: w.name,
  }))
)

const customerOptions = computed(() =>
  props.customers.map(c => ({
    value: c.id,
    label: `${c.name} - ${c.phone ?? ''}`,
  }))
)

const productOptions = computed(() =>
  props.products.map(p => ({
    value: p.id,
    label: `${p.name} - ${p.product_code}`,
  }))
)

const form = useForm({
  order_number: props.orderNumber,
  warehouse_id: props.warehouses[0]?.id ?? '',
  customer_id: '',
  order_date: new Date().toISOString().slice(0, 10),
  discount_amount: 0,
  payment_status: 'due',
  notes: '',
  items: [
    {
      product_id: '',
      quantity: 1,
      unit_price: 0,
      notes: '',
    },
  ],
})

const subtotal = computed(() =>
  form.items.reduce((sum, i) => sum + (i.quantity * i.unit_price), 0)
)

const total = computed(() =>
  subtotal.value - Number(form.discount_amount || 0)
)

const addItem = () => {
  form.items.push({
    product_id: '',
    quantity: 1,
    unit_price: 0,
    notes: '',
  })
}

const removeItem = (i) => {
  if (form.items.length === 1) return
  form.items.splice(i, 1)
}

const productSelected = (index) => {
  const product = props.products.find(p => p.id == form.items[index].product_id)
  if (!product) return

  form.items[index].unit_price = product.current_price ?? 0
}

const submit = () => {
  form.post('/orders')
}

const handleImage = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  imageLoading.value = true
  imageError.value = ''
  imageMessage.value = ''

  const fd = new FormData()
  fd.append('image', file)

  try {
    const res = await fetch('/orders/extract-image', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': window.Laravel.csrfToken,
        Accept: 'application/json',
      },
      body: fd,
    })

    const data = await res.json()

    if (!data.success) {
      imageError.value = data.message
      return
    }

    imageMessage.value = data.message

    form.order_number = data.draft.order_number || form.order_number
    form.order_date = data.draft.order_date || form.order_date
    form.discount_amount = data.draft.discount_amount || 0

    form.items = data.draft.items.map(i => ({
      product_id: i.product_id ?? '',
      quantity: i.quantity ?? 1,
      unit_price: i.unit_price ?? 0,
      notes: i.notes ?? '',
    }))

  } catch {
    imageError.value = 'خطأ في تحليل الصورة'
  } finally {
    imageLoading.value = false
  }
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة فاتورة بيع"
    hero-badge="المبيعات / إضافة"
    hero-title="فاتورة بيع جديدة"
    hero-description="أدخلي بيانات البيع أو استخدمي الذكاء لقراءة الفاتورة من صورة."
    hero-back-href="/orders"
    hero-gradient-class="bg-gradient-to-br from-indigo-700 via-purple-700 to-slate-900"
    card-title="بيانات الفاتورة"
    @submit="submit"
  >
    <div class="space-y-6 p-6">

      <!-- AI IMAGE -->
      <section class="rounded-2xl bg-indigo-50 p-4">
        <div class="flex justify-between">
          <div>
            <h3 class="font-black">إدخال من صورة</h3>
            <p class="text-sm">ارفع صورة فاتورة بيع وسيتم تعبئتها</p>
          </div>

          <label class="bg-black text-white px-4 py-2 rounded-xl cursor-pointer">
            {{ imageLoading ? 'جاري...' : 'رفع صورة' }}
            <input type="file" class="hidden" @change="handleImage" />
          </label>
        </div>

        <div v-if="imageMessage" class="text-green-600 mt-2">{{ imageMessage }}</div>
        <div v-if="imageError" class="text-red-600 mt-2">{{ imageError }}</div>
      </section>

      <!-- DATA -->
      <section class="grid md:grid-cols-3 gap-4">

        <FormControl v-model="form.order_number" label="رقم الفاتورة" />
        <FormControl v-model="form.order_date" type="date" label="التاريخ" />

        <FormControl v-model="form.customer_id" :options="customerOptions" label="العميل" />
        <FormControl v-model="form.warehouse_id" :options="warehouseOptions" label="المخزن" />

        <FormControl v-model="form.discount_amount" type="number" label="الخصم" />

      </section>

      <!-- ITEMS -->
      <section class="bg-white rounded-2xl p-4">
        <div class="flex justify-between mb-3">
          <h3 class="font-black">البنود</h3>
          <BaseButton label="إضافة" @click="addItem" />
        </div>

        <table class="w-full text-right">
          <thead>
            <tr>
              <th>منتج</th>
              <th>كمية</th>
              <th>سعر</th>
              <th>الإجمالي</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(item,i) in form.items" :key="i">
              <td>
                <FormControl
                  v-model="item.product_id"
                  :options="productOptions"
                  @change="productSelected(i)"
                />
              </td>

              <td><FormControl v-model="item.quantity" type="number" /></td>
              <td><FormControl v-model="item.unit_price" type="number" /></td>

              <td>{{ (item.quantity * item.unit_price).toFixed(2) }}</td>

              <td>
                <BaseButton label="X" color="danger" @click="removeItem(i)" />
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- TOTAL -->
      <section class="grid md:grid-cols-3 gap-4">
        <div class="bg-gray-100 p-4 rounded-xl">
          <div>الإجمالي</div>
          <div class="font-black text-xl">{{ subtotal.toFixed(2) }}</div>
        </div>

        <div class="bg-gray-100 p-4 rounded-xl">
          <div>الخصم</div>
          <div class="font-black text-red-600">{{ form.discount_amount }}</div>
        </div>

        <div class="bg-green-100 p-4 rounded-xl">
          <div>الصافي</div>
          <div class="font-black text-green-700 text-xl">{{ total.toFixed(2) }}</div>
        </div>
      </section>

    </div>

    <template #footer>
      <BaseButton label="حفظ الفاتورة" type="submit" />
    </template>
  </EntityFormShell>
</template>