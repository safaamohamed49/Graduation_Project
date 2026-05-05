<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'
import FormControl from '@/Components/FormControl.vue'

const props = defineProps({
  financialAccount: Object,
  movements: Array,
  filters: Object,
  totals: Object,
})

const search = ref(props.filters?.search ?? '')
const dateFrom = ref(props.filters?.date_from ?? '')
const dateTo = ref(props.filters?.date_to ?? '')
const type = ref(props.filters?.type ?? '')

const applyFilters = () => {
  router.get(`/financial-accounts/${props.financialAccount.id}`, {
    search: search.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
    type: type.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const resetFilters = () => {
  router.get(`/financial-accounts/${props.financialAccount.id}`, {}, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const printReport = () => {
  window.print()
}
</script>

<template>
  <MainLayout :title="props.financialAccount.name">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="كشف خزينة / بنك"
          :title="props.financialAccount.name"
          description="كشف حركة الحساب المالي من قبض وصرف مع الرصيد الجاري."
          gradient-class="bg-gradient-to-br from-slate-900 via-emerald-900 to-cyan-900"
        />
      </div>

      <div class="no-print flex flex-wrap justify-end gap-3">
        <Link href="/financial-accounts">
          <BaseButton label="رجوع" color="light" />
        </Link>

        <Link :href="`/financial-accounts/${props.financialAccount.id}/edit`">
          <BaseButton label="تعديل" color="warning" />
        </Link>

        <BaseButton label="طباعة الكشف" color="info" @click="printReport" />
      </div>

      <section class="no-print grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">رصيد افتتاحي</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ Number(props.totals.opening_balance || 0).toFixed(2) }}</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الداخل</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">{{ Number(props.totals.total_in || 0).toFixed(2) }}</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الخارج</div>
          <div class="mt-3 text-3xl font-black text-rose-700">{{ Number(props.totals.total_out || 0).toFixed(2) }}</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الرصيد الحالي</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">{{ Number(props.totals.current_balance || 0).toFixed(2) }}</div>
        </div>
      </section>

      <CardBox class="no-print">
        <CardBoxComponentHeader title="فلاتر الكشف" />

        <div class="grid gap-4 p-6 md:grid-cols-5">
          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">بحث</label>
            <FormControl v-model="search" type="text" placeholder="رقم الإيصال أو البيان" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">من تاريخ</label>
            <FormControl v-model="dateFrom" type="date" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">إلى تاريخ</label>
            <FormControl v-model="dateTo" type="date" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">نوع الحركة</label>
            <select v-model="type" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none">
              <option value="">الكل</option>
              <option value="receipt">قبض فقط</option>
              <option value="payment">صرف فقط</option>
            </select>
          </div>

          <div class="flex items-end gap-2">
            <BaseButton label="تطبيق" color="primary" @click="applyFilters" />
            <BaseButton label="مسح" color="light" @click="resetFilters" />
          </div>
        </div>
      </CardBox>

      <div id="print-area">
        <div class="print-header hidden print:block">
          <h1>بنيس للحديد الصناعي</h1>
          <h2>كشف حركة خزينة / بنك</h2>
          <p>الحساب: {{ props.financialAccount.name }} — الفرع: {{ props.financialAccount.branch?.name || '-' }}</p>
        </div>

        <CardBox>
          <CardBoxComponentHeader title="حركة الحساب المالي" />

          <div class="overflow-x-auto">
            <table class="min-w-full text-right">
              <thead class="bg-slate-50">
                <tr class="text-sm text-slate-600">
                  <th class="px-4 py-4 font-black">التاريخ</th>
                  <th class="px-4 py-4 font-black">النوع</th>
                  <th class="px-4 py-4 font-black">رقم الإيصال</th>
                  <th class="px-4 py-4 font-black">البيان</th>
                  <th class="px-4 py-4 font-black">طريقة الدفع</th>
                  <th class="px-4 py-4 font-black">داخل</th>
                  <th class="px-4 py-4 font-black">خارج</th>
                  <th class="px-4 py-4 font-black">الرصيد</th>
                  <th class="no-print px-4 py-4 font-black">عرض</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="movement in props.movements" :key="movement.id" class="border-t text-sm text-slate-700">
                  <td class="px-4 py-4">{{ movement.date ? new Date(movement.date).toLocaleDateString('en-GB') : '-' }}</td>
                  <td class="px-4 py-4 font-black" :class="movement.type === 'receipt' ? 'text-emerald-700' : 'text-rose-700'">
                    {{ movement.type_label }}
                  </td>
                  <td class="px-4 py-4 font-black">{{ movement.number }}</td>
                  <td class="px-4 py-4">{{ movement.description || '-' }}</td>
                  <td class="px-4 py-4">{{ movement.method || '-' }}</td>
                  <td class="px-4 py-4 font-black text-emerald-700">{{ Number(movement.in || 0).toFixed(2) }}</td>
                  <td class="px-4 py-4 font-black text-rose-700">{{ Number(movement.out || 0).toFixed(2) }}</td>
                  <td class="px-4 py-4 font-black text-cyan-700">{{ Number(movement.balance || 0).toFixed(2) }}</td>
                  <td class="no-print px-4 py-4">
                    <a :href="movement.url" class="text-sm font-black text-cyan-700">فتح</a>
                  </td>
                </tr>

                <tr v-if="!props.movements.length">
                  <td colspan="9" class="px-4 py-14 text-center text-sm text-slate-500">
                    لا توجد حركات مالية.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardBox>
      </div>
    </div>
  </MainLayout>
</template>

<style>
.print-header {
  text-align: center;
  margin-bottom: 18px;
}

.print-header h1 {
  font-size: 22px;
  font-weight: 900;
}

.print-header h2 {
  font-size: 18px;
  font-weight: 900;
  margin-top: 5px;
}

.print-header p {
  margin-top: 5px;
  font-size: 13px;
  font-weight: 700;
}

@media print {
  body {
    background: white !important;
  }

  aside,
  header,
  .no-print {
    display: none !important;
  }

  #print-area {
    display: block !important;
    direction: rtl;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }

  th,
  td {
    border: 1px solid #ddd;
    padding: 6px;
  }

  @page {
    size: A4 portrait;
    margin: 12mm;
  }
}
</style>