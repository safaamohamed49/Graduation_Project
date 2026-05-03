<script setup>
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  paymentVoucher: Object,
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
</script>

<template>
  <MainLayout :title="props.paymentVoucher.voucher_number">
    <div class="space-y-6">
      <PageHero
        badge="تفاصيل إيصال الصرف"
        :title="props.paymentVoucher.voucher_number"
        description="عرض تفاصيل إيصال الصرف والقيد المحاسبي المرتبط به."
        gradient-class="bg-gradient-to-br from-rose-900 via-slate-900 to-orange-800"
      />

      <div class="flex justify-end gap-3">
        <Link href="/payment-vouchers">
          <BaseButton label="رجوع" color="light" />
        </Link>
      </div>

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المبلغ</div>
          <div class="mt-3 text-3xl font-black text-rose-700">
            {{ Number(props.paymentVoucher.amount || 0).toFixed(2) }}
          </div>
          <div class="mt-2 text-xs text-slate-400">دينار ليبي</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الحساب المصروف منه</div>
          <div class="mt-3 text-xl font-black text-slate-800">
            {{ props.paymentVoucher.financial_account?.name || '-' }}
          </div>
          <div class="mt-2 text-xs text-slate-400">الخزينة / البنك دائن</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الحالة</div>
          <div class="mt-3">
            <span
              class="rounded-full px-4 py-2 text-sm font-black"
              :class="props.paymentVoucher.status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
            >
              {{ props.paymentVoucher.status === 'posted' ? 'مرحل' : 'مسودة' }}
            </span>
          </div>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="بيانات الإيصال" />

        <div class="grid gap-5 p-6 md:grid-cols-2">
          <div>
            <div class="text-xs font-bold text-slate-400">رقم الإيصال</div>
            <div class="mt-2 font-black text-slate-800">{{ props.paymentVoucher.voucher_number }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">التاريخ</div>
            <div class="mt-2 font-black text-slate-800">
              {{ props.paymentVoucher.voucher_date ? new Date(props.paymentVoucher.voucher_date).toLocaleDateString('en-GB') : '-' }}
            </div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">الفرع</div>
            <div class="mt-2 font-black text-slate-800">{{ props.paymentVoucher.branch?.name || '-' }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">المستخدم الذي جهز الإيصال</div>
            <div class="mt-2 font-black text-slate-800">{{ props.paymentVoucher.created_by?.name || '-' }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">طريقة الدفع</div>
            <div class="mt-2 font-black text-slate-800">{{ props.paymentVoucher.payment_method?.name || '-' }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">نوع الصرف</div>
            <div class="mt-2 font-black text-slate-800">
              {{ beneficiaryTypeLabel(props.paymentVoucher.beneficiary_type) }}
            </div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">الحساب المدين</div>
            <div class="mt-2 font-black text-emerald-700">
              {{ props.paymentVoucher.account?.name || '-' }}
            </div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">الحساب الدائن</div>
            <div class="mt-2 font-black text-rose-700">
              {{ props.paymentVoucher.financial_account?.name || '-' }}
            </div>
          </div>

          <div v-if="props.paymentVoucher.expense_category" class="md:col-span-2">
            <div class="text-xs font-bold text-slate-400">تصنيف المصروف</div>
            <div class="mt-2 font-black text-slate-800">
              {{ props.paymentVoucher.expense_category.name }}
            </div>
          </div>

          <div class="md:col-span-2">
            <div class="text-xs font-bold text-slate-400">البيان</div>
            <div class="mt-2 font-black text-slate-800">
              {{ props.paymentVoucher.description || '-' }}
            </div>
          </div>
        </div>
      </CardBox>

      <CardBox>
        <CardBoxComponentHeader title="القيد المحاسبي" />

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
                v-for="line in props.paymentVoucher.journal_entry?.lines || []"
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

                <td class="px-4 py-4 font-black text-emerald-700">
                  {{ Number(line.debit || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-rose-700">
                  {{ Number(line.credit || 0).toFixed(2) }}
                </td>
              </tr>

              <tr v-if="!props.paymentVoucher.journal_entry?.lines?.length">
                <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">
                  لا يوجد قيد محاسبي مرتبط بهذا الإيصال.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>
    </div>
  </MainLayout>
</template>