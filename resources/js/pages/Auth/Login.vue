<template>
  <div class="min-h-screen bg-slate-950 text-white relative overflow-hidden" dir="rtl">
    <!-- Background glow -->
    <div class="absolute inset-0">
      <div class="absolute top-[-120px] right-[-120px] h-80 w-80 rounded-full bg-blue-600/20 blur-3xl"></div>
      <div class="absolute bottom-[-120px] left-[-120px] h-80 w-80 rounded-full bg-violet-600/20 blur-3xl"></div>
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.08),transparent_30%),radial-gradient(circle_at_bottom_left,rgba(255,255,255,0.06),transparent_30%)]"></div>
    </div>

    <div class="relative z-10 grid min-h-screen lg:grid-cols-2">
      <!-- Right side / Brand -->
      <div class="hidden lg:flex flex-col justify-between p-12 xl:p-16">
        <div>
          <div class="inline-flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-violet-600 text-xl font-black shadow-lg">
              م
            </div>
            <div>
              <h1 class="text-xl font-extrabold tracking-wide">منظومة متين</h1>
              <p class="text-sm text-slate-300">إدارة احترافية للمبيعات والمشتريات والمخزون</p>
            </div>
          </div>

          <div class="mt-16 max-w-xl">
            <h2 class="text-4xl xl:text-5xl font-black leading-tight">
              واجهة عصرية
              <span class="bg-gradient-to-l from-blue-400 to-violet-400 bg-clip-text text-transparent">
                لإدارة أعمالك
              </span>
            </h2>
            <p class="mt-6 text-lg leading-8 text-slate-300">
              تحكم في العملاء، الموردين، المنتجات، الفواتير والتقارير من خلال لوحة أنيقة وسريعة ومصممة
              لتناسب بيئة العمل الفعلية.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
            <p class="text-sm text-slate-300">العملاء</p>
            <p class="mt-2 text-2xl font-bold">+ إدارة</p>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
            <p class="text-sm text-slate-300">الفواتير</p>
            <p class="mt-2 text-2xl font-bold">+ تنظيم</p>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md">
            <p class="text-sm text-slate-300">التقارير</p>
            <p class="mt-2 text-2xl font-bold">+ متابعة</p>
          </div>
        </div>
      </div>

      <!-- Left side / Login -->
      <div class="flex items-center justify-center p-6 sm:p-10">
        <div class="w-full max-w-md">
          <div class="rounded-3xl border border-white/10 bg-white/10 p-8 shadow-2xl backdrop-blur-2xl sm:p-10">
            <div class="mb-8 text-center">
              <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-600 text-2xl font-black shadow-xl lg:hidden">
                م
              </div>
              <h2 class="text-3xl font-black">تسجيل الدخول</h2>
              <p class="mt-2 text-sm text-slate-300">
                أدخلي بياناتك للوصول إلى لوحة التحكم
              </p>
            </div>

            <div
              v-if="usernameError"
              class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-200"
            >
              {{ usernameError }}
            </div>

            <form class="space-y-5" @submit.prevent="submit">
              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-200">
                  اسم المستخدم
                </label>
                <input
                  v-model="form.username"
                  type="text"
                  autocomplete="username"
                  placeholder="مثال: hedaya@bennis"
                  class="w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-white outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-500/20"
                />
              </div>

              <div>
                <label class="mb-2 block text-sm font-semibold text-slate-200">
                  كلمة المرور
                </label>
                <input
                  v-model="form.password"
                  type="password"
                  autocomplete="current-password"
                  placeholder="أدخلي كلمة المرور"
                  class="w-full rounded-2xl border border-white/10 bg-slate-900/60 px-4 py-3 text-white outline-none transition focus:border-violet-400 focus:ring-4 focus:ring-violet-500/20"
                />
              </div>

              <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-2xl bg-gradient-to-l from-blue-600 to-violet-600 px-4 py-3 text-base font-bold text-white shadow-lg transition hover:scale-[1.01] hover:shadow-blue-900/40 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {{ form.processing ? 'جاري الدخول...' : 'دخول إلى المنظومة' }}
              </button>
            </form>

            <div class="mt-8 border-t border-white/10 pt-5 text-center text-xs text-slate-400">
              منظومة إدارة احترافية مبنية بـ Laravel + Vue
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const page = usePage()

const form = useForm({
  username: '',
  password: '',
})

const usernameError = computed(() => page.props.errors?.username || '')

const submit = () => {
  form.post('/login')
}
</script>