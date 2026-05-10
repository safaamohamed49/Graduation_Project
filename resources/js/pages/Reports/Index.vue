<script setup>
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHero from '@/Components/App/PageHero.vue'

const props = defineProps({
  cards: Array,
})
</script>

<template>
  <MainLayout title="التقارير">
    <div class="space-y-6">
      <PageHero
        badge="المحاسبة / التقارير"
        title="مركز التقارير"
        description="جميع التقارير المالية والتشغيلية للنظام."
        gradient-class="bg-gradient-to-br from-slate-900 via-slate-800 to-cyan-900"
      />

      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <Link
          v-for="card in props.cards"
          :key="card.href"
          :href="card.href"
          class="group overflow-hidden rounded-[30px] bg-white shadow-sm ring-1 ring-slate-200 transition duration-300 hover:-translate-y-1 hover:shadow-xl"
        >
          <div
            class="h-3 bg-gradient-to-r"
            :class="card.color"
          />

          <div class="p-6">
            <div class="text-xl font-black text-slate-800">
              {{ card.title }}
            </div>

            <div class="mt-3 text-sm font-bold leading-7 text-slate-500">
              {{ card.description }}
            </div>

            <div class="mt-6 flex items-center justify-between">
              <span class="text-sm font-black text-cyan-700">
                فتح التقرير
              </span>

              <div
                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 transition group-hover:bg-cyan-100 group-hover:text-cyan-700"
              >
                تقرير
              </div>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </MainLayout>
</template>