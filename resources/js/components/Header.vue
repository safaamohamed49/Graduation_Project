<script setup>
import { usePage } from '@inertiajs/vue3'

defineProps({
  title: {
    type: String,
    default: 'لوحة النظام',
  },
})

defineEmits(['toggle-aside'])

const page = usePage()
</script>

<template>
  <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur lg:px-8">
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="rounded-xl border border-slate-200 px-3 py-2 text-lg transition hover:bg-slate-100 lg:hidden"
        @click="$emit('toggle-aside')"
      >
        ☰
      </button>

      <div>
        <h2 class="text-xl font-black text-slate-800">{{ title }}</h2>
        <p class="text-xs text-slate-500">واجهة الإدارة الحديثة</p>
      </div>
    </div>

    <div class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">
      {{ page.props.auth?.user?.name || 'مستخدم النظام' }}
    </div>
  </header>
</template>