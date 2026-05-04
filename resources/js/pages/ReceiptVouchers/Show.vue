<script setup>
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  receiptVoucher: Object,
  reverseEntry: Object,
  permissions: Object,
  receivedFromName: String,
  amountInWords: String,
})

const receivedFromTypeLabel = (type) => {
  const labels = {
    customer: 'عميل',
    supplier: 'مورد',
    employee: 'موظف',
    partner: 'شريك',
    other: 'أخرى',
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

const printVoucher = () => {
  window.print()
}

const cancelVoucher = () => {
  if (!confirm('هل أنتِ متأكدة من إلغاء هذا الإيصال؟ سيتم إنشاء قيد عكسي تلقائياً.')) {
    return
  }

  router.post(`/receipt-vouchers/${props.receiptVoucher.id}/cancel`, {}, {
    preserveScroll: true,
  })
}
</script>

<template>
  <MainLayout :title="props.receiptVoucher.voucher_number">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="تفاصيل إيصال القبض"
          :title="props.receiptVoucher.voucher_number"
          description="عرض تفاصيل إيصال القبض والقيد المحاسبي المرتبط به."
          gradient-class="bg-gradient-to-br from-emerald-900 via-slate-900 to-cyan-800"
        />
      </div>

      <div class="no-print flex flex-wrap justify-end gap-3">
        <Link href="/receipt-vouchers">
          <BaseButton label="رجوع" color="light" />
        </Link>

        <BaseButton label="طباعة الإيصال" color="info" @click="printVoucher" />

        <Link
          v-if="props.permissions?.canUpdate"
          :href="`/receipt-vouchers/${props.receiptVoucher.id}/edit`"
        >
          <BaseButton label="تعديل الإيصال" color="warning" />
        </Link>

        <BaseButton
          v-if="props.permissions?.canCancel"
          label="إلغاء الإيصال"
          color="danger"
          @click="cancelVoucher"
        />
      </div>

      <div id="print-area" class="space-y-6">
        <section class="official-voucher bg-white text-slate-950 shadow-sm ring-1 ring-slate-300">
          <div class="voucher-top">
            <div class="company-block">
              <div class="company-name">بنيس للحديد الصناعي</div>
              <div class="company-subtitle">
                الفرع المصدر للإيصال: {{ props.receiptVoucher.branch?.name || '-' }}
              </div>
            </div>

            <div class="voucher-title-block">
              <div class="voucher-title">إيصال قبض</div>
              <div class="voucher-title-en">RECEIPT VOUCHER</div>
            </div>

            <div class="voucher-number-block">
              <div class="small-label">رقم الإيصال</div>
              <div class="voucher-number">{{ props.receiptVoucher.voucher_number }}</div>
              <div
                v-if="props.receiptVoucher.status === 'cancelled'"
                class="cancel-stamp"
              >
                ملغي
              </div>
            </div>
          </div>

          <div class="voucher-row three">
            <div class="voucher-cell">
              <span>التاريخ</span>
              <strong>
                {{ props.receiptVoucher.voucher_date ? new Date(props.receiptVoucher.voucher_date).toLocaleDateString('en-GB') : '-' }}
              </strong>
            </div>

            <div class="voucher-cell">
              <span>طريقة القبض</span>
              <strong>{{ props.receiptVoucher.payment_method?.name || '-' }}</strong>
            </div>

            <div class="voucher-cell">
              <span>الخزينة / البنك</span>
              <strong>{{ props.receiptVoucher.financial_account?.name || '-' }}</strong>
            </div>
          </div>

          <div class="voucher-row two">
            <div class="voucher-cell">
              <span>استلمنا من السيد / الجهة</span>
              <strong>{{ props.receivedFromName || '-' }}</strong>
            </div>

            <div class="voucher-cell">
              <span>نوع المقبوض منه</span>
              <strong>{{ receivedFromTypeLabel(props.receiptVoucher.received_from_type) }}</strong>
            </div>
          </div>

          <div class="amount-line">
            <div class="amount-box">
              <span>المبلغ رقماً</span>
              <strong>{{ Number(props.receiptVoucher.amount || 0).toFixed(2) }} د.ل</strong>
            </div>

            <div class="words-box">
              <span>المبلغ كتابةً</span>
              <strong>{{ props.amountInWords || '-' }}</strong>
            </div>
          </div>

          <div class="voucher-cell description-cell">
            <span>البيان</span>
            <strong class="whitespace-pre-line">{{ props.receiptVoucher.description || '-' }}</strong>
          </div>

          <div class="accounting-strip">
            <div>
              <span>الحساب المدين</span>
              <strong>{{ props.receiptVoucher.financial_account?.name || '-' }}</strong>
            </div>

            <div>
              <span>الحساب الدائن</span>
              <strong>{{ props.receiptVoucher.account?.name || '-' }}</strong>
            </div>

            <div>
              <span>أعد الإيصال</span>
              <strong>{{ props.receiptVoucher.created_by?.name || '-' }}</strong>
            </div>
          </div>

          <div class="signature-strip">
            <div>توقيع الدافع</div>
            <div>توقيع المحاسب</div>
            <div>اعتماد الإدارة</div>
          </div>

          <div class="footer-note">
            هذا الإيصال صادر من فرع: {{ props.receiptVoucher.branch?.name || '-' }} — فقط لا يعتمد إلا بتوقيع المخولين.
          </div>
        </section>

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
                  v-for="line in props.receiptVoucher.journal_entry?.lines || []"
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

                <tr v-if="!props.receiptVoucher.journal_entry?.lines?.length">
                  <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">
                    لا يوجد قيد محاسبي مرتبط بهذا الإيصال.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardBox>

        <CardBox v-if="props.reverseEntry" class="no-print">
          <CardBoxComponentHeader title="القيد العكسي للإلغاء" />

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
                  v-for="line in props.reverseEntry.lines || []"
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
      </div>
    </div>
  </MainLayout>
</template>

<style>
.official-voucher {
  direction: rtl;
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
  border: 2px solid #0f172a;
  padding: 18px;
  border-radius: 10px;
  font-family: Arial, sans-serif;
}

.voucher-top {
  display: grid;
  grid-template-columns: 1fr 1.1fr 1fr;
  align-items: start;
  gap: 12px;
  border-bottom: 2px solid #0f172a;
  padding-bottom: 12px;
  margin-bottom: 12px;
}

.company-name {
  font-size: 22px;
  font-weight: 900;
  color: #0f172a;
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
  letter-spacing: 1px;
}

.voucher-number-block {
  text-align: left;
  position: relative;
}

.small-label,
.voucher-cell span,
.amount-box span,
.words-box span,
.accounting-strip span {
  display: block;
  font-size: 11px;
  font-weight: 900;
  color: #64748b;
  margin-bottom: 4px;
}

.voucher-number {
  font-size: 18px;
  font-weight: 900;
  color: #0f172a;
}

.cancel-stamp {
  display: inline-block;
  margin-top: 6px;
  border: 2px solid #e11d48;
  color: #e11d48;
  padding: 2px 14px;
  font-size: 17px;
  font-weight: 900;
  transform: rotate(-6deg);
}

.voucher-row {
  display: grid;
  gap: 8px;
  margin-top: 8px;
}

.voucher-row.three {
  grid-template-columns: repeat(3, 1fr);
}

.voucher-row.two {
  grid-template-columns: 2fr 1fr;
}

.voucher-cell,
.amount-box,
.words-box {
  border: 1px solid #334155;
  padding: 9px 10px;
  min-height: 56px;
}

.voucher-cell strong,
.amount-box strong,
.words-box strong,
.accounting-strip strong {
  display: block;
  font-size: 15px;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.5;
}

.amount-line {
  display: grid;
  grid-template-columns: 1fr 2.2fr;
  gap: 8px;
  margin-top: 8px;
}

.amount-box {
  border: 2px solid #0f172a;
}

.amount-box strong {
  font-size: 21px;
}

.words-box {
  background: #f8fafc;
  border: 2px solid #0f172a;
}

.description-cell {
  margin-top: 8px;
  min-height: 68px;
}

.accounting-strip {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-top: 8px;
}

.accounting-strip > div {
  border: 1px solid #cbd5e1;
  padding: 8px 10px;
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
    padding: 0 !important;
  }

  .official-voucher {
    max-width: 100% !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    margin: 0 !important;
    padding: 12px !important;
    border: 2px solid #000 !important;
  }

  .company-name {
    font-size: 20px !important;
  }

  .voucher-title {
    font-size: 22px !important;
    padding: 6px 24px !important;
  }

  .voucher-cell,
  .amount-box,
  .words-box {
    padding: 7px 8px !important;
    min-height: 48px !important;
  }

  .description-cell {
    min-height: 58px !important;
  }

  .signature-strip {
    margin-top: 34px !important;
  }

  @page {
    size: A4 portrait;
    margin: 12mm;
  }
}
</style>