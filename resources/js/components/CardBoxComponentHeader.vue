<script setup>
import BaseIcon from '@/Components/BaseIcon.vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  icon: String,
  buttonIcon: String,
})

const emit = defineEmits(['button-click'])

const buttonClick = (event) => {
  emit('button-click', event)
}
</script>

<template>
  <header class="flex items-stretch border-b border-gray-100">
    <div class="flex grow items-center py-3 font-bold" :class="[icon ? 'px-4' : 'px-6']">
      <BaseIcon v-if="icon" :path="icon" class="mr-3" />
      {{ title }}
    </div>

    <button
      v-if="buttonIcon"
      type="button"
      class="flex items-center justify-center p-2 focus:outline-none"
      @click="buttonClick"
    >
      <BaseIcon :path="buttonIcon" />
    </button>
  </header>
</template>