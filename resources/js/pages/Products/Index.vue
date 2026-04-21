<script setup>
import { computed, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
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

const showCreateModal = ref(false)
const showEditModal = ref(false)

const createForm = useForm({
  category_id: '',
  name: '',
  product_code: '',
  barcode: '',
  unit_name: 'قطعة',
  image_path: '',
  current_price: 0,
  last_purchase_price: 0,
  minimum_stock: 0,
  is_service: false,
  is_active: true,
  notes: '',
})

const editForm = useForm({
  id: null,
  category_id: '',
  name: '',
  product_code: '',
  barcode: '',
  unit_name: 'قطعة',
  image_path: '',
  current_price: 0,
  last_purchase_price: 0,
  minimum_stock: 0,
  is_service: false,
  is_active: true,
  notes: '',
})

const applyFilters = () => {
  router.get(
    route('products.index'),
    {
      search: search.value,
      category_id: categoryId.value,
      status: status.value,
    },
    {
      preserveState: true,
      replace: true,
      preserveScroll: true,
    }
  )
}

watch([search, categoryId, status], applyFilters)

const productsData = computed(() => props.products?.data ?? [])

const categoryOptions = computed(() => [
  { value: '', label: 'كل الفئات' },
  ...props.categories.map((category) => ({
    value: category.id,
    label: category.name,
  })),
])

const formCategoryOptions = computed(() => props.categories.map((category) => ({
  value: category.id,
  label: category.name,
})))

const openCreateModal = () => {
  createForm.reset()
  createForm.clearErrors()
  createForm.unit_name = 'قطعة'
  createForm.current_price = 0
  createForm.last_purchase_price = 0
  createForm.minimum_stock = 0
  createForm.is_service = false
  createForm.is_active = true
  showCreateModal.value = true
}

const openEditModal = (product) => {
  editForm.clearErrors()
  editForm.id = product.id
  editForm.category_id = product.category_id ?? ''
  editForm.name = product.name
  editForm.product_code = product.product_code
  editForm.barcode = product.barcode ?? ''
  editForm.unit_name = product.unit_name ?? 'قطعة'
  editForm.image_path = product.image_path ?? ''
  editForm.current_price = product.current_price
  editForm.last_purchase_price = product.last_purchase_price
  editForm.minimum_stock = product.minimum_stock
  editForm.is_service = !!product.is_service
  editForm.is_active = !!product.is_active
  editForm.notes = product.notes ?? ''
  showEditModal.value = true
}

const submitCreate = () => {
  createForm.post(route('products.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    },
  })
}

const submitEdit = () => {
  editForm.put(route('products.update', editForm.id), {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false
    },
  })
}

const deleteProduct = (product) => {
  if (!confirm(`هل أنت متأكدة من حذف المنتج "${product.name}"؟`)) return

  router.delete(route('products.destroy', product.id), {
    preserveScroll: true,
  })
}

