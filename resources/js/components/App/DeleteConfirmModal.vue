<script setup>
import BaseButton from '@/Components/BaseButton.vue'

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'تأكيد الحذف',
  },
  itemName: {
    type: String,
    default: '',
  },
  message: {
    type: String,
    default: 'هل أنت متأكد من تنفيذ هذه العملية؟',
  },
  confirmLabel: {
    type: String,
    default: 'تأكيد الحذف',
  },
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 backdrop-blur-[2px]"
  >
    <div class="w-full max-w-md rounded-[30px] bg-white p-6 shadow-2xl ring-1 ring-slate-200">
      <div class="mb-4">
        <h2 class="text-xl font-black text-slate-800">{{ title }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">
          {{ message }}
          <span v-if="itemName" class="font-black text-slate-800">"{{ itemName }}"</span>
        </p>
      </div>

      <div class="flex justify-end gap-3">
        <BaseButton label="إلغاء" color="light" @click="emit('close')" />
        <BaseButton :label="confirmLabel" color="danger" @click="emit('confirm')" />
      </div>
    </div>
  </div>
</template>