<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'
import EntityToolbar from '@/Components/App/EntityToolbar.vue'

const props = defineProps({
  transfers: Object,
  filters: Object,
  permissions: Object,
})

const search = ref(props.filters?.search ?? '')

const transfersData = computed(() => props.transfers?.data ?? [])

const postedCount = computed(() => {
  return transfersData.value.filter((item) => item.status === 'posted').length
})

const totalItems = computed(() => {
  return transfersData.value.reduce((sum, item) => sum + Number(item.items_count || 0), 0)
})

const submitSearch = () => {
  router.get(
    '/stock-transfers',
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

const statusLabel = (status) => {
  const labels = {
    posted: 'مرحل',
    draft: 'مسودة',
    cancelled: 'ملغي',
  }

  return labels[status] || status || '-'
}
</script>

<template>
  <MainLayout title="النقل بين المخازن">
    <div class="space-y-6">
      <PageHero
        badge="المخازن / التحويلات"
        title="النقل بين المخازن"
        description="تنفيذ تحويلات المخزون بين المخازن مع خصم الكميات من المخزن المصدر وإضافتها للمخزن المستلم حسب نظام FIFO."
        gradient-class="bg-gradient-to-br from-cyan-900 via-slate-900 to-blue-900"
      />

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد التحويلات</div>
          <div class="mt-3 text-3xl font-black text-slate-800">
            {{ props.transfers?.total ?? 0 }}
          </div>
          <div class="mt-2 text-xs text-slate-400">كل التحويلات المسموح لك بعرضها</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">التحويلات المرحلة</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">
            {{ postedCount }}
          </div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الأصناف المحولة</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">
            {{ totalItems }}
          </div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي برقم التحويل أو المخزن أو الملاحظات"
        :create-href="props.permissions?.canCreate ? '/stock-transfers/create' : null"
        create-label="إضافة تحويل"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة التحويلات المخزنية" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">رقم التحويل</th>
                <th class="px-4 py-4 font-black">التاريخ</th>
                <th class="px-4 py-4 font-black">من مخزن</th>
                <th class="px-4 py-4 font-black">إلى مخزن</th>
                <th class="px-4 py-4 font-black">عدد الأصناف</th>
                <th class="px-4 py-4 font-black">المستخدم</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(transfer, index) in transfersData"
                :key="transfer.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.transfers.current_page - 1) * props.transfers.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4 font-black text-slate-800">
                  {{ transfer.transfer_number }}
                </td>

                <td class="px-4 py-4">
                  {{ transfer.transfer_date ? new Date(transfer.transfer_date).toLocaleDateString('en-GB') : '-' }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ transfer.from_warehouse?.name || '-' }}</span>
                    <span class="text-xs text-slate-400">{{ transfer.from_warehouse?.code || '-' }}</span>
                  </div>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ transfer.to_warehouse?.name || '-' }}</span>
                    <span class="text-xs text-slate-400">{{ transfer.to_warehouse?.code || '-' }}</span>
                  </div>
                </td>

                <td class="px-4 py-4 font-black text-cyan-700">
                  {{ transfer.items_count || 0 }}
                </td>

                <td class="px-4 py-4">
                  {{ transfer.user?.name || '-' }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="transfer.status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                  >
                    {{ statusLabel(transfer.status) }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  <Link :href="`/stock-transfers/${transfer.id}`">
                    <BaseButton label="عرض" color="info" small />
                  </Link>
                </td>
              </tr>

              <tr v-if="!transfersData.length">
                <td colspan="9" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد تحويلات مخزنية مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.transfers.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.transfers.links"
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
  </MainLayout>
</template>