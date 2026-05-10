<script setup>
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  invoice: Object,
  amountInWords: String,
  paymentVouchers: Array,
})

const printPage = () => {
  window.print()
}

const paymentStatusLabel = (status) => {
  const labels = {
    paid: 'مصروفة بالكامل',
    due: 'غير مصروفة',
    partial: 'مصروفة جزئيًا',
  }

  return labels[status] || status || '-'
}

const remainingAmount = () => {
  return Math.max(
    0,
    Number(props.invoice.total_price || 0) - Number(props.invoice.paid_amount || 0)
  )
}
</script>

<template>
  <MainLayout :title="props.invoice.invoice_number">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="فاتورة شراء"
          :title="`فاتورة شراء رقم ${props.invoice.invoice_number}`"
          description="عرض فاتورة الشراء الرسمية، قيود المخزون، وإيصالات الصرف المرتبطة بالفاتورة."
          gradient-class="bg-gradient-to-br from-emerald-800 via-teal-800 to-slate-950"
        />
      </div>

      <section class="no-print flex flex-wrap justify-end gap-3">
        <Link href="/purchase-invoices">
          <BaseButton label="رجوع" color="light" />
        </Link>

        <BaseButton label="طباعة الفاتورة" color="info" @click="printPage" />

        <Link
          v-if="!props.invoice.is_deleted"
          :href="`/purchase-invoices/${props.invoice.id}/edit`"
        >
          <BaseButton label="تعديل الفاتورة" color="warning" />
        </Link>
      </section>

      <div id="print-area" class="space-y-6">
        <section class="official-invoice bg-white text-slate-950 shadow-sm ring-1 ring-slate-300">
          <div class="invoice-top">
            <div class="company-block">
              <div class="company-name">بنيس للحديد الصناعي</div>

              <div class="company-subtitle">
                الفرع:
                {{ props.invoice.branch?.name || '-' }}
              </div>

              <div class="company-subtitle">
                المخزن:
                {{ props.invoice.warehouse?.name || '-' }}
              </div>
            </div>

            <div class="invoice-title-block">
              <div class="invoice-title">فاتورة شراء</div>
              <div class="invoice-title-en">PURCHASE INVOICE</div>
            </div>

            <div class="invoice-number-block">
              <div class="small-label">رقم الفاتورة</div>
              <div class="invoice-number">{{ props.invoice.invoice_number }}</div>
            </div>
          </div>

          <div class="invoice-row four">
            <div class="invoice-cell">
              <span>التاريخ</span>
              <strong>
                {{
                  props.invoice.invoice_date
                    ? String(props.invoice.invoice_date).slice(0, 10)
                    : '-'
                }}
              </strong>
            </div>

            <div class="invoice-cell">
              <span>المورد</span>
              <strong>{{ props.invoice.supplier?.name || '-' }}</strong>
            </div>

            <div class="invoice-cell">
              <span>كود المورد</span>
              <strong>{{ props.invoice.supplier?.code || '-' }}</strong>
            </div>

            <div class="invoice-cell">
              <span>حالة الصرف</span>
              <strong>{{ paymentStatusLabel(props.invoice.payment_status) }}</strong>
            </div>
          </div>

          <div class="invoice-row three">
            <div class="invoice-cell">
              <span>إجمالي المصروف</span>
              <strong>{{ Number(props.invoice.paid_amount || 0).toFixed(2) }}</strong>
            </div>

            <div class="invoice-cell">
              <span>المتبقي للمورد</span>
              <strong>{{ remainingAmount().toFixed(2) }}</strong>
            </div>

            <div class="invoice-cell">
              <span>حالة الفاتورة</span>
              <strong>{{ props.invoice.is_deleted ? 'محذوفة' : 'نشطة' }}</strong>
            </div>
          </div>

          <div class="items-box">
            <table class="invoice-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>الصنف</th>
                  <th>الوحدة</th>
                  <th>الكمية</th>
                  <th>سعر الشراء</th>
                  <th>الإجمالي</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(item, index) in props.invoice.items"
                  :key="item.id"
                >
                  <td>{{ index + 1 }}</td>

                  <td>
                    <strong>{{ item.product?.name || '-' }}</strong>
                    <div v-if="item.notes" class="item-note">{{ item.notes }}</div>
                  </td>

                  <td>{{ item.product?.unit_name || '-' }}</td>
                  <td>{{ Number(item.quantity || 0).toFixed(2) }}</td>
                  <td>{{ Number(item.price || 0).toFixed(2) }}</td>
                  <td>{{ (Number(item.quantity || 0) * Number(item.price || 0)).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="invoice-summary">
            <div class="summary-notes">
              <div class="invoice-cell description-cell">
                <span>البيان / الملاحظات</span>
                <strong class="whitespace-pre-line">{{ props.invoice.notes || '-' }}</strong>
              </div>

              <div class="words-box">
                <span>المبلغ كتابةً</span>
                <strong>{{ props.amountInWords || '-' }}</strong>
              </div>
            </div>

            <div class="summary-totals">
              <div>
                <span>الإجمالي قبل الخصم</span>
                <strong>{{ Number(props.invoice.subtotal || 0).toFixed(2) }}</strong>
              </div>

              <div>
                <span>الخصم</span>
                <strong>{{ Number(props.invoice.discount_amount || 0).toFixed(2) }}</strong>
              </div>

              <div>
                <span>مصاريف إضافية</span>
                <strong>{{ Number(props.invoice.total_expenses || 0).toFixed(2) }}</strong>
              </div>

              <div class="total-row">
                <span>الصافي</span>
                <strong>{{ Number(props.invoice.total_price || 0).toFixed(2) }} د.ل</strong>
              </div>

              <div>
                <span>المصروف</span>
                <strong>{{ Number(props.invoice.paid_amount || 0).toFixed(2) }}</strong>
              </div>

              <div>
                <span>المتبقي للمورد</span>
                <strong>{{ remainingAmount().toFixed(2) }}</strong>
              </div>
            </div>
          </div>

          <div class="signature-strip">
            <div>توقيع المورد</div>
            <div>توقيع المستلم</div>
            <div>اعتماد الإدارة</div>
          </div>

          <div class="footer-note">
            هذه الفاتورة صادرة من فرع:
            {{ props.invoice.branch?.name || '-' }}
            — بنيس للحديد الصناعي.
          </div>
        </section>

        <section class="no-print grid gap-4 md:grid-cols-4">
          <div class="rounded-[24px] bg-slate-50 p-5 ring-1 ring-slate-200">
            <div class="text-sm font-bold text-slate-500">إجمالي الفاتورة</div>
            <div class="mt-2 text-2xl font-black text-slate-800">
              {{ Number(props.invoice.total_price || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-[24px] bg-indigo-50 p-5 ring-1 ring-indigo-200">
            <div class="text-sm font-bold text-indigo-700">المصروف</div>
            <div class="mt-2 text-2xl font-black text-indigo-700">
              {{ Number(props.invoice.paid_amount || 0).toFixed(2) }}
            </div>
          </div>

          <div class="rounded-[24px] bg-rose-50 p-5 ring-1 ring-rose-200">
            <div class="text-sm font-bold text-rose-700">المتبقي للمورد</div>
            <div class="mt-2 text-2xl font-black text-rose-700">
              {{ remainingAmount().toFixed(2) }}
            </div>
          </div>

          <div class="rounded-[24px] bg-emerald-50 p-5 ring-1 ring-emerald-200">
            <div class="text-sm font-bold text-emerald-700">حالة الصرف</div>
            <div class="mt-2 text-xl font-black text-emerald-700">
              {{ paymentStatusLabel(props.invoice.payment_status) }}
            </div>
          </div>
        </section>

        <CardBox class="no-print">
          <CardBoxComponentHeader title="إيصالات الصرف المرتبطة بالفاتورة" />

          <div v-if="props.paymentVouchers?.length" class="overflow-x-auto">
            <table class="min-w-full text-right">
              <thead class="bg-slate-50">
                <tr class="text-sm text-slate-600">
                  <th class="px-4 py-4 font-black">رقم الإيصال</th>
                  <th class="px-4 py-4 font-black">التاريخ</th>
                  <th class="px-4 py-4 font-black">الخزينة / البنك</th>
                  <th class="px-4 py-4 font-black">طريقة الصرف</th>
                  <th class="px-4 py-4 font-black">المبلغ</th>
                  <th class="px-4 py-4 font-black">الحالة</th>
                  <th class="px-4 py-4 font-black">الإجراءات</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="voucher in props.paymentVouchers"
                  :key="voucher.id"
                  class="border-t text-sm text-slate-700"
                >
                  <td class="px-4 py-4 font-black">
                    {{ voucher.voucher_number }}
                  </td>

                  <td class="px-4 py-4">
                    {{
                      voucher.voucher_date
                        ? new Date(voucher.voucher_date).toLocaleDateString('en-GB')
                        : '-'
                    }}
                  </td>

                  <td class="px-4 py-4">
                    {{ voucher.financial_account?.name || '-' }}
                  </td>

                  <td class="px-4 py-4">
                    {{ voucher.payment_method?.name || '-' }}
                  </td>

                  <td class="px-4 py-4 font-black text-emerald-700">
                    {{ Number(voucher.amount || 0).toFixed(2) }}
                  </td>

                  <td class="px-4 py-4">
                    <span
                      class="rounded-xl px-3 py-1 text-xs font-black"
                      :class="{
                        'bg-emerald-100 text-emerald-700': voucher.status === 'posted',
                        'bg-amber-100 text-amber-700': voucher.status === 'draft',
                        'bg-rose-100 text-rose-700': voucher.status === 'cancelled',
                      }"
                    >
                      {{
                        voucher.status === 'posted'
                          ? 'مرحل'
                          : voucher.status === 'cancelled'
                            ? 'ملغي'
                            : 'مسودة'
                      }}
                    </span>
                  </td>

                  <td class="px-4 py-4">
                    <Link :href="`/payment-vouchers/${voucher.id}`">
                      <BaseButton label="فتح الإيصال" color="info" small />
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-else
            class="flex min-h-[180px] items-center justify-center text-sm font-bold text-slate-500"
          >
            لا توجد إيصالات صرف مرتبطة بهذه الفاتورة.
          </div>
        </CardBox>

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
                  v-for="line in props.invoice.journal_entry?.lines || []"
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

                <tr v-if="!props.invoice.journal_entry?.lines?.length">
                  <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">
                    لا يوجد قيد محاسبي مرتبط بهذه الفاتورة.
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
.official-invoice {
  direction: rtl;
  width: 100%;
  max-width: 980px;
  margin: 0 auto;
  border: 2px solid #0f172a;
  padding: 18px;
  border-radius: 10px;
  font-family: Arial, sans-serif;
}

.invoice-top {
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

.invoice-title-block {
  text-align: center;
}

.invoice-title {
  display: inline-block;
  border: 2px solid #0f172a;
  padding: 8px 28px;
  font-size: 24px;
  font-weight: 900;
}

.invoice-title-en {
  margin-top: 4px;
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
  letter-spacing: 1px;
}

.invoice-number-block {
  text-align: left;
  position: relative;
}

.small-label,
.invoice-cell span,
.words-box span,
.summary-totals span {
  display: block;
  font-size: 11px;
  font-weight: 900;
  color: #64748b;
  margin-bottom: 4px;
}

.invoice-number {
  font-size: 18px;
  font-weight: 900;
  color: #0f172a;
}

.invoice-row {
  display: grid;
  gap: 8px;
  margin-top: 8px;
}

.invoice-row.four {
  grid-template-columns: repeat(4, 1fr);
}

.invoice-row.three {
  grid-template-columns: repeat(3, 1fr);
}

.invoice-cell,
.words-box {
  border: 1px solid #334155;
  padding: 9px 10px;
  min-height: 54px;
}

.invoice-cell strong,
.words-box strong,
.summary-totals strong {
  display: block;
  font-size: 14px;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.5;
}

.items-box {
  margin-top: 10px;
  border: 1px solid #0f172a;
}

.invoice-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.invoice-table th {
  background: #f1f5f9;
  color: #0f172a;
  font-weight: 900;
}

.invoice-table th,
.invoice-table td {
  border: 1px solid #cbd5e1;
  padding: 8px;
}

.item-note {
  margin-top: 2px;
  font-size: 10px;
  color: #64748b;
  font-weight: 700;
}

.invoice-summary {
  display: grid;
  grid-template-columns: 1.6fr 0.9fr;
  gap: 10px;
  margin-top: 10px;
}

.summary-notes {
  display: grid;
  gap: 8px;
}

.description-cell {
  min-height: 66px;
}

.words-box {
  background: #f8fafc;
  border: 2px solid #0f172a;
}

.summary-totals {
  border: 2px solid #0f172a;
}

.summary-totals > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  border-bottom: 1px solid #cbd5e1;
  padding: 8px 10px;
}

.summary-totals > div:last-child {
  border-bottom: 0;
}

.summary-totals .total-row {
  background: #f1f5f9;
}

.summary-totals .total-row strong {
  font-size: 18px;
}

.signature-strip {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-top: 38px;
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

  .official-invoice {
    max-width: 100% !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    margin: 0 !important;
    padding: 10px !important;
    border: 2px solid #000 !important;
  }

  .company-name {
    font-size: 20px !important;
  }

  .invoice-title {
    font-size: 22px !important;
    padding: 6px 24px !important;
  }

  .invoice-cell,
  .words-box {
    padding: 7px 8px !important;
    min-height: 44px !important;
  }

  .invoice-table {
    font-size: 11px !important;
  }

  .invoice-table th,
  .invoice-table td {
    padding: 6px !important;
  }

  .description-cell {
    min-height: 52px !important;
  }

  .signature-strip {
    margin-top: 32px !important;
  }

  @page {
    size: A4 portrait;
    margin: 10mm;
  }
}
</style>