<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  product: Object,
  categories: Array,
})

const categoryOptions = computed(() => props.categories.map((category) => ({
  value: category.id,
  label: category.name,
})))

const form = useForm({
  category_id: props.product.category_id ?? '',
  name: props.product.name ?? '',
  product_code: props.product.product_code ?? '',
  barcode: props.product.barcode ?? '',
  unit_name: props.product.unit_name ?? 'قطعة',
  image_path: props.product.image_path ?? '',
  current_price: props.product.current_price ?? 0,
  last_purchase_price: props.product.last_purchase_price ?? 0,
  minimum_stock: props.product.minimum_stock ?? 0,
  is_service: !!props.product.is_service,
  is_active: !!props.product.is_active,
  notes: props.product.notes ?? '',
})

const submit = () => {
  form.put(`/products/${props.product.id}`)
}
</script>

<template>
  <MainLayout title="تعديل منتج">
    <div class="space-y-6">
      <section class="rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-black text-slate-800">تعديل المنتج</h1>
            <p class="mt-1 text-sm text-slate-500">عدلي بيانات المنتج: {{ props.product.name }}</p>
          </div>

          <Link href="/products">
            <BaseButton label="رجوع" color="light" />
          </Link>
        </div>
      </section>

      <CardBox is-form @submit.prevent="submit">
        <CardBoxComponentHeader title="بيانات المنتج" />

        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الفئة</label>
            <FormControl v-model="form.category_id" :options="categoryOptions" />
            <div v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">اسم المنتج</label>
            <FormControl v-model="form.name" type="text" />
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">رمز المنتج</label>
            <FormControl v-model="form.product_code" type="text" />
            <div v-if="form.errors.product_code" class="mt-1 text-sm text-red-600">{{ form.errors.product_code }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الباركود</label>
            <FormControl v-model="form.barcode" type="text" />
            <div v-if="form.errors.barcode" class="mt-1 text-sm text-red-600">{{ form.errors.barcode }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الوحدة</label>
            <FormControl v-model="form.unit_name" type="text" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">مسار الصورة</label>
            <FormControl v-model="form.image_path" type="text" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">سعر البيع الحالي</label>
            <FormControl v-model="form.current_price" type="number" />
            <div v-if="form.errors.current_price" class="mt-1 text-sm text-red-600">{{ form.errors.current_price }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">آخر سعر شراء</label>
            <FormControl v-model="form.last_purchase_price" type="number" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الحد الأدنى للمخزون</label>
            <FormControl v-model="form.minimum_stock" type="number" />
          </div>

          <div class="md:col-span-2 xl:col-span-3">
            <label class="mb-2 block text-sm font-bold text-slate-700">ملاحظات</label>
            <FormControl v-model="form.notes" type="textarea" />
          </div>

          <div class="flex items-center gap-3">
            <input id="is_service" v-model="form.is_service" type="checkbox" />
            <label for="is_service" class="text-sm font-semibold text-slate-700">هذا المنتج خدمة</label>
          </div>

          <div class="flex items-center gap-3">
            <input id="is_active" v-model="form.is_active" type="checkbox" />
            <label for="is_active" class="text-sm font-semibold text-slate-700">المنتج فعال</label>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-end gap-3">
            <Link href="/products">
              <BaseButton label="إلغاء" color="light" />
            </Link>
            <BaseButton label="حفظ التعديل" color="warning" type="submit" :disabled="form.processing" />
          </div>
        </template>
      </CardBox>
    </div>
  </MainLayout>
</template>