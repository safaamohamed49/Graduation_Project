<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  financialAccount: Object,
  branches: Array,
  accounts: Array,
  isAdmin: Boolean,
  authBranchId: Number,
})

const form = useForm({
  branch_id: props.isAdmin ? props.financialAccount.branch_id : props.authBranchId,
  name: props.financialAccount.name ?? '',
  code: props.financialAccount.code ?? '',
  type: props.financialAccount.type ?? 'cash',
  account_id: props.financialAccount.account_id ?? '',
  currency: props.financialAccount.currency ?? 'LYD',
  opening_balance: props.financialAccount.opening_balance ?? 0,
  is_active: Boolean(props.financialAccount.is_active),
  notes: props.financialAccount.notes ?? '',
})

const submit = () => {
  form.put(`/financial-accounts/${props.financialAccount.id}`)
}
</script>

<template>
  <EntityFormShell
    page-title="تعديل خزينة / بنك"
    hero-badge="الخزائن والبنوك / تعديل"
    hero-title="تعديل الحساب المالي"
    hero-description="عدلي بيانات الخزينة أو البنك مع الانتباه أن الرصيد الافتتاحي يؤثر على الرصيد الحالي."
    hero-back-href="/financial-accounts"
    hero-gradient-class="bg-gradient-to-br from-slate-900 via-emerald-900 to-cyan-900"
    card-title="بيانات الحساب المالي"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الاسم</label>
        <FormControl v-model="form.name" type="text" />
        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الكود</label>
        <FormControl v-model="form.code" type="text" />
        <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
      </div>

      <div v-if="props.isAdmin">
        <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
        <select v-model="form.branch_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none">
          <option value="">اختاري الفرع</option>
          <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">النوع</label>
        <select v-model="form.type" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none">
          <option value="cash">خزينة</option>
          <option value="bank">بنك</option>
        </select>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الحساب المحاسبي المرتبط</label>
        <select v-model="form.account_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none">
          <option value="">اختاري الحساب</option>
          <option v-for="account in props.accounts" :key="account.id" :value="account.id">
            {{ account.code }} - {{ account.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">العملة</label>
        <FormControl v-model="form.currency" type="text" />
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الرصيد الافتتاحي</label>
        <FormControl v-model="form.opening_balance" type="number" step="0.01" />
      </div>

      <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
        <input id="is_active" v-model="form.is_active" type="checkbox" />
        <label for="is_active" class="text-sm font-bold text-slate-700">فعال داخل النظام</label>
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
        <textarea v-model="form.notes" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none" />
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link :href="`/financial-accounts/${props.financialAccount.id}`">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton label="حفظ التعديل" color="primary" type="submit" :disabled="form.processing" />
      </div>
    </template>
  </EntityFormShell>
</template>