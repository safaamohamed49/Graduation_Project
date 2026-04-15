<script setup>
import { computed, useSlots } from 'vue'
import CardBoxComponentBody from '@/Components/CardBoxComponentBody.vue'
import CardBoxComponentFooter from '@/Components/CardBoxComponentFooter.vue'

const props = defineProps({
  rounded: {
    type: String,
    default: 'rounded-2xl',
  },
  flex: {
    type: String,
    default: 'flex-col',
  },
  hasComponentLayout: Boolean,
  hasTable: Boolean,
  isForm: Boolean,
  isHoverable: Boolean,
})

const emit = defineEmits(['submit'])
const slots = useSlots()

const hasFooterSlot = computed(() => slots.footer && !!slots.footer())

const componentClass = computed(() => {
  const base = [
    'flex',
    'bg-white',
    'shadow-sm',
    'ring-1',
    'ring-gray-100',
    props.rounded,
    props.flex,
  ]

  if (props.isHoverable) {
    base.push('hover:shadow-md', 'transition-shadow', 'duration-300')
  }

  return base
})

const submit = (event) => {
  emit('submit', event)
}
</script>

<template>
  <component
    :is="isForm ? 'form' : 'div'"
    :class="componentClass"
    @submit.prevent="submit"
  >
    <slot v-if="hasComponentLayout" />
    <template v-else>
      <CardBoxComponentBody :no-padding="hasTable">
        <slot />
      </CardBoxComponentBody>

      <CardBoxComponentFooter v-if="hasFooterSlot">
        <slot name="footer" />
      </CardBoxComponentFooter>
    </template>
  </component>
</template>