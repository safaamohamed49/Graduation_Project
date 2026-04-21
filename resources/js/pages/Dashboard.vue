<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'

defineProps({
  stats: {
    type: Object,
    required: true,
  },
})

const statCards = (stats) => [
  {
    title: 'العملاء',
    value: stats.customers_count,
    subtitle: 'إجمالي العملاء النشطين',
    gradient: 'from-blue-500 to-cyan-400',
    ring: 'ring-blue-500/20',
    iconBg: 'bg-blue-500/15',
    icon: '👥',
  },
  {
    title: 'الموردين',
    value: stats.suppliers_count,
    subtitle: 'عدد الموردين الحاليين',
    gradient: 'from-emerald-500 to-teal-400',
    ring: 'ring-emerald-500/20',
    iconBg: 'bg-emerald-500/15',
    icon: '🚚',
  },
  {
    title: 'المنتجات',
    value: stats.products_count,
    subtitle: 'الأصناف المتاحة في المنظومة',
    gradient: 'from-amber-500 to-orange-400',
    ring: 'ring-amber-500/20',
    iconBg: 'bg-amber-500/15',
    icon: '📦',
  },
  {
    title: 'الطلبيات',
    value: stats.orders_count,
    subtitle: 'إجمالي الفواتير والطلبيات',
    gradient: 'from-violet-500 to-fuchsia-400',
    ring: 'ring-violet-500/20',
    iconBg: 'bg-violet-500/15',
    icon: '🧾',
  },
]
</script>

<template>
  <MainLayout title="لوحة التحكم">
    <div class="space-y-8">
      <section class="relative overflow-hidden rounded-[28px] bg-slate-950 p-8 text-white shadow-2xl">
        <div class="absolute inset-0">
          <div class="absolute -top-16 -right-16 h-56 w-56 rounded-full bg-blue-500/20 blur-3xl"></div>
          <div class="absolute -bottom-16 -left-16 h-56 w-56 rounded-full bg-violet-500/20 blur-3xl"></div>
        </div>

        <div class="relative z-10 flex flex-col gap-8 xl:flex-row xl:items-center xl:justify-between">
          <div class="max-w-2xl">
            <div class="mb-4 inline-flex items-center rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-200 backdrop-blur-md">
              لوحة تحكم المنظومة
            </div>

            <h1 class="text-3xl font-black leading-tight md:text-4xl">
              أهلاً بك في
              <span class="bg-gradient-to-l from-blue-400 to-violet-400 bg-clip-text text-transparent">
                منظومة متين
              </span>
            </h1>

            <p class="mt-4 max-w-xl text-sm leading-7 text-slate-300 md:text-base">
              من هنا تقدري تتابعي حالة المنظومة بشكل سريع، وتشوفي أهم المؤشرات
              الخاصة بالعملاء، الموردين، المنتجات، والطلبيات.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-4 xl:min-w-[340px]">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
              <p class="text-xs text-slate-300">حالة النظام</p>
              <p class="mt-2 text-lg font-bold text-emerald-400">نشط</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
              <p class="text-xs text-slate-300">الواجهة</p>
              <p class="mt-2 text-lg font-bold text-blue-300">Vue + Inertia</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
              <p class="text-xs text-slate-300">قاعدة البيانات</p>
              <p class="mt-2 text-lg font-bold text-amber-300">MySQL</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
              <p class="text-xs text-slate-300">الإطار</p>
              <p class="mt-2 text-lg font-bold text-violet-300">Laravel</p>
            </div>
          </div>
        </div>
      </section>

      <section>
        <div class="mb-5">
          <h2 class="text-2xl font-black text-slate-800">الإحصائيات الرئيسية</h2>
          <p class="mt-1 text-sm text-slate-500">ملخص سريع لأهم بيانات المنظومة</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
          <div
            v-for="card in statCards(stats)"
            :key="card.title"
            class="group relative overflow-hidden rounded-[24px] bg-white p-6 shadow-sm ring-1 transition duration-300 hover:-translate-y-1 hover:shadow-xl"
            :class="card.ring"
          >
            <div class="absolute top-0 left-0 h-1.5 w-full bg-gradient-to-r" :class="card.gradient"></div>

            <div class="mb-5 flex items-center justify-between">
              <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl text-2xl"
                :class="card.iconBg"
              >
                {{ card.icon }}
              </div>

              <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                ملخص
              </div>
            </div>

            <p class="text-sm font-semibold text-slate-500">{{ card.title }}</p>
            <h3 class="mt-2 text-4xl font-black text-slate-800">
              {{ card.value }}
            </h3>
            <p class="mt-3 text-sm leading-6 text-slate-500">
              {{ card.subtitle }}
            </p>
          </div>
        </div>
      </section>
    </div>
  </MainLayout>
</template>