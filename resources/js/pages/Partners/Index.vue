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
  partners: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const partnerToDelete = ref(null)

const partnersData = computed(() => props.partners?.data ?? [])

const activeCount = computed(() => partnersData.value.filter((item) => item.is_active).length)

const totalCapital = computed(() => {
  return partnersData.value.reduce((sum, item) => sum + Number(item.capital_amount || 0), 0)
})

const submitSearch = () => {
  router.get(
    '/partners',
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

const openDeleteModal = (partner) => {
  partnerToDelete.value = partner
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  partnerToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeletePartner = () => {
  if (!partnerToDelete.value) return

  router.delete(`/partners/${partnerToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => closeDeleteModal(),
  })
}
</script>

<template>
  <MainLayout title="الشركاء">
    <div class="space-y-6">
      <PageHero
        badge="إدارة الشركاء"
        title="الشركاء ورأس المال"
        description="إدارة بيانات الشركاء ونسب الملكية ورأس المال، مع تمهيد النظام لربط الحسابات الجارية والسحوبات والأرباح لاحقًا."
        gradient-class="bg-gradient-to-br from-violet-900 via-slate-900 to-cyan-900"
      />

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الشركاء</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ props.partners?.total ?? 0 }}</div>
          <div class="mt-2 text-xs text-slate-400">إجمالي الشركاء المسجلين</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الشركاء النشطون</div>
          <div class="mt-3 text-3xl font-black text-emerald-600">{{ activeCount }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي رأس المال</div>
          <div class="mt-3 text-3xl font-black text-violet-700">{{ totalCapital.toFixed(2) }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الشركاء الظاهرين حاليًا</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي باسم الشريك أو الهاتف أو البريد"
        create-href="/partners/create"
        create-label="إضافة شريك"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة الشركاء" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">اسم الشريك</th>
                <th class="px-4 py-4 font-black">الهاتف</th>
                <th class="px-4 py-4 font-black">رأس المال</th>
                <th class="px-4 py-4 font-black">نسبة الملكية</th>
                <th class="px-4 py-4 font-black">تاريخ البداية</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(partner, index) in partnersData"
                :key="partner.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.partners.current_page - 1) * props.partners.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ partner.name }}</span>
                    <span v-if="partner.email" class="mt-1 text-xs text-slate-400">{{ partner.email }}</span>
                  </div>
                </td>

                <td class="px-4 py-4">{{ partner.phone || '-' }}</td>

                <td class="px-4 py-4 font-black text-violet-700">
                  {{ Number(partner.capital_amount || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  <span class="rounded-xl bg-violet-100 px-3 py-1 text-xs font-black text-violet-700">
                    {{ Number(partner.ownership_percentage || 0).toFixed(2) }}%
                  </span>
                </td>

                <td class="px-4 py-4">
                  {{ partner.start_date ? String(partner.start_date).slice(0, 10) : '-' }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="partner.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ partner.is_active ? 'فعال' : 'غير فعال' }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/partners/${partner.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(partner)"
                    />
                    <Link :href="`/partners/${partner.id}/statement`">
                   <BaseButton label="كشف حساب" color="info" small />
                   </Link>
                  </div>
                </td>
              </tr>

              <tr v-if="!partnersData.length">
                <td colspan="8" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد بيانات شركاء مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.partners.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.partners.links"
          :key="link.label"
          class="rounded-2xl border px-4 py-2 text-sm font-semibold transition"
          :class="[
            link.active
              ? 'border-violet-600 bg-violet-600 text-white'
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
      title="تأكيد حذف الشريك"
      :item-name="partnerToDelete?.name || ''"
      message="سيتم حذف الشريك من النظام إذا لم يكن مرتبطًا بعمليات مالية"
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeletePartner"
    />
  </MainLayout>
</template>