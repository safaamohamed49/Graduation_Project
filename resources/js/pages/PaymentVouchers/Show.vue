<script setup>
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  paymentVoucher: Object,
  reverseEntry: Object,
  linkedPurchaseInvoice: Object,
  beneficiaryName: String,
  amountInWords: String,
  permissions: Object,
})

const printPage = () => {
  window.print()
}

const cancelVoucher = () => {
  if (!confirm('هل أنت متأكد من إلغاء هذا الإيصال؟ سيتم إنشاء قيد عكسي وتحديث الفاتورة المرتبطة.')) {
    return
  }

  router.post(`/payment-vouchers/${props.paymentVoucher.id}/cancel`)
}

const beneficiaryTypeLabel = (type) => {
  const labels = {
    supplier: 'مورد',
    customer: 'عميل',
    employee: 'موظف',
    salary: 'راتب',
    partner: 'شريك',
    expense: 'مصروف عام',
  }

  return labels[type] || type || '-'
}

const voucherStatusLabel = (status) => {
  const labels = {
    posted: 'مرحل',
    cancelled: 'ملغي',
    draft: 'مسودة',
  }

  return labels[status] || status || '-'
}

const voucherStatusClass = (status) => {
  return {
    'bg-emerald-100 text-emerald-700': status === 'posted',
    'bg-rose-100 text-rose-700': status === 'cancelled',
    'bg-amber-100 text-amber-700': status === 'draft',
  }
}
</script>

