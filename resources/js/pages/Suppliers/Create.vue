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
  phone: '',
  email: '',
  address: '',
  notes: '',
  is_active: true,
})

const submit = () => {
  form.post('/suppliers')
}
</script>

<template>
  <MainLayout title="إضافة مورد">
    <div class="space-y-6">
      <section class="rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-black text-slate-800">إضافة مورد جديد</h1>
            <p class="mt-1 text-sm text-slate-500">أدخلي بيانات المورد بشكل واضح</p>
          </div>

          <Link href="/suppliers">
            <BaseButton label="رجوع" color="light" />
          </Link>
        </div>
      </section>

      <CardBox is-form @submit.prevent="submit">
        <CardBoxComponentHeader title="بيانات المورد" />

        <div class="grid gap-4 p-6 md:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">اسم المورد</label>
            <FormControl v-model="form.name" type="text" />
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">رمز المورد</label>
            <FormControl v-model="form.code" type="text" />
            <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">الهاتف</label>
            <FormControl v-model="form.phone" type="text" />
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">البريد الإلكتروني</label>
            <FormControl v-model="form.email" type="text" />
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">العنوان</label>
            <FormControl v-model="form.address" type="text" />
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">ملاحظات</label>
            <FormControl v-model="form.notes" type="textarea" />
          </div>

          <div class="md:col-span-2 flex items-center gap-3">
            <input id="is_active" v-model="form.is_active" type="checkbox" />
            <label for="is_active" class="text-sm font-semibold text-slate-700">المورد فعال</label>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-end gap-3">
            <Link href="/suppliers">
              <BaseButton label="إلغاء" color="light" />
            </Link>
            <BaseButton label="حفظ المورد" color="primary" type="submit" :disabled="form.processing" />
          </div>
        </template>
      </CardBox>
    </div>
  </MainLayout>
</template>