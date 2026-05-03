<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  users: Array,
})

const form = useForm({
  name: '',
  code: '',
  address: '',
  phone: '',
  manager_user_id: '',
  is_active: true,
})

const submit = () => {
  form.post('/branches')
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة فرع"
    hero-badge="الفروع / إضافة"
    hero-title="إضافة فرع جديد"
    hero-description="أدخلي بيانات الفرع حتى يتم ربط المستخدمين والمخازن والحركات المحاسبية به."
    hero-back-href="/branches"
    hero-gradient-class="bg-gradient-to-br from-blue-900 via-slate-900 to-cyan-800"
    card-title="بيانات الفرع"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">اسم الفرع</label>
        <FormControl v-model="form.name" type="text" placeholder="مثال: الفرع الرئيسي" />
        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">كود الفرع</label>
        <FormControl v-model="form.code" type="text" placeholder="MAIN / BR001" />
        <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">مدير الفرع</label>
        <select
          v-model="form.manager_user_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
        >
          <option value="">بدون مدير</option>
          <option v-for="user in props.users" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
        <div v-if="form.errors.manager_user_id" class="mt-1 text-sm text-red-600">
          {{ form.errors.manager_user_id }}
        </div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">رقم الهاتف</label>
        <FormControl v-model="form.phone" type="text" placeholder="0910000000" />
        <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</div>
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">العنوان</label>
        <FormControl v-model="form.address" type="text" placeholder="عنوان الفرع" />
        <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
      </div>

      <div class="md:col-span-2 flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
        <input id="is_active" v-model="form.is_active" type="checkbox" />
        <label for="is_active" class="text-sm font-bold text-slate-700">الفرع فعال داخل النظام</label>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/branches">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ الفرع"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>