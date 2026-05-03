<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import BaseButton from '@/Components/BaseButton.vue'
import FormControl from '@/Components/FormControl.vue'
import PageHero from '@/Components/App/PageHero.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'

const props = defineProps({
  partner: Object,
  transactions: Array,
  filters: Object,
  summary: Object,
})

const fromDate = ref(props.filters?.from_date ?? '')
const toDate = ref(props.filters?.to_date ?? '')

const search = () => {
  router.get(`/partners/${props.partner.id}/statement`, {
    from_date: fromDate.value,
    to_date: toDate.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const printPage = () => {
  window.print()
}
</script>

<template>
  <MainLayout title="كشف حساب الشريك">
    <div class="space-y-6 print:space-y-3">
      <PageHero
        badge="الشركاء / كشف حساب"
        :title="`كشف حساب الشريك: ${partner.name}`"
        description="يعرض هذا الكشف رأس مال الشريك، السحوبات، الإيداعات، والرصيد النهائي خلال فترة محددة."
        hero-back-href="/partners"
        gradient-class="bg-gradient-to-br from-violet-900 via-slate-900 to-cyan-900"
        class="print:hidden"
      />

      <section class="grid gap-4 md:grid-cols-4 print:hidden">
        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">من تاريخ</label>
          <FormControl v-model="fromDate" type="date" />
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">إلى تاريخ</label>
          <FormControl v-model="toDate" type="date" />
        </div>

        <div class="flex items-end">
          <BaseButton label="بحث" color="primary" @click="search" />
        </div>

        <div class="flex items-end justify-end gap-2">
          <Link href="/partners">
            <BaseButton label="رجوع" color="light" />
          </Link>
          <BaseButton label="طباعة" color="success" @click="printPage" />
        </div>
      </section>

      <section class="hidden text-center print:block">
        <h1 class="text-xl font-black">كشف حساب الشريك</h1>
        <p class="mt-1 text-sm">الشريك: {{ partner.name }}</p>
        <p class="mt-1 text-sm">
          الفترة:
          {{ fromDate || 'البداية' }}
          -
          {{ toDate || 'اليوم' }}
        </p>
      </section>

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[24px] bg-white p-5 shadow-sm ring-1 ring-slate-200 print:shadow-none">
          <div class="text-sm font-bold text-slate-500">إجمالي عليه</div>
          <div class="mt-2 text-2xl font-black text-rose-600">
            {{ Number(summary.total_debit || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-white p-5 shadow-sm ring-1 ring-slate-200 print:shadow-none">
          <div class="text-sm font-bold text-slate-500">إجمالي له</div>
          <div class="mt-2 text-2xl font-black text-emerald-600">
            {{ Number(summary.total_credit || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-violet-50 p-5 shadow-sm ring-1 ring-violet-200 print:shadow-none">
          <div class="text-sm font-bold text-violet-700">الرصيد النهائي</div>
          <div class="mt-2 text-2xl font-black text-violet-700">
            {{ Number(summary.balance || 0).toFixed(2) }}
          </div>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="حركات كشف الحساب" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-3 font-black">التاريخ</th>
                <th class="px-4 py-3 font-black">النوع</th>
                <th class="px-4 py-3 font-black">البيان</th>
                <th class="px-4 py-3 font-black">عليه</th>
                <th class="px-4 py-3 font-black">له</th>
                <th class="px-4 py-3 font-black">الرصيد</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(item, index) in transactions"
                :key="index"
                class="border-t text-sm text-slate-700"
              >
                <td class="px-4 py-3">{{ item.date || '-' }}</td>
                <td class="px-4 py-3 font-bold">{{ item.type }}</td>
                <td class="px-4 py-3">{{ item.description }}</td>
                <td class="px-4 py-3 font-black text-rose-600">
                  {{ Number(item.debit || 0).toFixed(2) }}
                </td>
                <td class="px-4 py-3 font-black text-emerald-600">
                  {{ Number(item.credit || 0).toFixed(2) }}
                </td>
                <td class="px-4 py-3 font-black text-slate-800">
                  {{ Number(item.balance || 0).toFixed(2) }}
                </td>
              </tr>

              <tr v-if="!transactions.length">
                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                  لا توجد حركات في هذه الفترة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>
    </div>
  </MainLayout>
</template>