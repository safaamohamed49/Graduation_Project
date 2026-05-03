<script setup>
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'ابحث' },
  createHref: { type: String, default: '' },
  createLabel: { type: String, default: 'إضافة جديد' },
})

const emit = defineEmits(['update:modelValue', 'search'])

const updateValue = (value) => {
  emit('update:modelValue', value)
}
</script>

<template>
  <section class="rounded-[30px] bg-white/90 p-4 shadow-xl shadow-slate-200/60 ring-1 ring-slate-200 backdrop-blur">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex w-full flex-col gap-3 sm:flex-row lg:max-w-2xl">
        <div class="relative flex-1 rounded-2xl bg-slate-50 p-1 ring-1 ring-slate-200">
          <FormControl
            :model-value="modelValue"
            type="text"
            :placeholder="placeholder"
            borderless
            transparent
            @update:model-value="updateValue"
            @keyup.enter="emit('search')"
          />
        </div>

        <BaseButton label="بحث" color="light" @click="emit('search')" />
      </div>

      <div class="flex flex-wrap gap-3">
        <Link v-if="createHref" :href="createHref">
          <BaseButton :label="createLabel" color="primary" />
        </Link>

        <slot />
      </div>
    </div>
  </section>
</template>