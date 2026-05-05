<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHero from '@/Components/App/PageHero.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import FormControl from '@/Components/FormControl.vue'

const props = defineProps({
  asset: Object,
  totals: Object,
  currentPeriod: Object,
  permissions: Object,
})

const showManualDepreciation = ref(false)

const monthlyForm = useForm({
  period_year: props.currentPeriod?.year ?? new Date().getFullYear(),
  period_month: props.currentPeriod?.month ?? new Date().getMonth() + 1,
  notes: 'إهلاك شهري تلقائي',
})

const manualForm = useForm({
  depreciation_date: new Date().toISOString().slice(0, 10),
  period_year: new Date().getFullYear(),
  period_month: new Date().getMonth() + 1,
  amount: props.totals?.monthly_depreciation ?? 0,
  notes: '',
})

const statusLabel = (value) => value ? 'فعال' : 'موقوف'

const monthLabel = (month) => {
  const months = {
    1: 'يناير',
    2: 'فبراير',
    3: 'مارس',
    4: 'أبريل',
    5: 'مايو',
    6: 'يونيو',
    7: 'يوليو',
    8: 'أغسطس',
    9: 'سبتمبر',
    10: 'أكتوبر',
    11: 'نوفمبر',
    12: 'ديسمبر',
  }

  return months[Number(month)] || month
}

const storeMonthlyDepreciation = () => {
  if (!confirm('هل تريدين إثبات إهلاك هذا الشهر؟ سيتم إنشاء قيد محاسبي تلقائياً.')) return

  monthlyForm.post(`/fixed-assets/${props.asset.id}/depreciations/monthly`, {
    preserveScroll: true,
  })
}

const storeManualDepreciation = () => {
  manualForm.post(`/fixed-assets/${props.asset.id}/depreciations`, {
    preserveScroll: true,
    onSuccess: () => {
      showManualDepreciation.value = false
      manualForm.reset('notes')
    },
  })
}

const printPage = () => {
  window.print()
}
</script>

