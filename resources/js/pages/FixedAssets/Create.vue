<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  branches: Array,
  financialAccounts: Array,
  accounts: Array,
  isAdmin: Boolean,
  authBranchId: Number,
})

const form = useForm({
  branch_id: props.isAdmin ? '' : props.authBranchId,
  name: '',
  asset_code: '',
  purchase_date: new Date().toISOString().slice(0, 10),
  purchase_value: 0,
  salvage_value: 0,
  useful_life_years: 5,
  depreciation_method: 'straight_line',
  financial_account_id: '',
  asset_account_id: '',
  depreciation_account_id: '',
  depreciation_expense_account_id: '',
  is_active: true,
  notes: '',
})

const depreciableValue = computed(() => {
  return Math.max(0, Number(form.purchase_value || 0) - Number(form.salvage_value || 0))
})

const yearlyDepreciation = computed(() => {
  const years = Number(form.useful_life_years || 0)
  return years > 0 ? depreciableValue.value / years : 0
})

const monthlyDepreciation = computed(() => {
  const years = Number(form.useful_life_years || 0)
  return years > 0 ? depreciableValue.value / (years * 12) : 0
})

const submit = () => {
  form.post('/fixed-assets')
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة أصل ثابت"
    hero-badge="الأصول الثابتة / إضافة"
    hero-title="إضافة أصل جديد"
    hero-description="أضيفي أصل ثابت واربطِيه بالخزينة وحسابات الأصل والإهلاك. عند الحفظ سيتم تسجيل قيد شراء الأصل تلقائياً."
    hero-back-href="/fixed-assets"
    hero-gradient-class="bg-gradient-to-br from-indigo-900 via-slate-900 to-cyan-900"
    card-title="بيانات الأصل الثابت"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div v-if="props.isAdmin">
        <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
        <select
          v-model="form.branch_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
        >
          <option value="">اختاري الفرع</option>
          <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
            {{ branch.name }} - {{ branch.code }}
          </option>
        </select>
        <div v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">{{ form.errors.branch_id }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">اسم الأصل</label>
        <FormControl v-model="form.name" type="text" placeholder="مثال: سيارة نقل / رافعة / جهاز كمبيوتر" />
        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">كود الأصل</label>
        <FormControl v-model="form.asset_code" type="text" placeholder="FA-001" />
        <div v-if="form.errors.asset_code" class="mt-1 text-sm text-red-600">{{ form.errors.asset_code }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">تاريخ الشراء</label>
        <FormControl v-model="form.purchase_date" type="date" />
        <div v-if="form.errors.purchase_date" class="mt-1 text-sm text-red-600">{{ form.errors.purchase_date }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">قيمة الشراء</label>
        <FormControl v-model="form.purchase_value" type="number" step="0.01" placeholder="0.00" />
        <div v-if="form.errors.purchase_value" class="mt-1 text-sm text-red-600">{{ form.errors.purchase_value }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">القيمة المتبقية / التخريدية</label>
        <FormControl v-model="form.salvage_value" type="number" step="0.01" placeholder="0.00" />
        <p class="mt-1 text-xs font-bold text-slate-400">هي القيمة المتوقع بقاءها للأصل بعد نهاية عمره.</p>
        <div v-if="form.errors.salvage_value" class="mt-1 text-sm text-red-600">{{ form.errors.salvage_value }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">العمر الإنتاجي بالسنوات</label>
        <FormControl v-model="form.useful_life_years" type="number" min="1" placeholder="5" />
        <div v-if="form.errors.useful_life_years" class="mt-1 text-sm text-red-600">{{ form.errors.useful_life_years }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">طريقة الإهلاك</label>
        <select
          v-model="form.depreciation_method"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
        >
          <option value="straight_line">القسط الثابت</option>
        </select>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الخزينة / البنك المدفوع منه</label>
        <select
          v-model="form.financial_account_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
        >
          <option value="">اختاري الخزينة أو البنك</option>
          <option v-for="financialAccount in props.financialAccounts" :key="financialAccount.id" :value="financialAccount.id">
            {{ financialAccount.name }} - {{ financialAccount.code }}
          </option>
        </select>
        <div v-if="form.errors.financial_account_id" class="mt-1 text-sm text-red-600">{{ form.errors.financial_account_id }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">حساب الأصل</label>
        <select
          v-model="form.asset_account_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
        >
          <option value="">اختاري حساب الأصل</option>
          <option v-for="account in props.accounts" :key="account.id" :value="account.id">
            {{ account.code }} - {{ account.name }}
          </option>
        </select>
        <p class="mt-1 text-xs font-bold text-slate-400">هذا الحساب يكون مدين عند شراء الأصل.</p>
        <div v-if="form.errors.asset_account_id" class="mt-1 text-sm text-red-600">{{ form.errors.asset_account_id }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">حساب مجمع الإهلاك</label>
        <select
          v-model="form.depreciation_account_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
        >
          <option value="">اختاري حساب مجمع الإهلاك</option>
          <option v-for="account in props.accounts" :key="account.id" :value="account.id">
            {{ account.code }} - {{ account.name }}
          </option>
        </select>
        <p class="mt-1 text-xs font-bold text-slate-400">هذا الحساب يكون دائن عند تسجيل الإهلاك.</p>
        <div v-if="form.errors.depreciation_account_id" class="mt-1 text-sm text-red-600">{{ form.errors.depreciation_account_id }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">حساب مصروف الإهلاك</label>
        <select
          v-model="form.depreciation_expense_account_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
        >
          <option value="">اختاري حساب مصروف الإهلاك</option>
          <option v-for="account in props.accounts" :key="account.id" :value="account.id">
            {{ account.code }} - {{ account.name }}
          </option>
        </select>
        <p class="mt-1 text-xs font-bold text-slate-400">هذا الحساب يكون مدين عند تسجيل الإهلاك.</p>
        <div v-if="form.errors.depreciation_expense_account_id" class="mt-1 text-sm text-red-600">{{ form.errors.depreciation_expense_account_id }}</div>
      </div>

      <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
        <input id="is_active" v-model="form.is_active" type="checkbox" />
        <label for="is_active" class="text-sm font-bold text-slate-700">الأصل فعال داخل النظام</label>
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
        <textarea
          v-model="form.notes"
          rows="3"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
          placeholder="أي ملاحظات عن مكان الأصل أو حالته..."
        />
        <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</div>
      </div>

      <div class="md:col-span-2 rounded-[28px] bg-slate-50 p-5 ring-1 ring-slate-200">
        <div class="mb-4 text-sm font-black text-slate-700">معاينة الإهلاك بطريقة القسط الثابت</div>

        <div class="grid gap-3 md:grid-cols-3">
          <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
            <div class="text-xs font-bold text-slate-400">القيمة القابلة للإهلاك</div>
            <div class="mt-2 text-xl font-black text-slate-800">{{ depreciableValue.toFixed(2) }}</div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
            <div class="text-xs font-bold text-slate-400">الإهلاك السنوي المتوقع</div>
            <div class="mt-2 text-xl font-black text-cyan-700">{{ yearlyDepreciation.toFixed(2) }}</div>
          </div>

          <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200">
            <div class="text-xs font-bold text-slate-400">الإهلاك الشهري المتوقع</div>
            <div class="mt-2 text-xl font-black text-indigo-700">{{ monthlyDepreciation.toFixed(2) }}</div>
          </div>
        </div>

        <div class="mt-4 rounded-2xl bg-cyan-50 px-4 py-3 text-sm font-bold text-cyan-700">
          عند الحفظ سيتم إنشاء قيد شراء الأصل: الأصل مدين، والخزينة أو البنك دائن.
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/fixed-assets">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton label="حفظ الأصل" color="primary" type="submit" :disabled="form.processing" />
      </div>
    </template>
  </EntityFormShell>
</template>