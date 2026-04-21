<script setup>
import { ref } from 'vue'
import Sidebar from '@/Components/Sidebar.vue'
import Header from '@/Components/Header.vue'

defineProps({
  title: {
    type: String,
    default: 'لوحة النظام',
  },
})

const isAsideOpen = ref(false)

const toggleAside = () => {
  isAsideOpen.value = !isAsideOpen.value
}

const closeAside = () => {
  isAsideOpen.value = false
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 text-slate-800" dir="rtl">
    <!-- Overlay -->
    <div
      v-if="isAsideOpen"
      class="fixed inset-0 z-30 bg-black/40 lg:hidden"
      @click="closeAside"
    ></div>

    <Sidebar :is-aside-open="isAsideOpen" @close="closeAside" />

    <div class="min-h-screen lg:pr-64">
      <Header :title="title" @toggle-aside="toggleAside" />

      <main class="p-4 lg:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>