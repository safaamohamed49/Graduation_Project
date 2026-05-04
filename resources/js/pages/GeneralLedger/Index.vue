<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'
import FormControl from '@/Components/FormControl.vue'

const props = defineProps({
  lines: Object,
  printLines: Array,
  filters: Object,
  totals: Object,
  accounts: Array,
  branches: Array,
  isAdmin: Boolean,
})

const form = ref({
  search: props.filters?.search ?? '',
  date_from: props.filters?.date_from ?? '',
  date_to: props.filters?.date_to ?? '',
  account_id: props.filters?.account_id ?? '',
  branch_id: props.filters?.branch_id ?? '',
  status: props.filters?.status ?? '',
  source_type: props.filters?.source_type ?? '',
})

const linesData = computed(() => props.lines?.data ?? [])

const balance = computed(() => {
  return Number(props.totals?.debit || 0) - Number(props.totals?.credit || 0)
})

const sourceTypeLabel = (sourceType) => {
  if (!sourceType) return '-'

  if (sourceType.includes('ReceiptVoucher')) return 'إيصال قبض'
  if (sourceType.includes('PaymentVoucher')) return 'إيصال صرف'
  if (sourceType.includes('PurchaseInvoice')) return 'فاتورة شراء'
  if (sourceType.includes('Order')) return 'فاتورة بيع'
  if (sourceType.includes('ReturnInvoice')) return 'مرتجع'
  if (sourceType.includes('StockTransfer')) return 'تحويل مخزني'

  return sourceType
}

const statusLabel = (status) => {
  const labels = {
    posted: 'مرحل',
    draft: 'مسودة',
    cancelled: 'ملغي',
  }

  return labels[status] || status || '-'
}

