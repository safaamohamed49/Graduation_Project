<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  branches: Array,
  warehouses: Array,
  suppliers: Array,
  products: Array,
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
  label: item.name,
})))

const productOptions = computed(() => props.products.map((item) => ({
  value: item.id,
  label: `${item.name} - ${item.product_code}`,
})))

const form = useForm({
  invoice_number: '',
  branch_id: props.branches[0]?.id ?? '',
  warehouse_id: props.warehouses[0]?.id ?? '',
  supplier_id: '',
  invoice_date: new Date().toISOString().slice(0, 10),
  discount_amount: 0,
  total_expenses: 0,
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
       'Accept': 'application/json',
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
    hero-description="أدخلي بيانات فاتورة الشراء يدويًا أو ارفعي صورة واضحة للفاتورة لتجهيز مسودة قابلة للمراجعة."
    hero-back-href="/dashboard"
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
          <div v-if="form.errors.invoice_number" class="mt-1 text-sm text-red-600">{{ form.errors.invoice_number }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">تاريخ الفاتورة</label>
          <FormControl v-model="form.invoice_date" type="date" />
          <div v-if="form.errors.invoice_date" class="mt-1 text-sm text-red-600">{{ form.errors.invoice_date }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">المورد</label>
          <FormControl v-model="form.supplier_id" :options="supplierOptions" />
          <div v-if="form.errors.supplier_id" class="mt-1 text-sm text-red-600">{{ form.errors.supplier_id }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
          <FormControl v-model="form.branch_id" :options="branchOptions" />
          <div v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">{{ form.errors.branch_id }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">المخزن</label>
          <FormControl v-model="form.warehouse_id" :options="warehouseOptions" />
          <div v-if="form.errors.warehouse_id" class="mt-1 text-sm text-red-600">{{ form.errors.warehouse_id }}</div>
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
            <p class="mt-1 text-xs text-slate-400">اختاري المنتج والكمية وسعر الشراء لكل بند</p>
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
                </td>

                <td class="min-w-[140px] px-4 py-3">
                  <FormControl v-model="item.price" type="number" />
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
        <BaseButton label="حفظ فاتورة الشراء" color="primary" type="submit" :disabled="form.processing" />
      </div>
    </template>
  </EntityFormShell>
</template>