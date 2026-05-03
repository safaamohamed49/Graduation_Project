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
  customers: Object,
  filters: Object,
  permissions: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const customerToDelete = ref(null)

const customersData = computed(() => props.customers?.data ?? [])
const canCreate = computed(() => props.permissions?.canCreate ?? false)
const canUpdate = computed(() => props.permissions?.canUpdate ?? false)
const canDelete = computed(() => props.permissions?.canDelete ?? false)

const totalCustomers = computed(() => props.customers?.total ?? 0)
const activeCustomersCount = computed(() => customersData.value.filter((item) => item.is_active).length)
const inactiveCustomersCount = computed(() => customersData.value.filter((item) => !item.is_active).length)

const submitSearch = () => {
  router.get('/customers', { search: search.value }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const openDeleteModal = (customer) => {
  customerToDelete.value = customer
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  customerToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeleteCustomer = () => {
  if (!customerToDelete.value) return

  router.delete(`/customers/${customerToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => closeDeleteModal(),
  })
}
</script>

<template>
  <MainLayout title="العملاء">
    <div class="space-y-6">
      <PageHero
        badge="إدارة العملاء"
        title="متابعة العملاء داخل النظام"
        description="إدارة بيانات العملاء والبحث السريع والتعديل والحذف المنطقي حسب صلاحيات المستخدم."
        gradient-class="bg-gradient-to-l from-slate-900 via-slate-800 to-slate-700"
      />

      <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي العملاء</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ totalCustomers }}</div>
          <div class="mt-2 text-xs text-slate-400">إجمالي السجلات الموجودة في قاعدة البيانات</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">العملاء النشطون</div>
          <div class="mt-3 text-3xl font-black text-emerald-600">{{ activeCustomersCount }}</div>
          <div class="mt-2 text-xs text-slate-400">العملاء الفعالون في الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">العملاء غير النشطين</div>
          <div class="mt-3 text-3xl font-black text-amber-600">{{ inactiveCustomersCount }}</div>
          <div class="mt-2 text-xs text-slate-400">حسابات تحتاج مراجعة أو إعادة تفعيل</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي بالاسم أو الرمز أو الهاتف أو البريد"
        :create-href="canCreate ? '/customers/create' : null"
        create-label="إضافة عميل جديد"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة العملاء" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">اسم العميل</th>
                <th class="px-4 py-4 font-black">الرمز</th>
                <th class="px-4 py-4 font-black">الهاتف</th>
                <th class="px-4 py-4 font-black">البريد</th>
                <th class="px-4 py-4 font-black">العنوان</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th v-if="canUpdate || canDelete" class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(customer, index) in customersData"
                :key="customer.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.customers.current_page - 1) * props.customers.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ customer.name }}</span>
                    <span v-if="customer.notes" class="mt-1 line-clamp-1 text-xs text-slate-400">
                      {{ customer.notes }}
                    </span>
                  </div>
                </td>

                <td class="px-4 py-4">
                  <span class="rounded-xl bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                    {{ customer.code }}
                  </span>
                </td>

                <td class="px-4 py-4">{{ customer.phone || '-' }}</td>
                <td class="px-4 py-4">{{ customer.email || '-' }}</td>
                <td class="px-4 py-4">{{ customer.address || '-' }}</td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="customer.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ customer.is_active ? 'فعال' : 'غير فعال' }}
                  </span>
                </td>

                <td v-if="canUpdate || canDelete" class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link v-if="canUpdate" :href="`/customers/${customer.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      v-if="canDelete"
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(customer)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!customersData.length">
                <td :colspan="canUpdate || canDelete ? 8 : 7" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد بيانات مطابقة لنتيجة البحث الحالية.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.customers.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.customers.links"
          :key="link.label"
          class="rounded-2xl border px-4 py-2 text-sm font-semibold transition"
          :class="[
            link.active
              ? 'border-blue-600 bg-blue-600 text-white'
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
      title="تأكيد حذف العميل"
      :item-name="customerToDelete?.name || ''"
      message="سيتم تنفيذ حذف منطقي وإخفاء العميل من القائمة النشطة"
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeleteCustomer"
    />
  </MainLayout>
</template>