const restoreProduct = (product) => {
  if (!confirm(`هل تريدين استرجاع المنتج "${product.name}"؟`)) return

  router.post(route('products.restore', product.id), {}, {
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
            <p class="mt-1 text-sm text-slate-500">
              إدارة المنتجات، الأكواد، الباركود، الأسعار، والصور
            </p>
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

          <BaseButton
            label="إضافة منتج"
            color="primary"
            @click="openCreateModal"
          />
        </div>
      </section>

      <section>
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
                    <div class="flex flex-col gap-1">
                      <span
                        class="rounded-full px-3 py-1 text-center text-xs font-bold"
                        :class="product.is_deleted ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'"
                      >
                        {{ product.is_deleted ? 'محذوف' : 'نشط' }}
                      </span>
                    </div>
                  </td>

                  <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-2">
                      <BaseButton
                        v-if="!product.is_deleted"
                        label="تعديل"
                        color="warning"
                        small
                        @click="openEditModal(product)"
                      />

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
      </section>

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

    <!-- Create Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
      <div class="max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-[28px] bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
          <h2 class="text-xl font-black text-slate-800">إضافة منتج</h2>
          <button class="text-xl text-slate-500" @click="showCreateModal = false">×</button>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الفئة</label>
            <FormControl v-model="createForm.category_id" :options="formCategoryOptions" />
            <div v-if="createForm.errors.category_id" class="mt-1 text-sm text-red-600">{{ createForm.errors.category_id }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">اسم المنتج</label>
            <FormControl v-model="createForm.name" type="text" />
            <div v-if="createForm.errors.name" class="mt-1 text-sm text-red-600">{{ createForm.errors.name }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">رمز المنتج</label>
            <FormControl v-model="createForm.product_code" type="text" />
            <div v-if="createForm.errors.product_code" class="mt-1 text-sm text-red-600">{{ createForm.errors.product_code }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الباركود</label>
            <FormControl v-model="createForm.barcode" type="text" />
            <div v-if="createForm.errors.barcode" class="mt-1 text-sm text-red-600">{{ createForm.errors.barcode }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الوحدة</label>
            <FormControl v-model="createForm.unit_name" type="text" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">مسار الصورة</label>
            <FormControl v-model="createForm.image_path" type="text" placeholder="مثال: products/item.jpg" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">سعر البيع الحالي</label>
            <FormControl v-model="createForm.current_price" type="number" />
            <div v-if="createForm.errors.current_price" class="mt-1 text-sm text-red-600">{{ createForm.errors.current_price }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">آخر سعر شراء</label>
            <FormControl v-model="createForm.last_purchase_price" type="number" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الحد الأدنى للمخزون</label>
            <FormControl v-model="createForm.minimum_stock" type="number" />
          </div>

          <div class="md:col-span-2 xl:col-span-3">
            <label class="mb-2 block text-sm font-bold text-slate-700">ملاحظات</label>
            <FormControl v-model="createForm.notes" type="textarea" />
          </div>

          <div class="flex items-center gap-3">
            <input id="create_is_service" v-model="createForm.is_service" type="checkbox" />
            <label for="create_is_service" class="text-sm font-semibold text-slate-700">هذا المنتج خدمة</label>
          </div>

          <div class="flex items-center gap-3">
            <input id="create_product_active" v-model="createForm.is_active" type="checkbox" />
            <label for="create_product_active" class="text-sm font-semibold text-slate-700">المنتج فعال</label>
          </div>
        </div>

        <div class="flex justify-end gap-3 border-t px-6 py-4">
          <BaseButton label="إلغاء" color="light" @click="showCreateModal = false" />
          <BaseButton label="حفظ" color="primary" :disabled="createForm.processing" @click="submitCreate" />
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
      <div class="max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-[28px] bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
          <h2 class="text-xl font-black text-slate-800">تعديل منتج</h2>
          <button class="text-xl text-slate-500" @click="showEditModal = false">×</button>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الفئة</label>
            <FormControl v-model="editForm.category_id" :options="formCategoryOptions" />
            <div v-if="editForm.errors.category_id" class="mt-1 text-sm text-red-600">{{ editForm.errors.category_id }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">اسم المنتج</label>
            <FormControl v-model="editForm.name" type="text" />
            <div v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">رمز المنتج</label>
            <FormControl v-model="editForm.product_code" type="text" />
            <div v-if="editForm.errors.product_code" class="mt-1 text-sm text-red-600">{{ editForm.errors.product_code }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الباركود</label>
            <FormControl v-model="editForm.barcode" type="text" />
            <div v-if="editForm.errors.barcode" class="mt-1 text-sm text-red-600">{{ editForm.errors.barcode }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الوحدة</label>
            <FormControl v-model="editForm.unit_name" type="text" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">مسار الصورة</label>
            <FormControl v-model="editForm.image_path" type="text" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">سعر البيع الحالي</label>
            <FormControl v-model="editForm.current_price" type="number" />
            <div v-if="editForm.errors.current_price" class="mt-1 text-sm text-red-600">{{ editForm.errors.current_price }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">آخر سعر شراء</label>
            <FormControl v-model="editForm.last_purchase_price" type="number" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الحد الأدنى للمخزون</label>
            <FormControl v-model="editForm.minimum_stock" type="number" />
          </div>

          <div class="md:col-span-2 xl:col-span-3">
            <label class="mb-2 block text-sm font-bold text-slate-700">ملاحظات</label>
            <FormControl v-model="editForm.notes" type="textarea" />
          </div>

          <div class="flex items-center gap-3">
            <input id="edit_is_service" v-model="editForm.is_service" type="checkbox" />
            <label for="edit_is_service" class="text-sm font-semibold text-slate-700">هذا المنتج خدمة</label>
          </div>

          <div class="flex items-center gap-3">
            <input id="edit_product_active" v-model="editForm.is_active" type="checkbox" />
            <label for="edit_product_active" class="text-sm font-semibold text-slate-700">المنتج فعال</label>
          </div>
        </div>

        <div class="flex justify-end gap-3 border-t px-6 py-4">
          <BaseButton label="إلغاء" color="light" @click="showEditModal = false" />
          <BaseButton label="حفظ التعديل" color="warning" :disabled="editForm.processing" @click="submitEdit" />
        </div>
      </div>
    </div>
  </MainLayout>
</template>