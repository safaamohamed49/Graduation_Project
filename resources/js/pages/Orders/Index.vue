<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'
import EntityToolbar from '@/Components/App/EntityToolbar.vue'
import DeleteConfirmModal from '@/Components/App/DeleteConfirmModal.vue'

const props = defineProps({
  orders: Object,
  filters: Object,
  permissions: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const orderToDelete = ref(null)

const ordersData = computed(() => props.orders?.data ?? [])
const canCreate = computed(() => props.permissions?.canCreate ?? false)
const canUpdate = computed(() => props.permissions?.canUpdate ?? false)
const canDelete = computed(() => props.permissions?.canDelete ?? false)

const totalSales = computed(() =>
  ordersData.value.reduce((sum, item) => sum + Number(item.total_price || 0), 0)
)

const totalProfit = computed(() =>
  ordersData.value.reduce((sum, item) => sum + Number(item.total_profit || 0), 0)
)

const totalPaid = computed(() =>
  ordersData.value.reduce((sum, item) => sum + Number(item.paid_amount || 0), 0)
)

const totalRemaining = computed(() =>
  ordersData.value.reduce((sum, item) => {
    return sum + Math.max(0, Number(item.total_price || 0) - Number(item.paid_amount || 0))
  }, 0)
)

const paymentStatusLabel = (status) => {
  const labels = {
    paid: 'مدفوعة',
    due: 'دين',
    partial: 'جزئي',
  }

  return labels[status] || status || '-'
}

const paymentStatusClass = (status) => {
  if (status === 'paid') return 'bg-emerald-100 text-emerald-700'
  if (status === 'partial') return 'bg-amber-100 text-amber-700'
  return 'bg-rose-100 text-rose-700'
}

const submitSearch = () => {
  router.get('/orders', { search: search.value }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const openDeleteModal = (order) => {
  orderToDelete.value = order
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  orderToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeleteOrder = () => {
  if (!orderToDelete.value) return

  router.delete(`/orders/${orderToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => closeDeleteModal(),
  })
}
</script>

<template>
  <MainLayout title="فواتير البيع">
    <div class="space-y-6">
      <PageHero
        badge="المبيعات"
        title="فواتير البيع"
        description="إدارة فواتير البيع مع خصم المخزون بنظام FIFO واحتساب التكلفة والربح وتوليد القيود المحاسبية."
        gradient-class="bg-gradient-to-br from-indigo-800 via-purple-800 to-slate-900"
      />

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الفواتير</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ props.orders?.total ?? 0 }}</div>
          <div class="mt-2 text-xs text-slate-400">إجمالي فواتير البيع</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">مبيعات الصفحة</div>
          <div class="mt-3 text-3xl font-black text-indigo-700">{{ totalSales.toFixed(2) }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب النتائج الظاهرة</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">ربح الصفحة</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">{{ totalProfit.toFixed(2) }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب FIFO المحفوظ</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المتبقي دين</div>
          <div class="mt-3 text-3xl font-black text-rose-700">{{ totalRemaining.toFixed(2) }}</div>
          <div class="mt-2 text-xs text-slate-400">مديونية نتائج الصفحة</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي برقم الفاتورة أو العميل أو الهاتف أو حالة الدفع"
        :create-href="canCreate ? '/orders/create' : null"
        create-label="إضافة فاتورة بيع"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة فواتير البيع" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">رقم الفاتورة</th>
                <th class="px-4 py-4 font-black">التاريخ</th>
                <th class="px-4 py-4 font-black">العميل</th>
                <th class="px-4 py-4 font-black">المخزن</th>
                <th class="px-4 py-4 font-black">الإجمالي</th>
                <th class="px-4 py-4 font-black">المدفوع</th>
                <th class="px-4 py-4 font-black">المتبقي</th>
                <th class="px-4 py-4 font-black">الربح</th>
                <th class="px-4 py-4 font-black">التحصيل</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(order, index) in ordersData"
                :key="order.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.orders.current_page - 1) * props.orders.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4 font-black text-indigo-700">
                  {{ order.order_number }}
                </td>

                <td class="px-4 py-4">
                  {{ order.order_date ? String(order.order_date).slice(0, 10) : '-' }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-bold">{{ order.customer?.name || 'عميل نقدي' }}</span>
                    <span class="text-xs text-slate-400">{{ order.customer?.phone || order.customer?.code || '' }}</span>
                  </div>
                </td>

                <td class="px-4 py-4">
                  {{ order.warehouse?.name || '-' }}
                </td>

                <td class="px-4 py-4 font-black text-slate-800">
                  {{ Number(order.total_price || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-bold text-emerald-700">
                  {{ Number(order.paid_amount || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-bold text-rose-700">
                  {{ Math.max(0, Number(order.total_price || 0) - Number(order.paid_amount || 0)).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-emerald-700">
                  {{ Number(order.total_profit || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="paymentStatusClass(order.payment_status)"
                  >
                    {{ paymentStatusLabel(order.payment_status) }}
                  </span>

                  <div v-if="order.financial_account" class="mt-1 text-xs font-bold text-slate-400">
                    {{ order.financial_account.name }}
                  </div>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/orders/${order.id}`">
                      <BaseButton label="عرض" color="info" small />
                    </Link>

                    <Link v-if="canUpdate && order.status !== 'cancelled'" :href="`/orders/${order.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      v-if="canDelete && order.status !== 'cancelled'"
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(order)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!ordersData.length">
                <td colspan="11" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد فواتير بيع مطابقة.
                </td>
              </tr>
            </tbody>

            <tfoot v-if="ordersData.length" class="bg-slate-100 text-sm font-black">
              <tr>
                <td colspan="5" class="px-4 py-4">إجمالي الصفحة</td>
                <td class="px-4 py-4 text-slate-800">{{ totalSales.toFixed(2) }}</td>
                <td class="px-4 py-4 text-emerald-700">{{ totalPaid.toFixed(2) }}</td>
                <td class="px-4 py-4 text-rose-700">{{ totalRemaining.toFixed(2) }}</td>
                <td class="px-4 py-4 text-emerald-700">{{ totalProfit.toFixed(2) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </CardBox>

      <section v-if="props.orders.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.orders.links"
          :key="link.label"
          class="rounded-2xl border px-4 py-2 text-sm font-semibold transition"
          :class="[
            link.active
              ? 'border-indigo-600 bg-indigo-600 text-white'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
            !link.url ? 'cursor-not-allowed opacity-50' : ''
          ]"
          :disabled="!link.url"
          @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })"
          v-html="link.label"
        />
      </section>
    </div>

    <DeleteConfirmModal
      :open="deleteModalOpen && canDelete"
      title="تأكيد حذف فاتورة البيع"
      :item-name="orderToDelete?.order_number || ''"
      message="سيتم حذف الفاتورة منطقيًا، إرجاع الكميات للمخزون، وإنشاء قيد عكسي للقيد المحاسبي."
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeleteOrder"
    />
  </MainLayout>
</template>