<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHero from '@/Components/App/PageHero.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import FormControl from '@/Components/FormControl.vue'

const props = defineProps({
  assets: Array,
  filters: Object,
  branches: Array,
  isAdmin: Boolean,
  totals: Object,
})

const search = ref(props.filters?.search ?? '')
const branchId = ref(props.filters?.branch_id ?? '')
const status = ref(props.filters?.status ?? '')

const assetsData = computed(() => props.assets ?? [])

const applyFilters = () => {
  router.get('/fixed-assets-report', {
    search: search.value,
    branch_id: branchId.value,
    status: status.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const resetFilters = () => {
  search.value = ''
  branchId.value = ''
  status.value = ''

  router.get('/fixed-assets-report', {}, {
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
  <MainLayout title="تقرير الأصول الثابتة">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="تقارير / الأصول"
          title="تقرير الأصول الثابتة"
          description="تقرير شامل لقيم الأصول، مجمع الإهلاك، القيمة الدفترية، والمتبقي للإهلاك حسب الفرع والحالة."
          gradient-class="bg-gradient-to-br from-indigo-900 via-slate-900 to-cyan-900"
        />
      </div>

      <div class="no-print flex justify-end">
        <BaseButton label="طباعة التقرير" color="info" @click="printReport" />
      </div>

      <section class="no-print grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي قيمة الشراء</div>
          <div class="mt-3 text-3xl font-black text-indigo-700">
            {{ Number(props.totals?.purchase_value || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الإهلاك</div>
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
          <div class="text-sm font-bold text-slate-500">المتبقي للإهلاك</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">
            {{ Number(props.totals?.remaining_depreciation || 0).toFixed(2) }}
          </div>
        </div>
      </section>

      <CardBox class="no-print">
        <CardBoxComponentHeader title="فلاتر التقرير" />

        <div class="grid gap-4 p-6 md:grid-cols-5">
          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">بحث</label>
            <FormControl
              v-model="search"
              type="text"
              placeholder="اسم الأصل أو الكود"
            />
          </div>

          <div v-if="props.isAdmin">
            <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
            <select
              v-model="branchId"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            >
              <option value="">كل الفروع</option>
              <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
                {{ branch.name }} - {{ branch.code }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">الحالة</label>
            <select
              v-model="status"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            >
              <option value="">كل الحالات</option>
              <option value="1">فعال</option>
              <option value="0">موقوف</option>
            </select>
          </div>

          <div class="flex items-end gap-2 md:col-span-2">
            <BaseButton label="تطبيق الفلترة" color="primary" @click="applyFilters" />
            <BaseButton label="مسح" color="light" @click="resetFilters" />
          </div>
        </div>
      </CardBox>

      <div id="print-area">
        <div class="hidden print:block text-center">
          <h1 class="text-2xl font-black">بنيس للحديد الصناعي</h1>
          <h2 class="mt-2 text-xl font-black">تقرير الأصول الثابتة</h2>
          <p class="mt-1 text-sm font-bold">
            تاريخ الطباعة: {{ new Date().toLocaleDateString('en-GB') }}
          </p>
        </div>

        <CardBox>
          <CardBoxComponentHeader title="بيانات التقرير" />

          <div class="overflow-x-auto">
            <table class="min-w-full text-right">
              <thead class="bg-slate-50">
                <tr class="text-sm text-slate-600">
                  <th class="px-4 py-4 font-black">الكود</th>
                  <th class="px-4 py-4 font-black">الأصل</th>
                  <th class="px-4 py-4 font-black">الفرع</th>
                  <th class="px-4 py-4 font-black">تاريخ الشراء</th>
                  <th class="px-4 py-4 font-black">قيمة الشراء</th>
                  <th class="px-4 py-4 font-black">القيمة المتبقية</th>
                  <th class="px-4 py-4 font-black">القابل للإهلاك</th>
                  <th class="px-4 py-4 font-black">مجمع الإهلاك</th>
                  <th class="px-4 py-4 font-black">القيمة الدفترية</th>
                  <th class="px-4 py-4 font-black">إهلاك شهري</th>
                  <th class="px-4 py-4 font-black">المتبقي للإهلاك</th>
                  <th class="px-4 py-4 font-black">الحالة</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="asset in assetsData"
                  :key="asset.id"
                  class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
                >
                  <td class="px-4 py-4 font-bold">
                    {{ asset.asset_code }}
                  </td>

                  <td class="px-4 py-4">
                    <div class="flex flex-col">
                      <span class="font-black text-slate-800">{{ asset.name }}</span>
                      <span class="text-xs text-slate-400">
                        {{ asset.asset_account?.name || '-' }}
                      </span>
                    </div>
                  </td>

                  <td class="px-4 py-4">
                    {{ asset.branch?.name || '-' }}
                  </td>

                  <td class="px-4 py-4">
                    {{ asset.purchase_date ? new Date(asset.purchase_date).toLocaleDateString('en-GB') : '-' }}
                  </td>

                  <td class="px-4 py-4 font-black text-indigo-700">
                    {{ Number(asset.purchase_value || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4 font-black text-slate-700">
                    {{ Number(asset.salvage_value || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4 font-black text-slate-700">
                    {{ Number(asset.depreciable_value || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4 font-black text-rose-700">
                    {{ Number(asset.total_depreciation || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4 font-black text-cyan-700">
                    {{ Number(asset.book_value || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4 font-black text-emerald-700">
                    {{ Number(asset.monthly_depreciation || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4 font-black text-amber-700">
                    {{ Number(asset.remaining_depreciation || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4">
                    <span
                      class="rounded-full px-3 py-1 text-xs font-bold"
                      :class="asset.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                    >
                      {{ asset.is_active ? 'فعال' : 'موقوف' }}
                    </span>
                  </td>
                </tr>

                <tr v-if="!assetsData.length">
                  <td colspan="12" class="px-4 py-14 text-center text-sm text-slate-500">
                    لا توجد أصول مطابقة للفلاتر.
                  </td>
                </tr>
              </tbody>

              <tfoot v-if="assetsData.length" class="bg-slate-100">
                <tr class="text-sm font-black text-slate-800">
                  <td colspan="4" class="px-4 py-4">الإجمالي</td>
                  <td class="px-4 py-4 text-indigo-700">
                    {{ Number(props.totals?.purchase_value || 0).toFixed(2) }}
                  </td>
                  <td class="px-4 py-4">-</td>
                  <td class="px-4 py-4">-</td>
                  <td class="px-4 py-4 text-rose-700">
                    {{ Number(props.totals?.total_depreciation || 0).toFixed(2) }}
                  </td>
                  <td class="px-4 py-4 text-cyan-700">
                    {{ Number(props.totals?.book_value || 0).toFixed(2) }}
                  </td>
                  <td class="px-4 py-4">-</td>
                  <td class="px-4 py-4 text-amber-700">
                    {{ Number(props.totals?.remaining_depreciation || 0).toFixed(2) }}
                  </td>
                  <td class="px-4 py-4">-</td>
                </tr>
              </tfoot>
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
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
  }

  th,
  td {
    border: 1px solid #ddd;
    padding: 5px;
  }

  @page {
    size: A4 landscape;
    margin: 10mm;
  }
}
</style>