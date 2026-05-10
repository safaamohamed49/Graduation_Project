<script setup>
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  order: Object,
  amountInWords: String,
  receiptVouchers: Array,
})

const printPage = () => {
  window.print()
}

const paymentStatusLabel = (status) => {
  const labels = {
    paid: 'محصلة بالكامل',
    due: 'غير محصلة',
    partial: 'محصلة جزئيًا',
  }

  return labels[status] || status || '-'
}

const statusLabel = (status) => {
  const labels = {
    posted: 'مرحلة',
    draft: 'مسودة',
    cancelled: 'ملغية',
  }

  return labels[status] || status || '-'
}

const remainingAmount = () => {
  return Math.max(
    0,
    Number(props.order.total_price || 0) - Number(props.order.paid_amount || 0)
  )
}
</script>

<template>
  <MainLayout :title="props.order.order_number">
    <div class="space-y-6">
      <div class="no-print">
        <PageHero
          badge="فاتورة بيع"
          :title="`فاتورة رقم ${props.order.order_number}`"
          description="عرض فاتورة البيع الرسمية، القيود المحاسبية، وإيصالات القبض المرتبطة بالفاتورة."
          gradient-class="bg-gradient-to-br from-indigo-800 via-purple-800 to-slate-900"
        />
      </div>

      <section class="no-print flex flex-wrap justify-end gap-3">
        <Link href="/orders">
          <BaseButton label="رجوع" color="light" />
        </Link>

        <BaseButton label="طباعة الفاتورة" color="info" @click="printPage" />

        <Link
          v-if="props.order.status !== 'cancelled'"
          :href="`/orders/${props.order.id}/edit`"
        >
          <BaseButton label="تعديل الفاتورة" color="warning" />
        </Link>
      </section>

      <div id="print-area" class="space-y-6">
        <section
          class="official-invoice bg-white text-slate-950 shadow-sm ring-1 ring-slate-300"
        >
          <div class="invoice-top">
            <div class="company-block">
              <div class="company-name">بنيس للحديد الصناعي</div>

              <div class="company-subtitle">
                الفرع المصدر للفاتورة:
                {{ props.order.branch?.name || '-' }}
              </div>

              <div class="company-subtitle">
                المخزن:
                {{ props.order.warehouse?.name || '-' }}
              </div>
            </div>

            <div class="invoice-title-block">
              <div class="invoice-title">فاتورة بيع</div>

              <div class="invoice-title-en">
                SALES INVOICE
              </div>
            </div>

            <div class="invoice-number-block">
              <div class="small-label">رقم الفاتورة</div>

              <div class="invoice-number">
                {{ props.order.order_number }}
              </div>

              <div
                v-if="props.order.status === 'cancelled'"
                class="cancel-stamp"
              >
                ملغية
              </div>
            </div>
          </div>

          <div class="invoice-row four">
            <div class="invoice-cell">
              <span>التاريخ</span>

              <strong>
                {{
                  props.order.order_date
                    ? String(props.order.order_date).slice(0, 10)
                    : '-'
                }}
              </strong>
            </div>

            <div class="invoice-cell">
              <span>العميل</span>

              <strong>
                {{ props.order.customer?.name || 'عميل نقدي' }}
              </strong>
            </div>

            <div class="invoice-cell">
              <span>هاتف العميل</span>

              <strong>
                {{ props.order.customer?.phone || '-' }}
              </strong>
            </div>

            <div class="invoice-cell">
              <span>حالة التحصيل</span>

              <strong>
                {{ paymentStatusLabel(props.order.payment_status) }}
              </strong>
            </div>
          </div>

          <div class="invoice-row three">
            <div class="invoice-cell">
              <span>إجمالي المحصل</span>

              <strong>
                {{ Number(props.order.paid_amount || 0).toFixed(2) }}
              </strong>
            </div>

            <div class="invoice-cell">
              <span>المتبقي للتحصيل</span>

              <strong>
                {{ remainingAmount().toFixed(2) }}
              </strong>
            </div>

            <div class="invoice-cell">
              <span>حالة الفاتورة</span>

              <strong>
                {{ statusLabel(props.order.status) }}
              </strong>
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
                  <th>سعر البيع</th>
                  <th>الإجمالي</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(item, index) in props.order.items"
                  :key="item.id"
                >
                  <td>{{ index + 1 }}</td>

                  <td>
                    <strong>{{ item.product_name }}</strong>

                    <div
                      v-if="item.notes"
                      class="item-note"
                    >
                      {{ item.notes }}
                    </div>
                  </td>

                  <td>
                    {{ item.product?.unit_name || '-' }}
                  </td>

                  <td>
                    {{ Number(item.quantity || 0).toFixed(2) }}
                  </td>

                  <td>
                    {{ Number(item.unit_price || 0).toFixed(2) }}
                  </td>

                  <td>
                    {{
                      (
                        Number(item.quantity || 0) *
                        Number(item.unit_price || 0)
                      ).toFixed(2)
                    }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="invoice-summary">
            <div class="summary-notes">
              <div class="invoice-cell description-cell">
                <span>البيان / الملاحظات</span>

                <strong class="whitespace-pre-line">
                  {{ props.order.notes || '-' }}
                </strong>
              </div>

              <div class="words-box">
                <span>المبلغ كتابةً</span>

                <strong>
                  {{ props.amountInWords || '-' }}
                </strong>
              </div>
            </div>

            <div class="summary-totals">
              <div>
                <span>الإجمالي قبل الخصم</span>

                <strong>
                  {{ Number(props.order.subtotal || 0).toFixed(2) }}
                </strong>
              </div>

              <div>
                <span>الخصم</span>

                <strong>
                  {{ Number(props.order.discount_amount || 0).toFixed(2) }}
                </strong>
              </div>

              <div class="total-row">
                <span>الصافي</span>

                <strong>
                  {{ Number(props.order.total_price || 0).toFixed(2) }} د.ل
                </strong>
              </div>

              <div>
                <span>المحصل</span>

                <strong>
                  {{ Number(props.order.paid_amount || 0).toFixed(2) }}
                </strong>
              </div>

              <div>
                <span>المتبقي للتحصيل</span>

                <strong>
                  {{ remainingAmount().toFixed(2) }}
                </strong>
              </div>
            </div>
          </div>

          <div class="signature-strip">
            <div>توقيع العميل</div>
            <div>توقيع البائع</div>
            <div>اعتماد الإدارة</div>
          </div>

          <div class="footer-note">
            هذه الفاتورة صادرة من فرع:
            {{ props.order.branch?.name || '-' }}
            — بنيس للحديد الصناعي.
          </div>
        </section>

        <section class="no-print grid gap-4 md:grid-cols-4">
          <div
            class="rounded-[24px] bg-slate-50 p-5 ring-1 ring-slate-200"
          >
            <div class="text-sm font-bold text-slate-500">
              إجمالي التكلفة
            </div>

            <div class="mt-2 text-2xl font-black text-amber-700">
              {{ Number(props.order.total_cost || 0).toFixed(2) }}
            </div>
          </div>

          <div
            class="rounded-[24px] bg-emerald-50 p-5 ring-1 ring-emerald-200"
          >
            <div class="text-sm font-bold text-emerald-700">
              إجمالي الربح
            </div>

            <div class="mt-2 text-2xl font-black text-emerald-700">
              {{ Number(props.order.total_profit || 0).toFixed(2) }}
            </div>
          </div>

          <div
            class="rounded-[24px] bg-indigo-50 p-5 ring-1 ring-indigo-200"
          >
            <div class="text-sm font-bold text-indigo-700">
              المحصل
            </div>

            <div class="mt-2 text-2xl font-black text-indigo-700">
              {{ Number(props.order.paid_amount || 0).toFixed(2) }}
            </div>
          </div>

          <div
            class="rounded-[24px] bg-rose-50 p-5 ring-1 ring-rose-200"
          >
            <div class="text-sm font-bold text-rose-700">
              المتبقي للتحصيل
            </div>

            <div class="mt-2 text-2xl font-black text-rose-700">
              {{ remainingAmount().toFixed(2) }}
            </div>
          </div>
        </section>

        <CardBox class="no-print">
          <CardBoxComponentHeader
            title="إيصالات القبض المرتبطة بالفاتورة"
          />

          <div
            v-if="props.receiptVouchers?.length"
            class="overflow-x-auto"
          >
            <table class="min-w-full text-right">
              <thead class="bg-slate-50">
                <tr class="text-sm text-slate-600">
                  <th class="px-4 py-4 font-black">
                    رقم الإيصال
                  </th>

                  <th class="px-4 py-4 font-black">
                    التاريخ
                  </th>

                  <th class="px-4 py-4 font-black">
                    الخزينة / البنك
                  </th>

                  <th class="px-4 py-4 font-black">
                    طريقة القبض
                  </th>

                  <th class="px-4 py-4 font-black">
                    المبلغ
                  </th>

                  <th class="px-4 py-4 font-black">
                    الحالة
                  </th>

                  <th class="px-4 py-4 font-black">
                    الإجراءات
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="voucher in props.receiptVouchers"
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
                        'bg-emerald-100 text-emerald-700':
                          voucher.status === 'posted',
                        'bg-amber-100 text-amber-700':
                          voucher.status === 'draft',
                        'bg-rose-100 text-rose-700':
                          voucher.status === 'cancelled',
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
                    <Link :href="`/receipt-vouchers/${voucher.id}`">
                      <BaseButton
                        label="فتح الإيصال"
                        color="info"
                        small
                      />
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
            لا توجد إيصالات قبض مرتبطة بهذه الفاتورة.
          </div>
        </CardBox>
      </div>
    </div>
  </MainLayout>
</template>