const applyFilters = () => {
  router.get('/general-ledger', form.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const resetFilters = () => {
  form.value = {
    search: '',
    date_from: '',
    date_to: '',
    account_id: '',
    branch_id: '',
    status: '',
    source_type: '',
  }

  router.get('/general-ledger', {}, {
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
  <MainLayout title="دفتر الأستاذ">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="التقارير / المحاسبة"
          title="دفتر الأستاذ"
          description="عرض كل الحركات المحاسبية المرحلة مع إمكانية الفلترة والطباعة حسب الحساب أو الفرع أو التاريخ."
          gradient-class="bg-gradient-to-br from-indigo-900 via-slate-900 to-cyan-900"
        />
      </div>

      <section class="no-print grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي المدين</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">
            {{ Number(props.totals?.debit || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الدائن</div>
          <div class="mt-3 text-3xl font-black text-rose-700">
            {{ Number(props.totals?.credit || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الفرق</div>
          <div
            class="mt-3 text-3xl font-black"
            :class="balance === 0 ? 'text-slate-800' : 'text-amber-700'"
          >
            {{ balance.toFixed(2) }}
          </div>
          <div class="mt-2 text-xs text-slate-400">يفضل يكون صفر عند عرض كل الحسابات</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الحركات</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">
            {{ props.lines?.total ?? 0 }}
          </div>
        </div>
      </section>

      <CardBox class="no-print">
        <CardBoxComponentHeader title="فلاتر دفتر الأستاذ" />

        <div class="grid gap-4 p-6 md:grid-cols-4">
          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">بحث</label>
            <FormControl
              v-model="form.search"
              type="text"
              placeholder="رقم القيد / الحساب / البيان"
            />
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">من تاريخ</label>
            <FormControl v-model="form.date_from" type="date" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">إلى تاريخ</label>
            <FormControl v-model="form.date_to" type="date" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">الحساب</label>
            <select
              v-model="form.account_id"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            >
              <option value="">كل الحسابات</option>
              <option v-for="account in props.accounts" :key="account.id" :value="account.id">
                {{ account.code }} - {{ account.name }}
              </option>
            </select>
          </div>

          <div v-if="props.isAdmin">
            <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
            <select
              v-model="form.branch_id"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            >
              <option value="">كل الفروع</option>
              <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">الحالة</label>
            <select
              v-model="form.status"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            >
              <option value="">كل الحالات</option>
              <option value="posted">مرحل</option>
              <option value="draft">مسودة</option>
              <option value="cancelled">ملغي</option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">مصدر الحركة</label>
            <select
              v-model="form.source_type"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            >
              <option value="">كل المصادر</option>
              <option value="App\Models\ReceiptVoucher">إيصالات القبض</option>
              <option value="App\Models\PaymentVoucher">إيصالات الصرف</option>
              <option value="App\Models\PurchaseInvoice">فواتير الشراء</option>
              <option value="App\Models\Order">فواتير البيع</option>
              <option value="App\Models\ReturnInvoice">المرتجعات</option>
            </select>
          </div>

          <div class="flex items-end gap-2">
            <BaseButton label="تطبيق الفلترة" color="primary" @click="applyFilters" />
            <BaseButton label="مسح" color="light" @click="resetFilters" />
          </div>

          <div class="flex items-end justify-end">
            <BaseButton label="طباعة التقرير" color="info" @click="printReport" />
          </div>
        </div>
      </CardBox>

      <CardBox class="no-print">
        <CardBoxComponentHeader title="الحركات المحاسبية" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">التاريخ</th>
                <th class="px-4 py-4 font-black">رقم القيد</th>
                <th class="px-4 py-4 font-black">الفرع</th>
                <th class="px-4 py-4 font-black">الحساب</th>
                <th class="px-4 py-4 font-black">البيان</th>
                <th class="px-4 py-4 font-black">المصدر</th>
                <th class="px-4 py-4 font-black">مدين</th>
                <th class="px-4 py-4 font-black">دائن</th>
                <th class="px-4 py-4 font-black">الحالة</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="line in linesData"
                :key="line.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50"
              >
                <td class="px-4 py-4">
                  {{ line.journal_entry?.entry_date ? new Date(line.journal_entry.entry_date).toLocaleDateString('en-GB') : '-' }}
                </td>

                <td class="px-4 py-4 font-black text-slate-800">
                  {{ line.journal_entry?.entry_number || '-' }}
                </td>

                <td class="px-4 py-4">
                  {{ line.journal_entry?.branch?.name || '-' }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black">{{ line.account?.name || '-' }}</span>
                    <span class="text-xs text-slate-400">{{ line.account?.code || '-' }}</span>
                  </div>
                </td>

                <td class="px-4 py-4">
                  {{ line.description || line.journal_entry?.description || '-' }}
                </td>

                <td class="px-4 py-4">
                  {{ sourceTypeLabel(line.journal_entry?.source_type) }}
                </td>

                <td class="px-4 py-4 font-black text-emerald-700">
                  {{ Number(line.debit || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-rose-700">
                  {{ Number(line.credit || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  {{ statusLabel(line.journal_entry?.status) }}
                </td>
              </tr>

              <tr v-if="!linesData.length">
                <td colspan="9" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد حركات مطابقة للفلاتر.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.lines.links?.length > 3" class="no-print flex flex-wrap gap-2">
        <button
          v-for="link in props.lines.links"
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

      <div id="print-area" class="hidden print:block">
        <div class="text-center">
          <h1 class="text-2xl font-black">دفتر الأستاذ</h1>
          <div class="mt-2 text-sm font-bold">تقرير حسب الفلاتر المختارة</div>
        </div>

        <div class="mt-6 grid grid-cols-3 gap-3 text-sm">
          <div>إجمالي المدين: {{ Number(props.totals?.debit || 0).toFixed(2) }}</div>
          <div>إجمالي الدائن: {{ Number(props.totals?.credit || 0).toFixed(2) }}</div>
          <div>الفرق: {{ balance.toFixed(2) }}</div>
        </div>

        <table class="mt-6 min-w-full text-right text-sm">
          <thead>
            <tr>
              <th>التاريخ</th>
              <th>رقم القيد</th>
              <th>الفرع</th>
              <th>الحساب</th>
              <th>البيان</th>
              <th>مدين</th>
              <th>دائن</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="line in props.printLines" :key="line.id">
              <td>{{ line.journal_entry?.entry_date ? new Date(line.journal_entry.entry_date).toLocaleDateString('en-GB') : '-' }}</td>
              <td>{{ line.journal_entry?.entry_number || '-' }}</td>
              <td>{{ line.journal_entry?.branch?.name || '-' }}</td>
              <td>{{ line.account?.code || '-' }} - {{ line.account?.name || '-' }}</td>
              <td>{{ line.description || line.journal_entry?.description || '-' }}</td>
              <td>{{ Number(line.debit || 0).toFixed(2) }}</td>
              <td>{{ Number(line.credit || 0).toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
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
    display: block !important;
    direction: rtl;
    padding: 20px;
  }

  #print-area table {
    width: 100%;
    border-collapse: collapse;
  }

  #print-area th,
  #print-area td {
    border: 1px solid #ddd;
    padding: 8px;
  }

  #print-area th {
    background: #f1f5f9;
  }
}
</style>