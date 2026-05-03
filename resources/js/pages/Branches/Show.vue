<script setup>
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  branch: Object,
  permissions: Object,
})
</script>

<template>
  <MainLayout :title="props.branch.name">
    <div class="space-y-6">
      <PageHero
        badge="تفاصيل الفرع"
        :title="props.branch.name"
        description="عرض بيانات الفرع والمخازن والحركات المرتبطة به داخل النظام."
        gradient-class="bg-gradient-to-br from-blue-900 via-slate-900 to-cyan-800"
      />

      <div class="flex justify-end gap-3">
        <Link href="/branches">
          <BaseButton label="رجوع" color="light" />
        </Link>

        <Link v-if="props.permissions?.canUpdate" :href="`/branches/${props.branch.id}/edit`">
          <BaseButton label="تعديل الفرع" color="warning" />
        </Link>
      </div>

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المخازن</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">{{ props.branch.warehouses_count || 0 }}</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">المستخدمين</div>
          <div class="mt-3 text-3xl font-black text-blue-700">{{ props.branch.users_count || 0 }}</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">العملاء</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">{{ props.branch.customers_count || 0 }}</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الفواتير</div>
          <div class="mt-3 text-3xl font-black text-slate-800">{{ props.branch.orders_count || 0 }}</div>
        </div>
      </section>

      <CardBox>
        <CardBoxComponentHeader title="بيانات الفرع" />

        <div class="grid gap-5 p-6 md:grid-cols-2">
          <div>
            <div class="text-xs font-bold text-slate-400">اسم الفرع</div>
            <div class="mt-2 font-black text-slate-800">{{ props.branch.name }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">الكود</div>
            <div class="mt-2 font-black text-slate-800">{{ props.branch.code }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">المدير</div>
            <div class="mt-2 font-black text-slate-800">{{ props.branch.manager?.name || '-' }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">الهاتف</div>
            <div class="mt-2 font-black text-slate-800">{{ props.branch.phone || '-' }}</div>
          </div>

          <div class="md:col-span-2">
            <div class="text-xs font-bold text-slate-400">العنوان</div>
            <div class="mt-2 font-black text-slate-800">{{ props.branch.address || '-' }}</div>
          </div>

          <div>
            <div class="text-xs font-bold text-slate-400">الحالة</div>
            <div class="mt-2">
              <span
                class="rounded-full px-3 py-1 text-xs font-bold"
                :class="props.branch.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
              >
                {{ props.branch.is_active ? 'فعال' : 'غير فعال' }}
              </span>
            </div>
          </div>
        </div>
      </CardBox>

      <CardBox>
        <CardBoxComponentHeader title="مخازن الفرع" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">اسم المخزن</th>
                <th class="px-4 py-4 font-black">الكود</th>
                <th class="px-4 py-4 font-black">النوع</th>
                <th class="px-4 py-4 font-black">الحالة</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="warehouse in props.branch.warehouses"
                :key="warehouse.id"
                class="border-t text-sm text-slate-700"
              >
                <td class="px-4 py-4 font-black">{{ warehouse.name }}</td>
                <td class="px-4 py-4">{{ warehouse.code || '-' }}</td>
                <td class="px-4 py-4">{{ warehouse.type || '-' }}</td>
                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="warehouse.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ warehouse.is_active ? 'فعال' : 'غير فعال' }}
                  </span>
                </td>
              </tr>

              <tr v-if="!props.branch.warehouses?.length">
                <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">
                  لا توجد مخازن مربوطة بهذا الفرع.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>
    </div>
  </MainLayout>
</template>