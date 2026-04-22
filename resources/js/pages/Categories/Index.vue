<script setup>
import { ref, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  categories: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const categoryToDelete = ref(null)

watch(search, (value) => {
  router.get(
    '/categories',
    { search: value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
})

const categoriesData = computed(() => props.categories?.data ?? [])

const openDeleteModal = (category) => {
  categoryToDelete.value = category
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  categoryToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeleteCategory = () => {
  if (!categoryToDelete.value) return

  router.delete(`/categories/${categoryToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      closeDeleteModal()
    },
  })
}
</script>

<template>
  <MainLayout title="الفئات">
    <div class="space-y-6">
      <section class="rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 class="text-2xl font-black text-slate-800">إدارة الفئات</h1>
            <p class="mt-1 text-sm text-slate-500">عرض وإدارة تصنيفات المنتجات</p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <div class="min-w-[260px]">
              <FormControl
                v-model="search"
                type="text"
                placeholder="ابحثي بالاسم أو الرمز"
              />
            </div>

            <Link href="/categories/create">
              <BaseButton label="إضافة فئة" color="primary" />
            </Link>
          </div>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="قائمة الفئات" />
        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-3 font-bold">#</th>
                <th class="px-4 py-3 font-bold">اسم الفئة</th>
                <th class="px-4 py-3 font-bold">الرمز</th>
                <th class="px-4 py-3 font-bold">الوصف</th>
                <th class="px-4 py-3 font-bold">عدد المنتجات</th>
                <th class="px-4 py-3 font-bold">الحالة</th>
                <th class="px-4 py-3 font-bold">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(category, index) in categoriesData"
                :key="category.id"
                class="border-t text-sm text-slate-700"
              >
                <td class="px-4 py-3">
                  {{ ((props.categories.current_page - 1) * props.categories.per_page) + index + 1 }}
                </td>
                <td class="px-4 py-3 font-semibold">{{ category.name }}</td>
                <td class="px-4 py-3">{{ category.code }}</td>
                <td class="px-4 py-3">{{ category.description || '-' }}</td>
                <td class="px-4 py-3">{{ category.products_count }}</td>
                <td class="px-4 py-3">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="category.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                  >
                    {{ category.is_active ? 'فعالة' : 'غير فعالة' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/categories/${category.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>
                    <BaseButton label="حذف" color="danger" small @click="openDeleteModal(category)" />
                  </div>
                </td>
              </tr>

              <tr v-if="!categoriesData.length">
                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                  لا توجد فئات مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>
    </div>

    <div
      v-if="deleteModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
      <div class="w-full max-w-md rounded-[28px] bg-white p-6 shadow-2xl">
        <div class="mb-4">
          <h2 class="text-xl font-black text-slate-800">تأكيد الحذف</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">
            هل أنت متأكدة من حذف الفئة
            <span class="font-bold text-slate-800">"{{ categoryToDelete?.name }}"</span>
            ؟
          </p>
        </div>

        <div class="flex justify-end gap-3">
          <BaseButton label="إلغاء" color="light" @click="closeDeleteModal" />
          <BaseButton label="تأكيد الحذف" color="danger" @click="confirmDeleteCategory" />
        </div>
      </div>
    </div>
  </MainLayout>
</template>