<template>
  <MainLayout :title="paymentVoucher.voucher_number">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="إيصال صرف"
          :title="`إيصال صرف رقم ${paymentVoucher.voucher_number}`"
          description="عرض رسمي لإيصال الصرف، القيد المحاسبي، وأي ارتباط بفواتير شراء أو قيود عكسية."
          gradient-class="bg-gradient-to-br from-rose-900 via-slate-900 to-orange-800"
        />
      </div>

      <section class="no-print flex flex-wrap justify-end gap-3">
        <Link href="/payment-vouchers">
          <BaseButton label="رجوع" color="light" />
        </Link>

        <BaseButton label="طباعة الإيصال" color="info" @click="printPage" />

        <Link
          v-if="permissions?.canUpdate"
          :href="`/payment-vouchers/${paymentVoucher.id}/edit`"
        >
          <BaseButton label="تعديل" color="warning" />
        </Link>

        <BaseButton
          v-if="permissions?.canCancel"
          label="إلغاء الإيصال"
          color="danger"
          @click="cancelVoucher"
        />
      </section>

      <section class="grid gap-4 md:grid-cols-4 no-print">
        <div class="rounded-[24px] bg-white p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المبلغ</div>
          <div class="mt-2 text-2xl font-black text-slate-800">
            {{ Number(paymentVoucher.amount || 0).toFixed(2) }}
          </div>
        </div>

        <div class="rounded-[24px] bg-white p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المستفيد</div>
          <div class="mt-2 text-xl font-black text-slate-800">
            {{ beneficiaryName }}
          </div>
        </div>

        <div class="rounded-[24px] bg-white p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الحالة</div>
          <div class="mt-2">
            <span
              class="rounded-xl px-4 py-2 text-sm font-black"
              :class="voucherStatusClass(paymentVoucher.status)"
            >
              {{ voucherStatusLabel(paymentVoucher.status) }}
            </span>
          </div>
        </div>

        <div class="rounded-[24px] bg-white p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الحساب المالي</div>
          <div class="mt-2 text-lg font-black text-slate-800">
            {{ paymentVoucher.financial_account?.name || '-' }}
          </div>
        </div>
      </section>

      <div id="print-area">
        <section class="official-voucher bg-white text-slate-950 shadow-sm ring-1 ring-slate-300">
          <div class="voucher-top">
            <div class="company-block">
              <div class="company-name">بنيس للحديد الصناعي</div>

              <div class="company-subtitle">
                الفرع:
                {{ paymentVoucher.branch?.name || '-' }}
              </div>

              <div class="company-subtitle">
                نوع المستفيد:
                {{ beneficiaryTypeLabel(paymentVoucher.beneficiary_type) }}
              </div>
            </div>

            <div class="voucher-title-block">
              <div class="voucher-title">إيصال صرف</div>
              <div class="voucher-title-en">PAYMENT VOUCHER</div>
            </div>

            <div class="voucher-number-block">
              <div class="small-label">رقم الإيصال</div>
              <div class="voucher-number">{{ paymentVoucher.voucher_number }}</div>
            </div>
          </div>

          <div class="voucher-row four">
            <div class="voucher-cell">
              <span>التاريخ</span>
              <strong>
                {{
                  paymentVoucher.voucher_date
                    ? String(paymentVoucher.voucher_date).slice(0, 10)
                    : '-'
                }}
              </strong>
            </div>

            <div class="voucher-cell">
              <span>المستفيد</span>
              <strong>{{ beneficiaryName || '-' }}</strong>
            </div>

            <div class="voucher-cell">
              <span>طريقة الصرف</span>
              <strong>{{ paymentVoucher.payment_method?.name || '-' }}</strong>
            </div>

            <div class="voucher-cell">
              <span>الحالة</span>
              <strong>{{ voucherStatusLabel(paymentVoucher.status) }}</strong>
            </div>
          </div>

          <div class="voucher-row two">
            <div class="voucher-cell">
              <span>الحساب المالي</span>
              <strong>{{ paymentVoucher.financial_account?.name || '-' }}</strong>
            </div>

            <div class="voucher-cell">
              <span>المبلغ</span>
              <strong>{{ Number(paymentVoucher.amount || 0).toFixed(2) }} د.ل</strong>
            </div>
          </div>

          <div
            v-if="linkedPurchaseInvoice"
            class="linked-box"
          >
            <div class="linked-title">فاتورة شراء مرتبطة</div>

            <div class="voucher-row four">
              <div class="voucher-cell">
                <span>رقم الفاتورة</span>
                <strong>{{ linkedPurchaseInvoice.invoice_number }}</strong>
              </div>

              <div class="voucher-cell">
                <span>المورد</span>
                <strong>{{ linkedPurchaseInvoice.supplier?.name || '-' }}</strong>
              </div>

              <div class="voucher-cell">
                <span>إجمالي الفاتورة</span>
                <strong>{{ Number(linkedPurchaseInvoice.total_price || 0).toFixed(2) }}</strong>
              </div>

              <div class="voucher-cell">
                <span>المدفوع بعد هذا الإيصال</span>
                <strong>{{ Number(linkedPurchaseInvoice.paid_amount || 0).toFixed(2) }}</strong>
              </div>
            </div>
          </div>

          <div class="voucher-row one">
            <div class="voucher-cell description-cell">
              <span>البيان</span>
              <strong class="whitespace-pre-line">
                {{ paymentVoucher.description || '-' }}
              </strong>
            </div>
          </div>

          <div class="words-box">
            <span>المبلغ كتابةً</span>
            <strong>{{ amountInWords || '-' }}</strong>
          </div>

          <div class="signature-strip">
            <div>توقيع المستفيد</div>
            <div>توقيع أمين الخزينة</div>
            <div>اعتماد الإدارة</div>
          </div>

          <div class="footer-note">
            صادر من نظام بنيس للحديد الصناعي
          </div>
        </section>
      </div>

      <CardBox class="no-print">
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
                v-for="line in paymentVoucher.journal_entry?.lines || []"
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
            </tbody>
          </table>
        </div>
      </CardBox>

      <CardBox
        v-if="reverseEntry"
        class="no-print"
      >
        <CardBoxComponentHeader title="القيد العكسي للإلغاء" />

        <div class="rounded-[24px] bg-rose-50 p-5 ring-1 ring-rose-200">
          <div class="mb-4 text-sm font-bold text-rose-700">
            تم إلغاء هذا الإيصال، وتم إنشاء قيد عكسي.
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full text-right">
              <thead class="bg-white">
                <tr class="text-sm text-slate-600">
                  <th class="px-4 py-4 font-black">الحساب</th>
                  <th class="px-4 py-4 font-black">البيان</th>
                  <th class="px-4 py-4 font-black">مدين</th>
                  <th class="px-4 py-4 font-black">دائن</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="line in reverseEntry.lines || []"
                  :key="line.id"
                  class="border-t text-sm text-slate-700"
                >
                  <td class="px-4 py-4">
                    {{ line.account?.name || '-' }}
                  </td>

                  <td class="px-4 py-4">
                    {{ line.description || '-' }}
                  </td>

                  <td class="px-4 py-4 font-black text-emerald-700">
                    {{ Number(line.debit || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4 font-black text-rose-700">
                    {{ Number(line.credit || 0).toFixed(2) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </CardBox>
    </div>
  </MainLayout>
</template>

<style>
.official-voucher {
  direction: rtl;
  width: 100%;
  max-width: 960px;
  margin: 0 auto;
  border: 2px solid #0f172a;
  padding: 18px;
  border-radius: 10px;
  font-family: Arial, sans-serif;
}

.voucher-top {
  display: grid;
  grid-template-columns: 1fr 1.1fr 1fr;
  gap: 12px;
  border-bottom: 2px solid #0f172a;
  padding-bottom: 12px;
  margin-bottom: 12px;
}

.company-name {
  font-size: 22px;
  font-weight: 900;
}

.company-subtitle {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 800;
  color: #475569;
}

.voucher-title-block {
  text-align: center;
}

.voucher-title {
  display: inline-block;
  border: 2px solid #0f172a;
  padding: 8px 28px;
  font-size: 24px;
  font-weight: 900;
}

.voucher-title-en {
  margin-top: 4px;
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
}

.small-label,
.voucher-cell span,
.words-box span {
  display: block;
  font-size: 11px;
  font-weight: 900;
  color: #64748b;
  margin-bottom: 4px;
}

.voucher-number {
  font-size: 18px;
  font-weight: 900;
}

.voucher-row {
  display: grid;
  gap: 8px;
  margin-top: 8px;
}

.voucher-row.four {
  grid-template-columns: repeat(4, 1fr);
}

.voucher-row.two {
  grid-template-columns: repeat(2, 1fr);
}

.voucher-row.one {
  grid-template-columns: 1fr;
}

.voucher-cell,
.words-box {
  border: 1px solid #334155;
  padding: 10px;
}

.voucher-cell strong,
.words-box strong {
  display: block;
  font-size: 14px;
  font-weight: 900;
}

.linked-box {
  margin-top: 10px;
  border: 2px solid #fecaca;
  background: #fff1f2;
  padding: 12px;
}

.linked-title {
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 900;
  color: #9f1239;
}

.description-cell {
  min-height: 70px;
}

.words-box {
  margin-top: 10px;
  background: #f8fafc;
  border: 2px solid #0f172a;
}

.signature-strip {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-top: 42px;
  text-align: center;
  font-size: 13px;
  font-weight: 900;
}

.signature-strip > div {
  border-top: 1px solid #0f172a;
  padding-top: 8px;
}

.footer-note {
  margin-top: 12px;
  border-top: 1px dashed #94a3b8;
  padding-top: 8px;
  text-align: center;
  font-size: 11px;
  font-weight: 800;
  color: #475569;
}

@media print {
  aside,
  header,
  .no-print {
    display: none !important;
  }

  .official-voucher {
    max-width: 100%;
    border-radius: 0;
    box-shadow: none;
    margin: 0;
    padding: 10px;
  }

  @page {
    size: A4 portrait;
    margin: 10mm;
  }
}
</style>