<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'
import EntityToolbar from '@/Components/App/EntityToolbar.vue'

const props = defineProps({
  financialAccounts: Object,
  filters: Object,
  branches: Array,
  permissions: Object,
  isAdmin: Boolean,
})

const search = ref(props.filters?.search ?? '')
const type = ref(props.filters?.type ?? '')
const branchId = ref(props.filters?.branch_id ?? '')
const status = ref(props.filters?.status ?? '')

const accountsData = computed(() => props.financialAccounts?.data ?? [])

const typeLabel = (value) => {
  if (value === 'cash') return 'خزينة'
  if (value === 'bank') return 'بنك'
  return '-'
}

const applyFilters = () => {
  router.get('/financial-accounts', {
    search: search.value,
    type: type.value,
    branch_id: branchId.value,
    status: status.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const resetFilters = () => {
  search.value = ''
  type.value = ''
  branchId.value = ''
  status.value = ''

  router.get('/financial-accounts', {}, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}
</script>

<template>
  <MainLayout title="الخزائن والبنوك">
    <div class="space-y-6">
      <PageHero
        badge="الخزينة / البنوك"
        title="الخزائن والبنوك"
        description="إدارة الخزائن والحسابات البنكية، ومتابعة الرصيد الحالي لكل حساب مالي حسب القبض والصرف."
        gradient-class="bg-gradient-to-br from-slate-900 via-emerald-900 to-cyan-900"
      />

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي باسم الخزينة أو الكود"
        :create-href="props.permissions?.canCreate ? '/financial-accounts/create' : null"
        create-label="إضافة خزينة / بنك"
        @search="applyFilters"
      />

      <CardBox>
        <CardBoxComponentHeader title="فلاتر الخزائن والبنوك" />

        <div class="grid gap-4 p-6 md:grid-cols-5">
          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">النوع</label>
            <select v-model="type" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none">
              <option value="">الكل</option>
              <option value="cash">خزينة</option>
              <option value="bank">بنك</option>
            </select>
          </div>

          <div v-if="props.isAdmin">
            <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
            <select v-model="branchId" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none">
              <option value="">كل الفروع</option>
              <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">الحالة</label>
            <select v-model="status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none">
              <option value="">الكل</option>
              <option value="1">فعال</option>
              <option value="0">موقوف</option>
            </select>
          </div>

          <div class="flex items-end gap-2 md:col-span-2">
            <BaseButton label="تطبيق" color="primary" @click="applyFilters" />
            <BaseButton label="مسح" color="light" @click="resetFilters" />
          </div>
        </div>
      </CardBox>

      <CardBox>
        <CardBoxComponentHeader title="قائمة الخزائن والبنوك" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">الاسم</th>
                <th class="px-4 py-4 font-black">الكود</th>
                <th class="px-4 py-4 font-black">الفرع</th>
                <th class="px-4 py-4 font-black">النوع</th>
                <th class="px-4 py-4 font-black">افتتاحي</th>
                <th class="px-4 py-4 font-black">قبض</th>
                <th class="px-4 py-4 font-black">صرف</th>
                <th class="px-4 py-4 font-black">الرصيد</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="account in accountsData" :key="account.id" class="border-t text-sm text-slate-700">
                <td class="px-4 py-4 font-black">{{ account.name }}</td>
                <td class="px-4 py-4">{{ account.code }}</td>
                <td class="px-4 py-4">{{ account.branch?.name || '-' }}</td>
                <td class="px-4 py-4">{{ typeLabel(account.type) }}</td>
                <td class="px-4 py-4">{{ Number(account.opening_balance || 0).toFixed(2) }}</td>
                <td class="px-4 py-4 font-black text-emerald-700">{{ Number(account.receipts_total || 0).toFixed(2) }}</td>
                <td class="px-4 py-4 font-black text-rose-700">{{ Number(account.payments_total || 0).toFixed(2) }}</td>
                <td class="px-4 py-4 font-black text-cyan-700">{{ Number(account.current_balance || 0).toFixed(2) }}</td>
                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="account.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ account.is_active ? 'فعال' : 'موقوف' }}
                  </span>
                </td>
                <td class="px-4 py-4">
                  <div class="flex gap-2">
                    <Link :href="`/financial-accounts/${account.id}`">
                      <BaseButton label="كشف" color="info" small />
                    </Link>

                    <Link v-if="props.permissions?.canUpdate" :href="`/financial-accounts/${account.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>
                  </div>
                </td>
              </tr>

              <tr v-if="!accountsData.length">
                <td colspan="10" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد خزائن أو حسابات بنكية.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>
    </div>
  </MainLayout>
</template>