<template>
  <MainLayout :title="props.asset.name">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="الأصول الثابتة / تفاصيل"
          :title="props.asset.name"
          description="تفاصيل الأصل، قيد الشراء، سجل الإهلاك، والقيمة الدفترية الحالية."
          gradient-class="bg-gradient-to-br from-indigo-900 via-slate-900 to-cyan-900"
        />
      </div>

      <div class="no-print flex flex-wrap justify-end gap-3">
        <Link href="/fixed-assets">
          <BaseButton label="رجوع" color="light" />
        </Link>

        <BaseButton label="طباعة" color="info" @click="printPage" />

        <Link v-if="props.permissions?.canUpdate" :href="`/fixed-assets/${props.asset.id}/edit`">
          <BaseButton label="تعديل الأصل" color="warning" />
        </Link>

        <BaseButton
          v-if="props.permissions?.canDepreciate"
          label="إهلاك يدوي"
          color="light"
          @click="showManualDepreciation = !showManualDepreciation"
        />

        <BaseButton
          v-if="props.permissions?.canDepreciate"
          label="إهلاك هذا الشهر"
          color="primary"
          :disabled="monthlyForm.processing || props.currentPeriod?.already_depreciated || Number(props.totals?.monthly_depreciation || 0) <= 0"
          @click="storeMonthlyDepreciation"
        />
      </div>

      <div id="print-area" class="space-y-6">
        <div class="hidden print:block text-center">
          <h1 class="text-2xl font-black">بنيس للحديد الصناعي</h1>
          <h2 class="mt-2 text-xl font-black">بطاقة أصل ثابت</h2>
          <p class="mt-1 text-sm font-bold">
            الفرع: {{ props.asset.branch?.name || '-' }}
          </p>
        </div>

        <section class="grid gap-4 md:grid-cols-4">
          <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="text-sm font-bold text-slate-500">قيمة الشراء</div>
            <div class="mt-3 text-3xl font-black text-indigo-700">
              {{ Number(props.totals?.purchase_value || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="text-sm font-bold text-slate-500">مجمع الإهلاك</div>
            <div class="mt-3 text-3xl font-black text-rose-700">
              {{ Number(props.totals?.total_depreciation || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="text-sm font-bold text-slate-500">القيمة الدفترية</div>
            <div class="mt-3 text-3xl font-black text-cyan-700">
              {{ Number(props.totals?.book_value || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="text-sm font-bold text-slate-500">الإهلاك الشهري</div>
            <div class="mt-3 text-3xl font-black text-emerald-700">
              {{ Number(props.totals?.monthly_depreciation || 0).toFixed(2) }}
            </div>
          </div>
        </section>

        <CardBox>
          <CardBoxComponentHeader title="بيانات الأصل" />

          <div class="grid gap-5 p-6 md:grid-cols-3">
            <div>
              <div class="text-xs font-bold text-slate-400">اسم الأصل</div>
              <div class="mt-2 font-black text-slate-800">{{ props.asset.name }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">كود الأصل</div>
              <div class="mt-2 font-black text-slate-800">{{ props.asset.asset_code }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">الحالة</div>
              <div class="mt-2 font-black" :class="props.asset.is_active ? 'text-emerald-700' : 'text-rose-700'">
                {{ statusLabel(props.asset.is_active) }}
              </div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">الفرع</div>
              <div class="mt-2 font-black text-slate-800">{{ props.asset.branch?.name || '-' }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">تاريخ الشراء</div>
              <div class="mt-2 font-black text-slate-800">
                {{ props.asset.purchase_date ? new Date(props.asset.purchase_date).toLocaleDateString('en-GB') : '-' }}
              </div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">الخزينة / البنك</div>
              <div class="mt-2 font-black text-slate-800">{{ props.asset.financial_account?.name || '-' }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">القيمة المتبقية</div>
              <div class="mt-2 font-black text-slate-800">{{ Number(props.totals?.salvage_value || 0).toFixed(2) }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">القيمة القابلة للإهلاك</div>
              <div class="mt-2 font-black text-slate-800">{{ Number(props.totals?.depreciable_value || 0).toFixed(2) }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">المتبقي للإهلاك</div>
              <div class="mt-2 font-black text-slate-800">{{ Number(props.totals?.remaining_depreciation || 0).toFixed(2) }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">حساب الأصل</div>
              <div class="mt-2 font-black text-indigo-700">{{ props.asset.asset_account?.name || '-' }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">حساب مجمع الإهلاك</div>
              <div class="mt-2 font-black text-rose-700">{{ props.asset.depreciation_account?.name || '-' }}</div>
            </div>

            <div>
              <div class="text-xs font-bold text-slate-400">حساب مصروف الإهلاك</div>
              <div class="mt-2 font-black text-emerald-700">{{ props.asset.depreciation_expense_account?.name || '-' }}</div>
            </div>

            <div class="md:col-span-3">
              <div class="text-xs font-bold text-slate-400">ملاحظات</div>
              <div class="mt-2 whitespace-pre-line font-black text-slate-800">{{ props.asset.notes || '-' }}</div>
            </div>
          </div>
        </CardBox>

        <CardBox v-if="showManualDepreciation" class="no-print">
          <CardBoxComponentHeader title="إضافة إهلاك يدوي" />

          <form class="grid gap-4 p-6 md:grid-cols-5" @submit.prevent="storeManualDepreciation">
            <div>
              <label class="mb-2 block text-sm font-black text-slate-700">تاريخ الإهلاك</label>
              <FormControl v-model="manualForm.depreciation_date" type="date" />
            </div>

            <div>
              <label class="mb-2 block text-sm font-black text-slate-700">السنة</label>
              <FormControl v-model="manualForm.period_year" type="number" />
            </div>

            <div>
              <label class="mb-2 block text-sm font-black text-slate-700">الشهر</label>
              <select
                v-model="manualForm.period_month"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none"
              >
                <option v-for="month in 12" :key="month" :value="month">
                  {{ monthLabel(month) }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-2 block text-sm font-black text-slate-700">المبلغ</label>
              <FormControl v-model="manualForm.amount" type="number" step="0.01" />
            </div>

            <div class="flex items-end">
              <BaseButton label="حفظ الإهلاك" color="primary" type="submit" :disabled="manualForm.processing" />
            </div>

            <div class="md:col-span-5">
              <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
              <textarea
                v-model="manualForm.notes"
                rows="2"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none"
              />
            </div>

            <div v-if="manualForm.errors.amount" class="md:col-span-5 text-sm font-bold text-red-600">
              {{ manualForm.errors.amount }}
            </div>
          </form>
        </CardBox>

        <CardBox>
          <CardBoxComponentHeader title="سجل الإهلاك" />

          <div class="overflow-x-auto">
            <table class="min-w-full text-right">
              <thead class="bg-slate-50">
                <tr class="text-sm text-slate-600">
                  <th class="px-4 py-4 font-black">الفترة</th>
                  <th class="px-4 py-4 font-black">تاريخ القيد</th>
                  <th class="px-4 py-4 font-black">المبلغ</th>
                  <th class="px-4 py-4 font-black">رقم القيد</th>
                  <th class="px-4 py-4 font-black">ملاحظات</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="depreciation in props.asset.depreciations"
                  :key="depreciation.id"
                  class="border-t text-sm text-slate-700"
                >
                  <td class="px-4 py-4 font-black">
                    {{ monthLabel(depreciation.period_month) }} {{ depreciation.period_year }}
                  </td>

                  <td class="px-4 py-4">
                    {{ depreciation.depreciation_date ? new Date(depreciation.depreciation_date).toLocaleDateString('en-GB') : '-' }}
                  </td>

                  <td class="px-4 py-4 font-black text-rose-700">
                    {{ Number(depreciation.amount || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4 font-black text-slate-800">
                    {{ depreciation.journal_entry?.entry_number || '-' }}
                  </td>

                  <td class="px-4 py-4">
                    {{ depreciation.notes || '-' }}
                  </td>
                </tr>

                <tr v-if="!props.asset.depreciations?.length">
                  <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">
                    لم يتم تسجيل أي إهلاك لهذا الأصل بعد.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardBox>

        <CardBox>
          <CardBoxComponentHeader title="قيد شراء الأصل" />

          <div class="overflow-x-auto">
            <table class="min-w-full text-right">
              <thead class="bg-slate-50">
                <tr class="text-sm text-slate-600">
                  <th class="px-4 py-4 font-black">الحساب</th>
                  <th class="px-4 py-4 font-black">البيان</th>
                  <th class="px-4 py-4 font-black">مدين</th>
                  <th class="px-4 py-4 font-black">دائن</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="line in props.asset.journal_entry?.lines || []"
                  :key="line.id"
                  class="border-t text-sm text-slate-700"
                >
                  <td class="px-4 py-4">
                    <div class="flex flex-col">
                      <span class="font-black">{{ line.account?.name || '-' }}</span>
                      <span class="text-xs text-slate-400">{{ line.account?.code || '-' }}</span>
                    </div>
                  </td>

                  <td class="px-4 py-4">{{ line.description || '-' }}</td>

                  <td class="px-4 py-4 font-black text-emerald-700">{{ Number(line.debit || 0).toFixed(2) }}</td>
                  <td class="px-4 py-4 font-black text-rose-700">{{ Number(line.credit || 0).toFixed(2) }}</td>
                </tr>

                <tr v-if="!props.asset.journal_entry?.lines?.length">
                  <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">
                    لا يوجد قيد شراء مرتبط بهذا الأصل.
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
    direction: rtl;
  }

  table {
    page-break-inside: avoid;
  }

  @page {
    size: A4 portrait;
    margin: 12mm;
  }
}
</style>