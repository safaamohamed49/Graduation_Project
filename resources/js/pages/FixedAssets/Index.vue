<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHero from '@/Components/App/PageHero.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import EntityToolbar from '@/Components/App/EntityToolbar.vue'

const props = defineProps({
  assets: Object,
  filters: Object,
  branches: Array,
  permissions: Object,
  isAdmin: Boolean,
})

const search = ref(props.filters?.search ?? '')
const branchId = ref(props.filters?.branch_id ?? '')
const status = ref(props.filters?.status ?? '')

const assetsData = computed(() => props.assets?.data ?? [])

const totals = computed(() => {
  return assetsData.value.reduce(
    (result, asset) => {
      result.purchase += Number(asset.purchase_value || 0)
      result.depreciation += Number(asset.total_depreciation || 0)
      result.book += Number(asset.book_value || 0)
      return result
    },
    { purchase: 0, depreciation: 0, book: 0 }
  )
})

const applyFilters = () => {
  router.get('/fixed-assets', {
    search: search.value,
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
  branchId.value = ''
  status.value = ''

  router.get('/fixed-assets', {}, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}
</script>

<template>
  <MainLayout title="الأصول الثابتة">
    <div class="space-y-6">
      <PageHero
        badge="المحاسبة / الأصول"
        title="الأصول الثابتة"
        description="إدارة الأصول الثابتة، قيود الشراء، الإهلاك، والقيمة الدفترية لكل أصل داخل الفروع."
        gradient-class="bg-gradient-to-br from-indigo-900 via-slate-900 to-cyan-900"
      />

      <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد الأصول</div>
          <div class="mt-3 text-3xl font-black text-slate-800">
            {{ props.assets?.total ?? 0 }}
          </div>
          <div class="mt-2 text-xs text-slate-400">إجمالي الأصول المسجلة</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي قيمة الشراء</div>
          <div class="mt-3 text-3xl font-black text-indigo-700">
            {{ totals.purchase.toFixed(2) }}
          </div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الإهلاك</div>
          <div class="mt-3 text-3xl font-black text-rose-700">
            {{ totals.depreciation.toFixed(2) }}
          </div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">القيمة الدفترية</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">
            {{ totals.book.toFixed(2) }}
          </div>
          <div class="mt-2 text-xs text-slate-400">قيمة الشراء ناقص الإهلاك</div>
        </div>
      </section>

      <EntityToolbar
        v-model="search"
        placeholder="ابحثي باسم الأصل أو الكود أو الملاحظات"
        :create-href="props.permissions?.canCreate ? '/fixed-assets/create' : null"
        create-label="إضافة أصل ثابت"
        @search="applyFilters"
      />

      <CardBox>
        <CardBoxComponentHeader title="فلاتر الأصول" />

        <div class="grid gap-4 p-6 md:grid-cols-5">
          <div v-if="props.isAdmin">
            <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
            <select
              v-model="branchId"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            >
              <option value="">كل الفروع</option>
              <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
                {{ branch.name }} - {{ branch.code }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-black text-slate-700">الحالة</label>
            <select
              v-model="status"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            >
              <option value="">كل الحالات</option>
              <option value="1">فعال</option>
              <option value="0">موقوف</option>
            </select>
          </div>

          <div class="flex items-end gap-2 md:col-span-3">
            <BaseButton label="تطبيق الفلترة" color="primary" @click="applyFilters" />
            <BaseButton label="مسح" color="light" @click="resetFilters" />
          </div>
        </div>
      </CardBox>

      <CardBox>
        <CardBoxComponentHeader title="قائمة الأصول الثابتة" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">الأصل</th>
                <th class="px-4 py-4 font-black">الكود</th>
                <th class="px-4 py-4 font-black">الفرع</th>
                <th class="px-4 py-4 font-black">تاريخ الشراء</th>
                <th class="px-4 py-4 font-black">قيمة الشراء</th>
                <th class="px-4 py-4 font-black">الإهلاك</th>
                <th class="px-4 py-4 font-black">القيمة الدفترية</th>
                <th class="px-4 py-4 font-black">الحالة</th>
                <th class="px-4 py-4 font-black">الإجراءات</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(asset, index) in assetsData"
                :key="asset.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.assets.current_page - 1) * props.assets.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-800">{{ asset.name }}</span>
                    <span class="text-xs text-slate-400">
                      {{ asset.asset_account?.name || 'حساب الأصل غير محدد' }}
                    </span>
                  </div>
                </td>

                <td class="px-4 py-4 font-bold">
                  {{ asset.asset_code }}
                </td>

                <td class="px-4 py-4">
                  {{ asset.branch?.name || '-' }}
                </td>

                <td class="px-4 py-4">
                  {{ asset.purchase_date ? new Date(asset.purchase_date).toLocaleDateString('en-GB') : '-' }}
                </td>

                <td class="px-4 py-4 font-black text-indigo-700">
                  {{ Number(asset.purchase_value || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-rose-700">
                  {{ Number(asset.total_depreciation || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-cyan-700">
                  {{ Number(asset.book_value || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="asset.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                  >
                    {{ asset.is_active ? 'فعال' : 'موقوف' }}
                  </span>
                </td>

                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-2">
                    <Link :href="`/fixed-assets/${asset.id}`">
                      <BaseButton label="عرض" color="info" small />
                    </Link>

                    <Link v-if="props.permissions?.canUpdate" :href="`/fixed-assets/${asset.id}/edit`">
                      <BaseButton label="تعديل" color="warning" small />
                    </Link>
                  </div>
                </td>
              </tr>

              <tr v-if="!assetsData.length">
                <td colspan="10" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد أصول ثابتة مطابقة.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.assets.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.assets.links"
          :key="link.label"
          class="rounded-2xl border px-4 py-2 text-sm font-semibold transition"
          :class="[
            link.active
              ? 'border-cyan-600 bg-cyan-600 text-white'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
            !link.url ? 'cursor-not-allowed opacity-50' : ''
          ]"
          :disabled="!link.url"
          @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })"
          v-html="link.label"
        />
      </section>
    </div>
  </MainLayout>
</template>