<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import BaseButton from '@/Components/BaseButton.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  warehouse: Object,
  products: Object,
  canManageWarehouses: Boolean,
})

const productsData = computed(() => props.products?.data ?? [])

const totalQuantity = computed(() => {
  return productsData.value.reduce((sum, item) => sum + Number(item.stock_quantity || 0), 0)
})
</script>

<template>
  <MainLayout :title="`مخزن ${warehouse.name}`">
    <div class="space-y-6">
      <PageHero
        badge="المخازن / المنتجات"
        :title="warehouse.name"
        :description="`عرض المنتجات والكميات داخل المخزن المرتبط بفرع: ${warehouse.branch?.name || '-'}`"
        gradient-class="bg-gradient-to-br from-emerald-900 via-slate-900 to-cyan-900"
      />

      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">الفرع</div>
          <div class="mt-3 text-2xl font-black text-slate-800">{{ warehouse.branch?.name || '-' }}</div>
          <div class="mt-2 text-xs text-slate-400">الفرع المرتبط بالمخزن</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">عدد المنتجات</div>
          <div class="mt-3 text-3xl font-black text-cyan-700">{{ props.products?.total ?? 0 }}</div>
          <div class="mt-2 text-xs text-slate-400">منتجات لديها حركة داخل المخزن</div>
        </div>

        <div class="rounded-[28px] bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <div class="text-sm font-bold text-slate-500">إجمالي الكميات</div>
          <div class="mt-3 text-3xl font-black text-emerald-700">{{ totalQuantity.toFixed(2) }}</div>
          <div class="mt-2 text-xs text-slate-400">حسب الصفحة الحالية</div>
        </div>
      </section>

      <div class="flex flex-wrap justify-between gap-3">
        <Link href="/warehouses">
          <BaseButton label="رجوع للمخازن" color="light" />
        </Link>

        <Link v-if="canManageWarehouses" :href="`/warehouses/${warehouse.id}/edit`">
          <BaseButton label="تعديل المخزن" color="warning" />
        </Link>
      </div>

      <CardBox>
        <CardBoxComponentHeader title="المنتجات داخل المخزن" />

        <div class="overflow-x-auto">
          <table class="min-w-full text-right">
            <thead class="bg-slate-50">
              <tr class="text-sm text-slate-600">
                <th class="px-4 py-4 font-black">#</th>
                <th class="px-4 py-4 font-black">اسم المنتج</th>
                <th class="px-4 py-4 font-black">كود المنتج</th>
                <th class="px-4 py-4 font-black">الباركود</th>
                <th class="px-4 py-4 font-black">الوحدة</th>
                <th class="px-4 py-4 font-black">الكمية داخل المخزن</th>
                <th class="px-4 py-4 font-black">حد التنبيه</th>
                <th class="px-4 py-4 font-black">سعر البيع</th>
                <th class="px-4 py-4 font-black">الحالة</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(product, index) in productsData"
                :key="product.id"
                class="border-t text-sm text-slate-700 transition hover:bg-slate-50/80"
              >
                <td class="px-4 py-4">
                  {{ ((props.products.current_page - 1) * props.products.per_page) + index + 1 }}
                </td>

                <td class="px-4 py-4">
                  <span class="font-black text-slate-800">{{ product.name }}</span>
                </td>

                <td class="px-4 py-4">{{ product.product_code || '-' }}</td>
                <td class="px-4 py-4">{{ product.barcode || '-' }}</td>
                <td class="px-4 py-4">{{ product.unit_name || '-' }}</td>

                <td class="px-4 py-4 font-black text-emerald-700">
                  {{ Number(product.stock_quantity || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  {{ Number(product.minimum_stock || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4 font-black text-cyan-700">
                  {{ Number(product.current_price || 0).toFixed(2) }}
                </td>

                <td class="px-4 py-4">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-bold"
                    :class="Number(product.stock_quantity || 0) <= Number(product.minimum_stock || 0)
                      ? 'bg-rose-100 text-rose-700'
                      : 'bg-emerald-100 text-emerald-700'"
                  >
                    {{ Number(product.stock_quantity || 0) <= Number(product.minimum_stock || 0) ? 'منخفض' : 'متوفر' }}
                  </span>
                </td>
              </tr>

              <tr v-if="!productsData.length">
                <td colspan="9" class="px-4 py-14 text-center text-sm text-slate-500">
                  لا توجد منتجات داخل هذا المخزن حاليًا.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBox>

      <section v-if="props.products.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.products.links"
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
  </MainLayout>
</template>