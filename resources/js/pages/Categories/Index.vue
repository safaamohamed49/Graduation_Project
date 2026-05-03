<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'
import EntityToolbar from '@/Components/App/EntityToolbar.vue'
import DeleteConfirmModal from '@/Components/App/DeleteConfirmModal.vue'

const props = defineProps({
  categories: Object,
  filters: Object,
  permissions: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const categoryToDelete = ref(null)

const categoriesData = computed(() => props.categories?.data ?? [])
const canCreate = computed(() => props.permissions?.canCreate ?? false)
const canUpdate = computed(() => props.permissions?.canUpdate ?? false)
const canDelete = computed(() => props.permissions?.canDelete ?? false)

const activeCount = computed(() => categoriesData.value.filter((item) => item.is_active).length)

const totalProducts = computed(() => {
  return categoriesData.value.reduce((sum, item) => sum + Number(item.products_count || 0), 0)
})

const submitSearch = () => {
  router.get(
    '/categories',
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

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
    onFinish: () => closeDeleteModal(),
  })
}
</script>

<template>
  <MainLayout title="الفئات">
    <div class="space-y-6">
      <PageHero
        badge="إدارة الفئات"
        title="تصنيفات المنتجات"
        description="إدارة فئات المنتجات وربطها بالمنتجات لتسهيل البحث والتنظيم داخل المخزون والمبيعات."
        gradient-class="bg-gradient-to-br from-cyan-900 via-slate-900 to-indigo-900"
      />

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الفئات</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ props.categories?.total ?? 0 }}</div>
          <div class="mt-2 text-xs text-slate-400">إجمالي الفئات المسجلة</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الفئات الفعالة</div>
          <div class="mt-3 text-3xl font-black text-emerald-600">{{ activeCount }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المنتجات المرتبطة</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">{{ totalProducts }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الفئات الظاهرة حاليًا</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي باسم الفئة أو الرمز"
        :create-href="canCreate ? '/categories/create' : null"
        create-label="إضافة فئة"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة الفئات" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">اسم الفئة</th>
                <th class="px-4 py-4 font-black">الرمز</th>
                <th class="px-4 py-4 font-black">الوصف</th>
                <th class="px-4 py-4 font-black">عدد المنتجات</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th v-if="canUpdate || canDelete" class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(category, index) in categoriesData"
                :key="category.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.categories.current_page - 1) * props.categories.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ category.name }}</span>
                    <span v-if="category.notes" class="mt-1 text-xs text-slate-400">{{ category.notes }}</span>
                  </div>
                </td>

                <td class="px-4 py-4 font-bold text-cyan-700">{{ category.code }}</td>
                <td class="px-4 py-4">{{ category.description || '-' }}</td>
                <td class="px-4 py-4 font-black text-indigo-700">{{ category.products_count || 0 }}</td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="category.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ category.is_active ? 'فعالة' : 'غير فعالة' }}
                  </span>
                </td>

                <td v-if="canUpdate || canDelete" class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link v-if="canUpdate" :href="`/categories/${category.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      v-if="canDelete"
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(category)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!categoriesData.length">
                <td
                  :colspan="canUpdate || canDelete ? 7 : 6"
                  class="px-4 py-14 text-center text-sm text-slate-500"
                >
                  لا توجد فئات مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.categories.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.categories.links"
          :key="link.label"
          class="rounded-2xl border px-4 py-2 text-sm font-semibold transition"
          :class="[
            link.active
              ? 'border-cyan-600 bg-cyan-600 text-white'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
            !link.url ? 'cursor-not-allowed opacity-50' : ''
          ]"
          :disabled="!link.url"
          @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })"
          v-html="link.label"
        />
      </section>
    </div>

    <DeleteConfirmModal
      :open="deleteModalOpen && canDelete"
      title="تأكيد حذف الفئة"
      :item-name="categoryToDelete?.name || ''"
      message="سيتم حذف الفئة فقط إذا لم تكن مرتبطة بمنتجات داخل النظام."
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeleteCategory"
    />
  </MainLayout>
</template>