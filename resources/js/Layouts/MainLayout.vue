<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const isAsideOpen = ref(false)
const page = usePage()

const toggleAside = () => {
  isAsideOpen.value = !isAsideOpen.value
}

const closeAside = () => {
  isAsideOpen.value = false
}

const currentUrl = page.url
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-800" dir="rtl">
    <!-- Overlay للموبايل -->
    <div
      v-if="isAsideOpen"
      class="fixed inset-0 z-30 bg-black/40 lg:hidden"
      @click="closeAside"
    ></div>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed top-0 right-0 z-40 h-screen w-64 bg-slate-900 text-white shadow-xl transition-transform duration-300',
        isAsideOpen ? 'translate-x-0' : 'translate-x-full',
        'lg:translate-x-0'
      ]"
    >
      <div class="flex h-16 items-center justify-between border-b border-slate-800 px-5">
        <h1 class="text-xl font-bold">متين</h1>
        <button
          class="rounded-md px-2 py-1 text-sm hover:bg-slate-800 lg:hidden"
          @click="closeAside"
        >
          ✕
        </button>
      </div>

      <nav class="p-4 space-y-2">
        <Link
          href="/dashboard"
          class="block rounded-lg px-4 py-3 transition hover:bg-slate-800"
          :class="currentUrl.startsWith('/dashboard') ? 'bg-slate-800 font-bold' : ''"
        >
          لوحة التحكم
        </Link>

        <Link
          href="/products"
          class="block rounded-lg px-4 py-3 transition hover:bg-slate-800"
          :class="currentUrl.startsWith('/products') ? 'bg-slate-800 font-bold' : ''"
        >
          المنتجات
        </Link>

        <Link
          href="/customers"
          class="block rounded-lg px-4 py-3 transition hover:bg-slate-800"
          :class="currentUrl.startsWith('/customers') ? 'bg-slate-800 font-bold' : ''"
        >
          العملاء
        </Link>

        <Link
          href="/suppliers"
          class="block rounded-lg px-4 py-3 transition hover:bg-slate-800"
          :class="currentUrl.startsWith('/suppliers') ? 'bg-slate-800 font-bold' : ''"
        >
          الموردين
        </Link>
      </nav>

      <div class="absolute bottom-0 right-0 left-0 border-t border-slate-800 p-4">
        <Link
          href="/logout"
          method="post"
          as="button"
          class="w-full rounded-lg bg-red-600 px-4 py-2 text-center font-semibold transition hover:bg-red-700"
        >
          تسجيل الخروج
        </Link>
      </div>
    </aside>

    <!-- Main area -->
    <div class="lg:pr-64">
      <!-- Top header -->
      <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b bg-white px-4 shadow-sm lg:px-6">
        <div class="flex items-center gap-3">
          <button
            class="rounded-lg border px-3 py-2 hover:bg-gray-100 lg:hidden"
            @click="toggleAside"
          >
            ☰
          </button>
          <h2 class="text-lg font-bold">لوحة النظام</h2>
        </div>

        <div class="text-sm text-gray-500">
          {{ page.props.auth?.user?.name || 'مستخدم النظام' }}
        </div>
      </header>

      <!-- Page content -->
      <main class="p-4 lg:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>