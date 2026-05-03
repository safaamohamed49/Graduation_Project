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
  employees: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const deleteModalOpen = ref(false)
const employeeToDelete = ref(null)

const employeesData = computed(() => props.employees?.data ?? [])

const activeCount = computed(() => employeesData.value.filter((item) => item.is_active).length)

const usersCount = computed(() => employeesData.value.filter((item) => item.user).length)

const totalSalary = computed(() => {
  return employeesData.value.reduce((sum, item) => sum + Number(item.salary || 0), 0)
})

const submitSearch = () => {
  router.get(
    '/employees',
    { search: search.value },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

const openDeleteModal = (employee) => {
  employeeToDelete.value = employee
  deleteModalOpen.value = true
}

const closeDeleteModal = () => {
  employeeToDelete.value = null
  deleteModalOpen.value = false
}

const confirmDeleteEmployee = () => {
  if (!employeeToDelete.value) return

  router.delete(`/employees/${employeeToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => closeDeleteModal(),
  })
}
</script>

<template>
  <MainLayout title="الموظفين">
    <div class="space-y-6">
      <PageHero
        badge="إدارة الموظفين"
        title="الموظفين والمستخدمين"
        description="إدارة بيانات الموظفين ورواتبهم وربطهم بحسابات دخول للنظام حسب الفرع والدور والصلاحيات."
        gradient-class="bg-gradient-to-br from-indigo-900 via-slate-900 to-cyan-900"
      />

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الموظفين</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ props.employees?.total ?? 0 }}</div>
          <div class="mt-2 text-xs text-slate-400">إجمالي الموظفين المسجلين</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الموظفين النشطين</div>
          <div class="mt-3 text-3xl font-black text-emerald-600">{{ activeCount }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">حسابات الدخول</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">{{ usersCount }}</div>
          <div class="mt-2 text-xs text-slate-400">موظفين لديهم مستخدم للنظام</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الرواتب</div>
          <div class="mt-3 text-3xl font-black text-indigo-700">{{ totalSalary.toFixed(2) }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الموظفين الظاهرين حاليًا</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي باسم الموظف أو الهاتف أو البريد أو الفرع أو اسم المستخدم"
        create-href="/employees/create"
        create-label="إضافة موظف"
        @search="submitSearch"
      />

      <CardBox>
        <CardBoxComponentHeader title="قائمة الموظفين" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">الموظف</th>
                <th class="px-4 py-4 font-black">الفرع</th>
                <th class="px-4 py-4 font-black">الهاتف</th>
                <th class="px-4 py-4 font-black">الراتب</th>
                <th class="px-4 py-4 font-black">تاريخ التوظيف</th>
                <th class="px-4 py-4 font-black">حساب الدخول</th>
                <th class="px-4 py-4 font-black">الدور</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(employee, index) in employeesData"
                :key="employee.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.employees.current_page - 1) * props.employees.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ employee.name }}</span>
                    <span v-if="employee.email" class="mt-1 text-xs text-slate-400">{{ employee.email }}</span>
                  </div>
                </td>

                <td class="px-4 py-4">{{ employee.branch?.name || '-' }}</td>
                <td class="px-4 py-4">{{ employee.phone || '-' }}</td>

                <td class="px-4 py-4 font-black text-indigo-700">
                  {{ Number(employee.salary || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  {{ employee.hire_date ? String(employee.hire_date).slice(0, 10) : '-' }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="employee.user ? 'bg-cyan-100 text-cyan-700' : 'bg-slate-100 text-slate-500'"
                  >
                    {{ employee.user ? employee.user.username : 'لا يوجد' }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  {{ employee.user?.role?.name || '-' }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="employee.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ employee.is_active ? 'فعال' : 'موقوف' }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/employees/${employee.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>

                    <BaseButton
                      label="حذف"
                      color="danger"
                      small
                      @click="openDeleteModal(employee)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="!employeesData.length">
                <td colspan="10" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد بيانات موظفين مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.employees.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.employees.links"
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
      :open="deleteModalOpen"
      title="تأكيد حذف الموظف"
      :item-name="employeeToDelete?.name || ''"
      message="سيتم حذف الموظف، وإذا كان لديه حساب دخول سيتم تعطيله وحذفه منطقيًا من النظام."
      confirm-label="تأكيد الحذف"
      @close="closeDeleteModal"
      @confirm="confirmDeleteEmployee"
    />
  </MainLayout>
</template>