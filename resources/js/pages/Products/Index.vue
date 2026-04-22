<script setup>
import { ref, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  products: Object,
  categories: Array,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const categoryId = ref(props.filters?.category_id ?? '')
const status = ref(props.filters?.status ?? 'active')

const categoryOptions = computed(() => [
  { value: '', label: 'كل الفئات' },
  ...props.categories.map((category) => ({
    value: category.id,
    label: category.name,
  })),
])

watch([search, categoryId, status], () => {
  router.get(
    '/products',
    {
      search: search.value,
      category_id: categoryId.value,
      status: status.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
})

const productsData = computed(() => props.products?.data ?? [])

const deleteProduct = (product) => {
  if (!confirm(`هل أنت متأكدة من حذف المنتج "${product.name}"؟`)) return

  router.delete(`/products/${product.id}`, {
    preserveScroll: true,
  })
}

const restoreProduct = (product) => {
  if (!confirm(`هل تريدين استرجاع المنتج "${product.name}"؟`)) return

  router.post(`/products/${product.id}/restore`, {}, {
    preserveScroll: true,
  })
}
</script>

<template>
  <MainLayout title="المنتجات">
    <div class="space-y-6">
      <section class="rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h1 class="text-2xl font-black text-slate-800">إدارة المنتجات</h1>
            <p class="mt-1 text-sm text-slate-500">عرض وإدارة المنتجات والأسعار والأكواد</p>
          </div>

          <div class="grid gap-3 md:grid-cols-3 xl:min-w-[820px]">
            <FormControl v-model="search" type="text" placeholder="ابحثي بالاسم أو الكود أو الباركود" />
            <FormControl v-model="categoryId" :options="categoryOptions" />
            <FormControl
              v-model="status"
              :options="[
                { value: 'active', label: 'الفعالة' },
                { value: 'deleted', label: 'المحذوفة' },
                { value: 'all', label: 'الكل' },
              ]"
            />
          </div>

          <Link href="/products/create">
            <BaseButton label="إضافة منتج" color="primary" />
          </Link>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="قائمة المنتجات" />
        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-3 font-bold">#</th>
                <th class="px-4 py-3 font-bold">الصورة</th>
                <th class="px-4 py-3 font-bold">الاسم</th>
                <th class="px-4 py-3 font-bold">الكود</th>
                <th class="px-4 py-3 font-bold">الباركود</th>
                <th class="px-4 py-3 font-bold">الفئة</th>
                <th class="px-4 py-3 font-bold">الوحدة</th>
                <th class="px-4 py-3 font-bold">السعر</th>
                <th class="px-4 py-3 font-bold">آخر شراء</th>
                <th class="px-4 py-3 font-bold">الحد الأدنى</th>
                <th class="px-4 py-3 font-bold">الحالة</th>
                <th class="px-4 py-3 font-bold">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(product, index) in productsData"
                :key="product.id"
                class="border-t text-sm text-slate-700"
              >
                <td class="px-4 py-3">
                  {{ ((props.products.current_page - 1) * props.products.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-3">
                  <div class="flex justify-center">
                    <img
                      v-if="product.image_path"
                      :src="`/storage/${product.image_path}`"
                      alt="product"
                      class="h-14 w-14 rounded-xl object-cover ring-1 ring-slate-200"
                    />
                    <div
                      v-else
                      class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-xs text-slate-500 ring-1 ring-slate-200"
                    >
                      لا يوجد
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3 font-semibold">
                  <div class="flex flex-col">
                    <span>{{ product.name }}</span>
                    <span v-if="product.is_service" class="text-xs text-violet-600">خدمة</span>
                  </div>
                </td>

                <td class="px-4 py-3">{{ product.product_code }}</td>
                <td class="px-4 py-3">{{ product.barcode || '-' }}</td>
                <td class="px-4 py-3">{{ product.category?.name || '-' }}</td>
                <td class="px-4 py-3">{{ product.unit_name }}</td>
                <td class="px-4 py-3 font-bold text-blue-700">{{ product.current_price }}</td>
                <td class="px-4 py-3">{{ product.last_purchase_price }}</td>
                <td class="px-4 py-3">{{ product.minimum_stock }}</td>
                <td class="px-4 py-3">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="product.is_deleted ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'"
                  >
                    {{ product.is_deleted ? 'محذوف' : 'نشط' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <Link v-if="!product.is_deleted" :href="`/products/${product.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      v-if="!product.is_deleted"
                      label="حذف"
                      color="danger"
                      small
                      @click="deleteProduct(product)"
                    />

                    <BaseButton
                      v-if="product.is_deleted"
                      label="استرجاع"
                      color="success"
                      small
                      @click="restoreProduct(product)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!productsData.length">
                <td colspan="12" class="px-4 py-10 text-center text-sm text-slate-500">
                  لا توجد منتجات مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.products.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.products.links"
          :key="link.label"
          class="rounded-xl border px-4 py-2 text-sm transition"
          :class="[
            link.active
              ? 'border-blue-600 bg-blue-600 text-white'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
            !link.url ? 'cursor-not-allowed opacity-50' : ''
          ]"
          :disabled="!link.url"
          @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })"
          v-html="link.label"
        />
      </section>
    </div>
  </MainLayout>
</template>