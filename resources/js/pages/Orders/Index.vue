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
        description="إدارة فواتير البيع مع خصم المخزون بنظام FIFO واحتساب التكلفة والربح تلقائيًا."
        gradient-class="bg-gradient-to-br from-indigo-800 via-purple-800 to-slate-900"
      />

      <section class="grid gap-4 md:grid-cols-3">
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
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي برقم الفاتورة أو العميل أو الهاتف"
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
                <th class="px-4 py-4 font-black">التكلفة</th>
                <th class="px-4 py-4 font-black">الربح</th>
                <th class="px-4 py-4 font-black">الدفع</th>
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

                <td class="px-4 py-4 font-black text-indigo-700">{{ order.order_number }}</td>
                <td class="px-4 py-4">{{ String(order.order_date).slice(0, 10) }}</td>
                <td class="px-4 py-4">{{ order.customer?.name || 'عميل نقدي' }}</td>
                <td class="px-4 py-4">{{ order.warehouse?.name || '-' }}</td>

                <td class="px-4 py-4 font-black text-slate-800">
                  {{ Number(order.total_price || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-bold text-amber-700">
                  {{ Number(order.total_cost || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-emerald-700">
                  {{ Number(order.total_profit || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                    {{ order.payment_status }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/orders/${order.id}`">
                      <BaseButton label="عرض" color="info" small />
                    </Link>

                    <Link v-if="canUpdate" :href="`/orders/${order.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      v-if="canDelete"
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(order)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!ordersData.length">
                <td colspan="10" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد فواتير بيع مطابقة.
                </td>
              </tr>
            </tbody>
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
      message="سيتم حذف الفاتورة منطقيًا وإرجاع الكميات المسحوبة إلى دفعات المخزون."
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeleteOrder"
    />
  </MainLayout>
</template>