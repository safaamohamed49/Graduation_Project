<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const form = useForm({
  name: '',
  code: '',
  description: '',
  is_active: true,
  notes: '',
})

const submit = () => {
  form.post('/categories')
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة فئة"
    hero-badge="الفئات / إضافة"
    hero-title="إضافة فئة جديدة"
    hero-description="أدخلي بيانات الفئة ورمزها حتى يتم استخدامها في تنظيم المنتجات والمخزون والتقارير."
    hero-back-href="/categories"
    hero-gradient-class="bg-gradient-to-br from-cyan-800 via-slate-900 to-indigo-900"
    card-title="بيانات الفئة"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">اسم الفئة</label>
        <FormControl v-model="form.name" type="text" placeholder="مثال: حديد ومعادن" />
        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">رمز الفئة</label>
        <FormControl v-model="form.code" type="text" placeholder="مثال: METAL" />
        <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">الوصف</label>
        <FormControl v-model="form.description" type="textarea" placeholder="وصف مختصر للفئة" />
        <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
        <FormControl v-model="form.notes" type="textarea" placeholder="أي ملاحظات إضافية" />
        <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</div>
      </div>

      <div class="md:col-span-2 flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
        <input id="is_active" v-model="form.is_active" type="checkbox" />
        <label for="is_active" class="text-sm font-bold text-slate-700">الفئة فعالة داخل النظام</label>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/categories">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ الفئة"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>