<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  transfer: Object,
})

const totalQuantity = computed(() => {
  return (props.transfer.items || []).reduce((sum, item) => sum + Number(item.quantity || 0), 0)
})

const totalCost = computed(() => {
  return (props.transfer.items || []).reduce((sum, item) => sum + Number(item.total_cost || 0), 0)
})

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
  <MainLayout :title="props.transfer.transfer_number">
    <div class="space-y-6">
      <PageHero
        badge="تفاصيل التحويل"
        :title="props.transfer.transfer_number"
        description="عرض تفاصيل حركة النقل بين المخازن والمنتجات التي تم تحويلها."
        gradient-class="bg-gradient-to-br from-cyan-900 via-slate-900 to-blue-900"
      />

      <div class="flex justify-end gap-3">
        <Link href="/stock-transfers">
          <BaseButton label="رجوع" color="light" />
        </Link>
      </div>

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الأصناف</div>
          <div class="mt-3 text-3xl font-black text-slate-800">
            {{ props.transfer.items?.length || 0 }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الكميات</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">
            {{ totalQuantity.toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي التكلفة</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">
            {{ totalCost.toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الحالة</div>
          <div class="mt-3">
            <span
              class="rounded-full px-4 py-2 text-sm font-black"
              :class="props.transfer.status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
            >
              {{ statusLabel(props.transfer.status) }}
            </span>
          </div>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="بيانات التحويل" />

        <div class="grid gap-5 p-6 md:grid-cols-2">
          <div>
            <div class="text-xs font-bold text-slate-400">رقم التحويل</div>
            <div class="mt-2 font-black text-slate-800">{{ props.transfer.transfer_number }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">تاريخ التحويل</div>
            <div class="mt-2 font-black text-slate-800">
              {{ props.transfer.transfer_date ? new Date(props.transfer.transfer_date).toLocaleDateString('en-GB') : '-' }}
            </div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">الفرع</div>
            <div class="mt-2 font-black text-slate-800">
              {{ props.transfer.branch?.name || '-' }}
            </div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">المستخدم</div>
            <div class="mt-2 font-black text-slate-800">
              {{ props.transfer.user?.name || '-' }}
            </div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">من مخزن</div>
            <div class="mt-2 font-black text-rose-700">
              {{ props.transfer.from_warehouse?.name || '-' }}
            </div>
            <div class="mt-1 text-xs text-slate-400">
              {{ props.transfer.from_warehouse?.code || '-' }}
            </div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">إلى مخزن</div>
            <div class="mt-2 font-black text-emerald-700">
              {{ props.transfer.to_warehouse?.name || '-' }}
            </div>
            <div class="mt-1 text-xs text-slate-400">
              {{ props.transfer.to_warehouse?.code || '-' }}
            </div>
          </div>

          <div class="md:col-span-2">
            <div class="text-xs font-bold text-slate-400">ملاحظات</div>
            <div class="mt-2 font-black text-slate-800">
              {{ props.transfer.notes || '-' }}
            </div>
          </div>
        </div>
      </CardBox>

      <CardBox>
        <CardBoxComponentHeader title="المنتجات المحولة" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">المنتج</th>
                <th class="px-4 py-4 font-black">الكود</th>
                <th class="px-4 py-4 font-black">الوحدة</th>
                <th class="px-4 py-4 font-black">الكمية</th>
                <th class="px-4 py-4 font-black">متوسط التكلفة</th>
                <th class="px-4 py-4 font-black">إجمالي التكلفة</th>
                <th class="px-4 py-4 font-black">ملاحظات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(item, index) in props.transfer.items || []"
                :key="item.id"
                class="border-t text-sm text-slate-700"
              >
                <td class="px-4 py-4">{{ index + 1 }}</td>

                <td class="px-4 py-4 font-black text-slate-800">
                  {{ item.product?.name || '-' }}
                </td>

                <td class="px-4 py-4">
                  {{ item.product?.product_code || '-' }}
                </td>

                <td class="px-4 py-4">
                  {{ item.product?.unit_name || '-' }}
                </td>

                <td class="px-4 py-4 font-black text-cyan-700">
                  {{ Number(item.quantity || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-slate-700">
                  {{ Number(item.unit_cost || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-emerald-700">
                  {{ Number(item.total_cost || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  {{ item.notes || '-' }}
                </td>
              </tr>

              <tr v-if="!props.transfer.items?.length">
                <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">
                  لا توجد منتجات داخل هذا التحويل.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>
    </div>
  </MainLayout>
</template>