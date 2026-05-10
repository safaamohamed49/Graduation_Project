<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHero from '@/Components/App/PageHero.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import FormControl from '@/Components/FormControl.vue'

const props = defineProps({
  filters: Object,
  branches: Array,
  isAdmin: Boolean,
  summary: Object,
  financialAccounts: Array,
  stats: Object,
})

const fromDate = ref(props.filters?.from_date ?? '')
const toDate = ref(props.filters?.to_date ?? '')
const branchId = ref(props.filters?.branch_id ?? '')

const applyFilters = () => {
  router.get('/reports/financial-summary', {
    from_date: fromDate.value,
    to_date: toDate.value,
    branch_id: branchId.value,
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
  <MainLayout title="الملخص المالي">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="التقارير / الملخص المالي"
          title="الملخص المالي"
          description="ملخص للخزائن، التدفقات النقدية، المبيعات، والمشتريات."
          gradient-class="bg-gradient-to-br from-emerald-900 via-slate-900 to-cyan-900"
        />
      </div>

      <div class="no-print flex justify-end">
        <BaseButton label="طباعة" color="info" @click="printPage" />
      </div>

      <CardBox class="no-print">
        <CardBoxComponentHeader title="فلترة التقرير" />

        <div class="grid gap-4 p-6 md:grid-cols-4">
          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">من تاريخ</label>
            <FormControl v-model="fromDate" type="date" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">إلى تاريخ</label>
            <FormControl v-model="toDate" type="date" />
          </div>

          <div v-if="props.isAdmin">
            <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>

            <select
              v-model="branchId"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none"
            >
              <option value="">كل الفروع</option>

              <option
                v-for="branch in props.branches"
                :key="branch.id"
                :value="branch.id"
              >
                {{ branch.name }}
              </option>
            </select>
          </div>

          <div class="flex items-end">
            <BaseButton label="تطبيق الفلترة" color="primary" @click="applyFilters" />
          </div>
        </div>
      </CardBox>

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي المقبوضات</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">
            {{ Number(props.summary.total_receipts || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي المصروفات</div>
          <div class="mt-3 text-3xl font-black text-rose-700">
            {{ Number(props.summary.total_payments || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">صافي التدفق النقدي</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">
            {{ Number(props.summary.net_cash_flow || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي المبيعات</div>
          <div class="mt-3 text-3xl font-black text-indigo-700">
            {{ Number(props.summary.total_sales || 0).toFixed(2) }}
          </div>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="الخزائن والبنوك" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-4 font-black">الحساب</th>
                <th class="px-4 py-4 font-black">النوع</th>
                <th class="px-4 py-4 font-black">الرصيد الافتتاحي</th>
                <th class="px-4 py-4 font-black">الرصيد الحالي</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="account in props.financialAccounts"
                :key="account.id"
                class="border-t"
              >
                <td class="px-4 py-4 font-black">
                  {{ account.name }}
                </td>

                <td class="px-4 py-4">
                  {{ account.type }}
                </td>

                <td class="px-4 py-4 font-black text-slate-700">
                  {{ Number(account.opening_balance || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-cyan-700">
                  {{ Number(account.current_balance || 0).toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>
    </div>
  </MainLayout>
</template>