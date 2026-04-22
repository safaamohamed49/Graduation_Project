<script setup>
import { ref, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  suppliers: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const supplierToDelete = ref(null)

watch(search, (value) => {
  router.get(
    '/suppliers',
    { search: value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
})

const suppliersData = computed(() => props.suppliers?.data ?? [])

const openDeleteModal = (supplier) => {
  supplierToDelete.value = supplier
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  supplierToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeleteSupplier = () => {
  if (!supplierToDelete.value) return

  router.delete(`/suppliers/${supplierToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      closeDeleteModal()
    },
  })
}
</script>

<template>
  <MainLayout title="الموردين">
    <div class="space-y-6">
      <section class="rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 class="text-2xl font-black text-slate-800">إدارة الموردين</h1>
            <p class="mt-1 text-sm text-slate-500">عرض وإضافة وتعديل الموردين</p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <div class="min-w-[280px]">
              <FormControl
                v-model="search"
                type="text"
                placeholder="ابحثي بالاسم أو الرمز أو الهاتف"
              />
            </div>

            <Link href="/suppliers/create">
              <BaseButton label="إضافة مورد" color="primary" />
            </Link>
          </div>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="قائمة الموردين" />
        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-3 font-bold">#</th>
                <th class="px-4 py-3 font-bold">اسم المورد</th>
                <th class="px-4 py-3 font-bold">الرمز</th>
                <th class="px-4 py-3 font-bold">الهاتف</th>
                <th class="px-4 py-3 font-bold">البريد</th>
                <th class="px-4 py-3 font-bold">الحالة</th>
                <th class="px-4 py-3 font-bold">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(supplier, index) in suppliersData"
                :key="supplier.id"
                class="border-t text-sm text-slate-700"
              >
                <td class="px-4 py-3">
                  {{ ((props.suppliers.current_page - 1) * props.suppliers.per_page) + index + 1 }}
                </td>
                <td class="px-4 py-3 font-semibold">{{ supplier.name }}</td>
                <td class="px-4 py-3">{{ supplier.code }}</td>
                <td class="px-4 py-3">{{ supplier.phone || '-' }}</td>
                <td class="px-4 py-3">{{ supplier.email || '-' }}</td>
                <td class="px-4 py-3">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="supplier.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                  >
                    {{ supplier.is_active ? 'فعال' : 'غير فعال' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/suppliers/${supplier.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(supplier)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!suppliersData.length">
                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                  لا توجد موردين مطابقين.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.suppliers.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.suppliers.links"
          :key="link.label"
          class="rounded-xl border px-4 py-2 text-sm transition"
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

    <div
      v-if="deleteModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
      <div class="w-full max-w-md rounded-[28px] bg-white p-6 shadow-2xl">
        <div class="mb-4">
          <h2 class="text-xl font-black text-slate-800">تأكيد الحذف</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">
            هل أنت متأكدة من حذف المورد
            <span class="font-bold text-slate-800">"{{ supplierToDelete?.name }}"</span>
            ؟
          </p>
        </div>

        <div class="flex justify-end gap-3">
          <BaseButton label="إلغاء" color="light" @click="closeDeleteModal" />
          <BaseButton label="تأكيد الحذف" color="danger" @click="confirmDeleteSupplier" />
        </div>
      </div>
    </div>
  </MainLayout>
</template>