<script setup>
import BaseButton from '@/Components/BaseButton.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  badge: String,
  title: { type: String, required: true },
  description: String,
  backHref: String,
  backLabel: { type: String, default: 'الرجوع' },
  gradientClass: {
    type: String,
    default: 'bg-gradient-to-br from-slate-950 via-slate-900 to-slate-700',
  },
})
</script>

<template>
  <section
    class="relative overflow-hidden rounded-[36px] p-7 text-white shadow-2xl ring-1 ring-white/10"
    :class="gradientClass"
  >
    <div class="absolute -left-20 -top-20 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>
    <div class="absolute -bottom-24 right-10 h-64 w-64 rounded-full bg-sky-400/20 blur-3xl"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.16),transparent_35%)]"></div>

    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
      <div class="max-w-3xl">
        <div
          v-if="badge"
          class="mb-4 inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-black shadow-sm backdrop-blur"
        >
          {{ badge }}
        </div>

        <h1 class="text-3xl font-black leading-tight md:text-4xl">
          {{ title }}
        </h1>

        <p v-if="description" class="mt-3 max-w-2xl text-sm leading-8 text-white/85">
          {{ description }}
        </p>
      </div>

      <div v-if="backHref" class="shrink-0">
        <Link :href="backHref">
          <BaseButton :label="backLabel" color="light" />
        </Link>
      </div>

      <slot v-else name="actions" />
    </div>
  </section>
</template>