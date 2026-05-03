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
  invoices: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const invoiceToDelete = ref(null)

const invoicesData = computed(() => props.invoices?.data ?? [])

const submitSearch = () => {
  router.get(
    '/purchase-invoices',
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

const openDeleteModal = (invoice) => {
  invoiceToDelete.value = invoice
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  invoiceToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeleteInvoice = () => {
  if (!invoiceToDelete.value) return

  router.delete(`/purchase-invoices/${invoiceToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => closeDeleteModal(),
  })
}
</script>

<template>
  <MainLayout title="فواتير الشراء">
    <div class="space-y-6">
      <PageHero
        badge="المشتريات"
        title="فواتير الشراء"
        description="عرض ومراجعة فواتير الشراء المسجلة، مع إمكانية البحث والتعديل والحذف المنطقي قبل استخدام كمياتها في فواتير البيع."
        gradient-class="bg-gradient-to-br from-emerald-800 via-teal-800 to-slate-950"
      />

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الفواتير</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ props.invoices?.total ?? 0 }}</div>
          <div class="mt-2 text-xs text-slate-400">إجمالي فواتير الشراء المسجلة</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الصفحة</div>
          <div class="mt-3 text-3xl font-black text-emerald-600">
            {{
              invoicesData
                .reduce((sum, invoice) => sum + Number(invoice.total_price || 0), 0)
                .toFixed(2)
            }}
          </div>
          <div class="mt-2 text-xs text-slate-400">مجموع الفواتير الظاهرة حاليًا</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">آخر فاتورة</div>
          <div class="mt-3 text-xl font-black text-cyan-700">
            {{ invoicesData[0]?.invoice_number ?? '-' }}
          </div>
          <div class="mt-2 text-xs text-slate-400">آخر فاتورة شراء حسب الإدخال</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي برقم الفاتورة أو اسم المورد"
        create-href="/purchase-invoices/create"
        create-label="إضافة فاتورة شراء"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة فواتير الشراء" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">رقم الفاتورة</th>
                <th class="px-4 py-4 font-black">التاريخ</th>
                <th class="px-4 py-4 font-black">المورد</th>
                <th class="px-4 py-4 font-black">الفرع</th>
                <th class="px-4 py-4 font-black">المخزن</th>
                <th class="px-4 py-4 font-black">الإجمالي</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(invoice, index) in invoicesData"
                :key="invoice.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.invoices.current_page - 1) * props.invoices.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <span class="rounded-xl bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">
                    {{ invoice.invoice_number }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  {{ invoice.invoice_date ? String(invoice.invoice_date).slice(0, 10) : '-' }}
                </td>

                <td class="px-4 py-4 font-bold text-slate-800">
                  {{ invoice.supplier?.name ?? '-' }}
                </td>

                <td class="px-4 py-4">{{ invoice.branch?.name ?? '-' }}</td>
                <td class="px-4 py-4">{{ invoice.warehouse?.name ?? '-' }}</td>

                <td class="px-4 py-4 font-black text-emerald-700">
                  {{ Number(invoice.total_price || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/purchase-invoices/${invoice.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(invoice)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!invoicesData.length">
                <td colspan="8" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد فواتير شراء مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.invoices.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.invoices.links"
          :key="link.label"
          class="rounded-2xl border px-4 py-2 text-sm font-semibold transition"
          :class="[
            link.active
              ? 'border-emerald-600 bg-emerald-600 text-white'
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
      :open="deleteModalOpen"
      title="تأكيد حذف فاتورة الشراء"
      :item-name="invoiceToDelete?.invoice_number || ''"
      message="سيتم حذف الفاتورة منطقيًا وحذف دفعات المخزون الخاصة بها إذا لم تكن مستخدمة في البيع"
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeleteInvoice"
    />
  </MainLayout>
</template>