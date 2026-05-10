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
  paymentVouchers: Object,
  filters: Object,
  permissions: Object,
})

const search = ref(props.filters?.search ?? '')
const vouchersData = computed(() => props.paymentVouchers?.data ?? [])

const totalAmount = computed(() => {
  return vouchersData.value.reduce((sum, item) => sum + Number(item.amount || 0), 0)
})

const postedAmount = computed(() => {
  return vouchersData.value
    .filter((item) => item.status === 'posted')
    .reduce((sum, item) => sum + Number(item.amount || 0), 0)
})

const cancelledAmount = computed(() => {
  return vouchersData.value
    .filter((item) => item.status === 'cancelled')
    .reduce((sum, item) => sum + Number(item.amount || 0), 0)
})

const beneficiaryTypeLabel = (type) => {
  const labels = {
    supplier: 'مورد',
    customer: 'عميل',
    employee: 'موظف',
    salary: 'راتب',
    partner: 'شريك',
    expense: 'مصروف عام',
  }

  return labels[type] || '-'
}

const statusLabel = (status) => {
  const labels = {
    posted: 'مرحل',
    draft: 'مسودة',
    cancelled: 'ملغي',
  }

  return labels[status] || '-'
}

const referenceLabel = (voucher) => {
  if (voucher.reference_type === 'purchase_invoice') {
    return 'فاتورة شراء'
  }

  return 'غير مرتبط'
}

const submitSearch = () => {
  router.get(
    '/payment-vouchers',
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}
</script>

<template>
  <MainLayout title="إيصالات الصرف">
    <div class="space-y-6">
      <PageHero
        badge="الخزينة / الصرف"
        title="إيصالات الصرف"
        description="إدارة عمليات الصرف من الخزينة أو البنك، مع دعم الصرف العام والصرف المرتبط بفواتير الشراء والقيود العكسية للإلغاء."
        gradient-class="bg-gradient-to-br from-rose-900 via-slate-900 to-orange-800"
      />

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الإيصالات</div>
          <div class="mt-3 text-3xl font-black text-slate-800">
            {{ props.paymentVouchers?.total ?? 0 }}
          </div>
          <div class="mt-2 text-xs text-slate-400">إجمالي إيصالات الصرف</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الصفحة</div>
          <div class="mt-3 text-3xl font-black text-rose-700">
            {{ totalAmount.toFixed(2) }}
          </div>
          <div class="mt-2 text-xs text-slate-400">كل الإيصالات الظاهرة</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">مصروف فعلي</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">
            {{ postedAmount.toFixed(2) }}
          </div>
          <div class="mt-2 text-xs text-slate-400">الإيصالات المرحلة فقط</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">ملغي</div>
          <div class="mt-3 text-3xl font-black text-slate-500">
            {{ cancelledAmount.toFixed(2) }}
          </div>
          <div class="mt-2 text-xs text-slate-400">ملغى بقيود عكسية</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي برقم الإيصال أو الوصف أو نوع المستفيد أو فاتورة الشراء"
        :create-href="props.permissions?.canCreate ? '/payment-vouchers/create' : null"
        create-label="إضافة إيصال صرف"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة إيصالات الصرف" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">رقم الإيصال</th>
                <th class="px-4 py-4 font-black">التاريخ</th>
                <th class="px-4 py-4 font-black">الفرع</th>
                <th class="px-4 py-4 font-black">من حساب</th>
                <th class="px-4 py-4 font-black">المستفيد</th>
                <th class="px-4 py-4 font-black">المرجع</th>
                <th class="px-4 py-4 font-black">المبلغ</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(voucher, index) in vouchersData"
                :key="voucher.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
                :class="voucher.status === 'cancelled' ? 'bg-slate-50/70 opacity-80' : ''"
              >
                <td class="px-4 py-4">
                  {{ ((props.paymentVouchers.current_page - 1) * props.paymentVouchers.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <Link :href="`/payment-vouchers/${voucher.id}`">
                    <span class="rounded-xl bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 transition hover:bg-rose-100 hover:text-rose-700">
                      {{ voucher.voucher_number }}
                    </span>
                  </Link>
                </td>

                <td class="px-4 py-4">
                  {{ voucher.voucher_date ? new Date(voucher.voucher_date).toLocaleDateString('en-GB') : '-' }}
                </td>

                <td class="px-4 py-4">
                  {{ voucher.branch?.name || '-' }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-bold">{{ voucher.financial_account?.name || '-' }}</span>
                    <span class="text-xs text-slate-400">{{ voucher.payment_method?.name || '-' }}</span>
                  </div>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">
                      {{ beneficiaryTypeLabel(voucher.beneficiary_type) }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">
                      {{ voucher.account?.name || voucher.expense_category?.name || '-' }}
                    </span>
                  </div>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col gap-1">
                    <span
                      class="w-fit rounded-xl px-3 py-1 text-xs font-black"
                      :class="voucher.reference_type === 'purchase_invoice'
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-100 text-slate-500'"
                    >
                      {{ referenceLabel(voucher) }}
                    </span>

                    <span
                      v-if="voucher.reference_type === 'purchase_invoice'"
                      class="text-xs font-bold text-slate-400"
                    >
                      مرجع رقم: {{ voucher.reference_id }}
                    </span>
                  </div>
                </td>

                <td class="px-4 py-4 font-black text-rose-700">
                  {{ Number(voucher.amount || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="voucher.status === 'cancelled'
                      ? 'bg-rose-100 text-rose-700'
                      : voucher.status === 'posted'
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-amber-100 text-amber-700'"
                  >
                    {{ statusLabel(voucher.status) }}
                  </span>

                  <div
                    v-if="voucher.status === 'cancelled'"
                    class="mt-1 text-xs font-bold text-rose-500"
                  >
                    ملغي بقيد عكسي
                  </div>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/payment-vouchers/${voucher.id}`">
                      <BaseButton label="عرض" color="info" small />
                    </Link>

                    <Link
                      v-if="props.permissions?.canUpdate && voucher.status !== 'cancelled'"
                      :href="`/payment-vouchers/${voucher.id}/edit`"
                    >
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <Link
                      v-if="voucher.reference_type === 'purchase_invoice'"
                      :href="`/purchase-invoices/${voucher.reference_id}`"
                    >
                      <BaseButton label="الفاتورة" color="light" small />
                    </Link>
                  </div>
                </td>
              </tr>

              <tr v-if="!vouchersData.length">
                <td colspan="10" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد إيصالات صرف مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.paymentVouchers.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.paymentVouchers.links"
          :key="link.label"
          class="rounded-2xl border px-4 py-2 text-sm font-semibold transition"
          :class="[
            link.active
              ? 'border-rose-600 bg-rose-600 text-white'
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