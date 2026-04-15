<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import BaseIcon from '@/Components/BaseIcon.vue'

const props = defineProps({
  label: [String, Number],
  icon: String,
  iconSize: {
    type: [String, Number],
    default: 20,
  },
  href: String,
  target: String,
  type: {
    type: String,
    default: 'button',
  },
  color: {
    type: String,
    default: 'primary',
  },
  small: Boolean,
  outline: Boolean,
  active: Boolean,
  disabled: Boolean,
  roundedFull: Boolean,
  method: {
    type: String,
    default: 'get',
  },
  as: String,
})

const is = computed(() => {
  if (props.as) return props.as
  if (props.href) return Link
  return 'button'
})

const componentClass = computed(() => {
  const base = [
    'inline-flex',
    'items-center',
    'justify-center',
    'gap-2',
    'border',
    'transition',
    'duration-200',
    props.roundedFull ? 'rounded-full' : 'rounded-lg',
    props.small ? 'px-3 py-1.5 text-sm' : 'px-4 py-2',
    props.disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
  ]

  const variants = {
    primary: props.outline
      ? 'border-blue-600 text-blue-600 bg-white hover:bg-blue-50'
      : 'border-blue-600 bg-blue-600 text-white hover:bg-blue-700',
    success: props.outline
      ? 'border-emerald-600 text-emerald-600 bg-white hover:bg-emerald-50'
      : 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700',
    danger: props.outline
      ? 'border-red-600 text-red-600 bg-white hover:bg-red-50'
      : 'border-red-600 bg-red-600 text-white hover:bg-red-700',
    warning: props.outline
      ? 'border-amber-500 text-amber-600 bg-white hover:bg-amber-50'
      : 'border-amber-500 bg-amber-500 text-white hover:bg-amber-600',
    light: props.outline
      ? 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
      : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
  }

  base.push(variants[props.color] || variants.primary)

  if (props.active) {
    base.push('ring-2 ring-offset-2 ring-blue-300')
  }

  return base
})
</script>

<template>
  <component
    :is="is"
    :href="href"
    :method="is === Link ? method : undefined"
    :target="target"
    :type="is === 'button' ? type : undefined"
    :disabled="is === 'button' ? disabled : undefined"
    :class="componentClass"
  >
    <BaseIcon v-if="icon" :path="icon" :size="iconSize" />
    <span v-if="label">{{ label }}</span>
    <slot v-else />
  </component>
</template>