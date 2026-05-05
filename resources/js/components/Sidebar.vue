<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

defineProps({
  isAsideOpen: Boolean,
})

const emit = defineEmits(['close'])

const page = usePage()
const currentUrl = computed(() => page.url)

const permissions = computed(() => page.props.auth?.user?.permissions ?? [])

const can = (permission) => {
  return permissions.value.includes('*') || permissions.value.includes(permission)
}
</script>

<template>
  <aside
    :class="[
      'fixed top-0 right-0 z-40 flex h-screen w-64 flex-col bg-gradient-to-b from-slate-950 via-slate-900 to-slate-800 text-white shadow-2xl transition-transform duration-300',
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

    <nav class="flex-1 space-y-6 overflow-y-auto p-4">
      <div v-if="can('dashboard.view')">
        <p class="mb-2 px-2 text-xs font-bold text-slate-400">عام</p>

        <Link
          href="/dashboard"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          لوحة التحكم
        </Link>
      </div>

      <div
        v-if="
          can('categories.view') ||
          can('products.view') ||
          can('branches.view') ||
          can('warehouses.view') ||
          can('customers.view') ||
          can('suppliers.view') ||
          can('partners.view') ||
          can('employees.view')
        "
      >
        <p class="mb-2 px-2 text-xs font-bold text-slate-400">إدارة البيانات</p>
         

        <Link
          v-if="can('financial_accounts.view')"
          href="/financial-accounts"
            class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
           :class="currentUrl.startsWith('/financial-accounts') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
          >
         الخزائن والبنوك
        </Link>
        <Link
          v-if="can('categories.view')"
          href="/categories"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/categories') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          الفئات
        </Link>

        <Link
          v-if="can('products.view')"
          href="/products"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/products') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          المنتجات
        </Link>

        <Link
          v-if="can('branches.view')"
          href="/branches"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/branches') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          الفروع
        </Link>

        <Link
          v-if="can('warehouses.view')"
          href="/warehouses"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/warehouses') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          المخازن
        </Link>
        <Link
          v-if="can('warehouses.transfer')"
          href="/stock-transfers"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/stock-transfers') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
        النقل بين المخازن
       </Link>
        <Link
          v-if="can('customers.view')"
          href="/customers"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/customers') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          العملاء
        </Link>

        <Link
          v-if="can('suppliers.view')"
          href="/suppliers"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/suppliers') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          الموردين
        </Link>

        <Link
          v-if="can('partners.view')"
          href="/partners"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/partners') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          الشركاء
        </Link>

        <Link
          v-if="can('employees.view')"
          href="/employees"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/employees') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          الموظفين
        </Link>
      </div>

      <div v-if="can('purchase_invoices.view')">
        <p class="mb-2 px-2 text-xs font-bold text-slate-400">المشتريات</p>

        <Link
          href="/purchase-invoices"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/purchase-invoices') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          فواتير الشراء
        </Link>
      </div>

      <div v-if="can('orders.view') || can('returns.view')">
        <p class="mb-2 px-2 text-xs font-bold text-slate-400">المبيعات</p>

        <Link
          v-if="can('orders.view')"
          href="/orders"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/orders') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          فواتير البيع
        </Link>

        <Link
          v-if="can('returns.view')"
          href="/returns"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/returns') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          المرتجعات
        </Link>
      </div>

      <div v-if="can('payments.view') || can('receipts.view') || can('reports.view')">
        <p class="mb-2 px-2 text-xs font-bold text-slate-400">المالية والتقارير</p>

        <Link
         v-if="can('payments.view')"
           href="/payment-vouchers"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
         :class="currentUrl.startsWith('/payment-vouchers') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
         >
  إيصالات الصرف
</Link>

       <Link
  v-if="can('receipts.view')"
  href="/receipt-vouchers"
  class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
  :class="currentUrl.startsWith('/receipt-vouchers') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
>
  إيصالات القبض
</Link>
<Link
  v-if="can('reports.view')"
  href="/general-ledger"
  class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
  :class="currentUrl.startsWith('/general-ledger') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
>
  دفتر الأستاذ
</Link>
<Link
  v-if="can('fixed_assets.view')"
  href="/fixed-assets"
  class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
  :class="currentUrl.startsWith('/fixed-assets') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
>
  الأصول الثابتة
</Link>
 <Link
    href="/fixed-assets-report"
    class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
    :class="currentUrl.startsWith('/fixed-assets-report')
      ? 'bg-white/10 text-white'
      : 'text-slate-300 hover:bg-white/10 hover:text-white'"
  >
    تقرير الأصول
  </Link>
        <Link
          v-if="can('reports.view')"
          href="/reports"
          class="block rounded-2xl px-4 py-3 text-base font-semibold transition"
          :class="currentUrl.startsWith('/reports') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
        >
          التقارير
        </Link>

      </div>
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