<script setup>
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  order: Object,
})
</script>

<template>
  <MainLayout title="عرض فاتورة بيع">
    <div class="space-y-6">
      <PageHero
        badge="فاتورة بيع"
        :title="`فاتورة رقم ${props.order.order_number}`"
        description="عرض تفاصيل الفاتورة والتكلفة والربح والدفعات التي تم السحب منها بنظام FIFO."
        gradient-class="bg-gradient-to-br from-indigo-800 via-purple-800 to-slate-900"
      />

      <section class="flex justify-end gap-3">
        <Link href="/orders">
          <BaseButton label="رجوع" color="light" />
        </Link>

        <button
          type="button"
          class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-800"
          @click="window.print()"
        >
          طباعة
        </button>
      </section>

      <section class="grid gap-4 md:grid-cols-4 print:grid-cols-4">
        <div class="rounded-[24px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">العميل</div>
          <div class="mt-2 text-xl font-black text-slate-800">{{ props.order.customer?.name || 'عميل نقدي' }}</div>
        </div>

        <div class="rounded-[24px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المخزن</div>
          <div class="mt-2 text-xl font-black text-slate-800">{{ props.order.warehouse?.name || '-' }}</div>
        </div>

        <div class="rounded-[24px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">التاريخ</div>
          <div class="mt-2 text-xl font-black text-slate-800">{{ String(props.order.order_date).slice(0, 10) }}</div>
        </div>

        <div class="rounded-[24px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">حالة الدفع</div>
          <div class="mt-2 text-xl font-black text-slate-800">{{ props.order.payment_status }}</div>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="بنود الفاتورة" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">المنتج</th>
                <th class="px-4 py-4 font-black">الكمية</th>
                <th class="px-4 py-4 font-black">سعر البيع</th>
                <th class="px-4 py-4 font-black">الإيراد</th>
                <th class="px-4 py-4 font-black">متوسط التكلفة</th>
                <th class="px-4 py-4 font-black">الربح</th>
              </tr>
            </thead>

            <tbody>
              <template v-for="item in props.order.items" :key="item.id">
                <tr class="border-t text-sm text-slate-700">
                  <td class="px-4 py-4 font-black text-slate-800">{{ item.product_name }}</td>
                  <td class="px-4 py-4">{{ Number(item.quantity).toFixed(2) }}</td>
                  <td class="px-4 py-4">{{ Number(item.unit_price).toFixed(2) }}</td>
                  <td class="px-4 py-4 font-bold">{{ (Number(item.quantity) * Number(item.unit_price)).toFixed(2) }}</td>
                  <td class="px-4 py-4 text-amber-700">{{ Number(item.cost_price).toFixed(2) }}</td>
                  <td class="px-4 py-4 font-black text-emerald-700">{{ Number(item.profit).toFixed(2) }}</td>
                </tr>

                <tr class="bg-slate-50">
                  <td colspan="6" class="px-4 py-3">
                    <div class="text-xs font-black text-slate-500">دفعات FIFO المسحوبة:</div>

                    <div class="mt-2 flex flex-wrap gap-2">
                      <span
                        v-for="line in item.batches"
                        :key="line.id"
                        class="rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200"
                      >
                        دفعة #{{ line.batch_id }} —
                        كمية {{ Number(line.quantity_used).toFixed(2) }} —
                        تكلفة {{ Number(line.unit_cost).toFixed(2) }}
                      </span>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[24px] bg-slate-50 p-5 ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الإجمالي قبل الخصم</div>
          <div class="mt-2 text-2xl font-black text-slate-800">{{ Number(props.order.subtotal).toFixed(2) }}</div>
        </div>

        <div class="rounded-[24px] bg-rose-50 p-5 ring-1 ring-rose-200">
          <div class="text-sm font-bold text-rose-700">الخصم</div>
          <div class="mt-2 text-2xl font-black text-rose-700">{{ Number(props.order.discount_amount).toFixed(2) }}</div>
        </div>

        <div class="rounded-[24px] bg-indigo-50 p-5 ring-1 ring-indigo-200">
          <div class="text-sm font-bold text-indigo-700">الصافي</div>
          <div class="mt-2 text-2xl font-black text-indigo-700">{{ Number(props.order.total_price).toFixed(2) }}</div>
        </div>

        <div class="rounded-[24px] bg-emerald-50 p-5 ring-1 ring-emerald-200">
          <div class="text-sm font-bold text-emerald-700">الربح</div>
          <div class="mt-2 text-2xl font-black text-emerald-700">{{ Number(props.order.total_profit).toFixed(2) }}</div>
        </div>
      </section>
    </div>
  </MainLayout>
</template>