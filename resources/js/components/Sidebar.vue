<script setup>
import { Link, usePage } from '@inertiajs/vue3'

defineProps({
  isAsideOpen: Boolean,
})

const emit = defineEmits(['close'])

const page = usePage()
const currentUrl = page.url
</script>

<template>
  <aside
    :class="[
      'fixed top-0 right-0 z-40 flex h-screen w-64 flex-col bg-slate-950 text-white shadow-2xl transition-transform duration-300',
      isAsideOpen ? 'translate-x-0' : 'translate-x-full',
      'lg:translate-x-0'
    ]"
  >
    <div class="flex h-20 items-center justify-between border-b border-white/10 px-6">
      <h1 class="text-2xl font-black tracking-wide">متين</h1>

      <button
        type="button"
        class="rounded-lg px-2 py-1 text-sm hover:bg-white/10 lg:hidden"
        @click="emit('close')"
      >
        ✕
      </button>
    </div>

    <nav class="flex-1 space-y-2 p-4">
      <Link
        href="/dashboard"
        class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
        :class="currentUrl.startsWith('/dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
      >
        لوحة التحكم
      </Link>

      <Link
        href="/categories"
        class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
        :class="currentUrl.startsWith('/categories') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
      >
        الفئات
      </Link>

      <Link
        href="/products"
        class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
        :class="currentUrl.startsWith('/products') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
      >
        المنتجات
      </Link>

      <Link
        href="/customers"
        class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
        :class="currentUrl.startsWith('/customers') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
      >
        العملاء
      </Link>

      <Link
        href="/suppliers"
        class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
        :class="currentUrl.startsWith('/suppliers') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
      >
        الموردين
      </Link>
    </nav>

    <div class="border-t border-white/10 p-4">
      <Link
        href="/logout"
        method="post"
        as="button"
        class="w-full rounded-2xl bg-red-600 px-4 py-3 text-center text-base font-bold text-white transition hover:bg-red-700"
      >
        تسجيل الخروج
      </Link>
    </div>
  </aside>
</template>