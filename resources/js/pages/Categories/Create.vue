<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
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
  <MainLayout title="إضافة فئة">
    <div class="space-y-6">
      <section class="rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-black text-slate-800">إضافة فئة جديدة</h1>
            <p class="mt-1 text-sm text-slate-500">أدخلي بيانات الفئة بشكل واضح ومنظم</p>
          </div>

          <Link href="/categories">
            <BaseButton label="رجوع" color="light" />
          </Link>
        </div>
      </section>

      <CardBox is-form @submit.prevent="submit">
        <CardBoxComponentHeader title="بيانات الفئة" />

        <div class="grid gap-4 p-6 md:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">اسم الفئة</label>
            <FormControl v-model="form.name" type="text" />
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">رمز الفئة</label>
            <FormControl v-model="form.code" type="text" />
            <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">الوصف</label>
            <FormControl v-model="form.description" type="textarea" />
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">ملاحظات</label>
            <FormControl v-model="form.notes" type="textarea" />
          </div>

          <div class="md:col-span-2 flex items-center gap-3">
            <input id="is_active" v-model="form.is_active" type="checkbox" />
            <label for="is_active" class="text-sm font-semibold text-slate-700">الفئة فعالة</label>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-end gap-3">
            <Link href="/categories">
              <BaseButton label="إلغاء" color="light" />
            </Link>
            <BaseButton label="حفظ" color="primary" type="submit" :disabled="form.processing" />
          </div>
        </template>
      </CardBox>
    </div>
  </MainLayout>
</template>