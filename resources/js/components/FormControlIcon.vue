<script setup>
import BaseIcon from '@/Components/BaseIcon.vue'

defineProps({
  icon: String,
  h: {
    type: String,
    default: 'h-11',
  },
})
</script>

<template>
  <BaseIcon
    :path="icon"
    w="w-10"
    :h="h"
    class="pointer-events-none absolute top-0 left-0 z-10 text-gray-400"
  />
</template>