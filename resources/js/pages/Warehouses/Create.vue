<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  branches: Array,
})

const form = useForm({
  branch_id: '',
  name: '',
  code: '',
  type: '',
  address: '',
  phone: '',
  is_active: true,
})

const submit = () => {
  form.post('/warehouses')
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة مخزن"
    hero-badge="المخازن / إضافة"
    hero-title="إضافة مخزن جديد"
    hero-description="أدخلي بيانات المخزن واربطِيه بالفرع المناسب حتى يتم التحكم في صلاحيات العرض حسب فرع المستخدم."
    hero-back-href="/warehouses"
    hero-gradient-class="bg-gradient-to-br from-cyan-800 via-slate-900 to-emerald-900"
    card-title="بيانات المخزن"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">اسم المخزن</label>
        <FormControl v-model="form.name" type="text" placeholder="مثال: مخزن الفرع الرئيسي" />
        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">كود المخزن</label>
        <FormControl v-model="form.code" type="text" placeholder="WH-001" />
        <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
        <select
          v-model="form.branch_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
        >
          <option value="">اختاري الفرع</option>
          <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
        <div v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">{{ form.errors.branch_id }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">نوع المخزن</label>
        <FormControl v-model="form.type" type="text" placeholder="رئيسي / فرعي / تالف / مرتجعات" />
        <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">رقم الهاتف</label>
        <FormControl v-model="form.phone" type="text" placeholder="0910000000" />
        <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">العنوان</label>
        <FormControl v-model="form.address" type="text" placeholder="عنوان المخزن" />
        <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
      </div>

      <div class="md:col-span-2 flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
        <input id="is_active" v-model="form.is_active" type="checkbox" />
        <label for="is_active" class="text-sm font-bold text-slate-700">المخزن فعال داخل النظام</label>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/warehouses">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ المخزن"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>