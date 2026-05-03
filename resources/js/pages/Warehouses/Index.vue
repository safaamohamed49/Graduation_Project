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
  warehouses: Object,
  filters: Object,
  canManageWarehouses: Boolean,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const warehouseToDelete = ref(null)

const warehousesData = computed(() => props.warehouses?.data ?? [])

const activeCount = computed(() => warehousesData.value.filter((item) => item.is_active).length)

const totalQuantity = computed(() => {
  return warehousesData.value.reduce((sum, item) => sum + Number(item.total_quantity || 0), 0)
})

const submitSearch = () => {
  router.get(
    '/warehouses',
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

const openDeleteModal = (warehouse) => {
  warehouseToDelete.value = warehouse
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  warehouseToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeleteWarehouse = () => {
  if (!warehouseToDelete.value) return

  router.delete(`/warehouses/${warehouseToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => closeDeleteModal(),
  })
}
</script>

<template>
  <MainLayout title="المخازن">
    <div class="space-y-6">
      <PageHero
        badge="إدارة المخازن"
        title="المخازن والكميات"
        description="إدارة مخازن الفروع وعرض المنتجات والكميات المتوفرة داخل كل مخزن حسب صلاحية المستخدم."
        gradient-class="bg-gradient-to-br from-cyan-900 via-slate-900 to-emerald-900"
      />

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد المخازن</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ props.warehouses?.total ?? 0 }}</div>
          <div class="mt-2 text-xs text-slate-400">المخازن المسموح لك بعرضها</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المخازن الفعالة</div>
          <div class="mt-3 text-3xl font-black text-emerald-600">{{ activeCount }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الكميات</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">{{ totalQuantity.toFixed(2) }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب المخازن الظاهرة حاليًا</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي باسم المخزن أو الكود أو الفرع"
        :create-href="canManageWarehouses ? '/warehouses/create' : null"
        create-label="إضافة مخزن"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة المخازن" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">اسم المخزن</th>
                <th class="px-4 py-4 font-black">الكود</th>
                <th class="px-4 py-4 font-black">الفرع</th>
                <th class="px-4 py-4 font-black">النوع</th>
                <th class="px-4 py-4 font-black">عدد المنتجات</th>
                <th class="px-4 py-4 font-black">إجمالي الكميات</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(warehouse, index) in warehousesData"
                :key="warehouse.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.warehouses.current_page - 1) * props.warehouses.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ warehouse.name }}</span>
                    <span v-if="warehouse.address" class="mt-1 text-xs text-slate-400">{{ warehouse.address }}</span>
                  </div>
                </td>

                <td class="px-4 py-4">{{ warehouse.code || '-' }}</td>
                <td class="px-4 py-4">{{ warehouse.branch?.name || '-' }}</td>
                <td class="px-4 py-4">{{ warehouse.type || '-' }}</td>

                <td class="px-4 py-4 font-black text-cyan-700">
                  {{ warehouse.products_count || 0 }}
                </td>

                <td class="px-4 py-4 font-black text-emerald-700">
                  {{ Number(warehouse.total_quantity || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="warehouse.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ warehouse.is_active ? 'فعال' : 'غير فعال' }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/warehouses/${warehouse.id}`">
                      <BaseButton label="عرض المنتجات" color="info" small />
                    </Link>

                    <Link v-if="canManageWarehouses" :href="`/warehouses/${warehouse.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      v-if="canManageWarehouses"
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(warehouse)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!warehousesData.length">
                <td colspan="9" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد مخازن مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.warehouses.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.warehouses.links"
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
      :open="deleteModalOpen"
      title="تأكيد حذف المخزن"
      :item-name="warehouseToDelete?.name || ''"
      message="سيتم حذف المخزن فقط إذا لم يكن مرتبطًا بحركات مخزون أو فواتير."
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeleteWarehouse"
    />
  </MainLayout>
</template>