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
  branches: Object,
  filters: Object,
  permissions: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const branchToDelete = ref(null)

const branchesData = computed(() => props.branches?.data ?? [])
const activeCount = computed(() => branchesData.value.filter((item) => item.is_active).length)

const totalWarehouses = computed(() => {
  return branchesData.value.reduce((sum, item) => sum + Number(item.warehouses_count || 0), 0)
})

const submitSearch = () => {
  router.get(
    '/branches',
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

const openDeleteModal = (branch) => {
  branchToDelete.value = branch
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  branchToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeleteBranch = () => {
  if (!branchToDelete.value) return

  router.delete(`/branches/${branchToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => closeDeleteModal(),
  })
}
</script>

<template>
  <MainLayout title="الفروع">
    <div class="space-y-6">
      <PageHero
        badge="إدارة الفروع"
        title="الفروع والصلاحيات"
        description="إدارة فروع الشركة وربط المستخدمين والمخازن والحركات المحاسبية بكل فرع."
        gradient-class="bg-gradient-to-br from-blue-900 via-slate-900 to-cyan-800"
      />

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الفروع</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ props.branches?.total ?? 0 }}</div>
          <div class="mt-2 text-xs text-slate-400">الفروع المسجلة في النظام</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الفروع الفعالة</div>
          <div class="mt-3 text-3xl font-black text-emerald-600">{{ activeCount }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي المخازن</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">{{ totalWarehouses }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الفروع الظاهرة حاليًا</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي باسم الفرع أو الكود أو العنوان"
        :create-href="props.permissions?.canCreate ? '/branches/create' : null"
        create-label="إضافة فرع"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة الفروع" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">اسم الفرع</th>
                <th class="px-4 py-4 font-black">الكود</th>
                <th class="px-4 py-4 font-black">المدير</th>
                <th class="px-4 py-4 font-black">الهاتف</th>
                <th class="px-4 py-4 font-black">المخازن</th>
                <th class="px-4 py-4 font-black">المستخدمين</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(branch, index) in branchesData"
                :key="branch.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.branches.current_page - 1) * props.branches.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ branch.name }}</span>
                    <span v-if="branch.address" class="mt-1 text-xs text-slate-400">{{ branch.address }}</span>
                  </div>
                </td>

                <td class="px-4 py-4">{{ branch.code || '-' }}</td>
                <td class="px-4 py-4">{{ branch.manager?.name || '-' }}</td>
                <td class="px-4 py-4">{{ branch.phone || '-' }}</td>

                <td class="px-4 py-4 font-black text-cyan-700">
                  {{ branch.warehouses_count || 0 }}
                </td>

                <td class="px-4 py-4 font-black text-blue-700">
                  {{ branch.users_count || 0 }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="branch.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ branch.is_active ? 'فعال' : 'غير فعال' }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/branches/${branch.id}`">
                      <BaseButton label="عرض" color="info" small />
                    </Link>

                    <Link v-if="props.permissions?.canUpdate" :href="`/branches/${branch.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      v-if="props.permissions?.canDelete"
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(branch)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!branchesData.length">
                <td colspan="9" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد فروع مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.branches.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.branches.links"
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
      title="تأكيد حذف الفرع"
      :item-name="branchToDelete?.name || ''"
      message="سيتم حذف الفرع فقط إذا لم يكن مرتبطًا بمخازن أو مستخدمين أو حركات داخل النظام."
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeleteBranch"
    />
  </MainLayout>
</template>