<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const form = useForm({
  name: '',
  phone: '',
  email: '',
  address: '',
  capital_amount: 0,
  start_date: new Date().toISOString().slice(0, 10),
  is_active: true,
  notes: '',
})

const submit = () => {
  form.post('/partners')
}
</script>

<template>
  <EntityFormShell
    page-title="إضافة شريك"
    hero-badge="الشركاء / إضافة"
    hero-title="إضافة شريك جديد"
    hero-description="أدخلي بيانات الشريك ورأس المال، وسيقوم النظام بحساب نسبة الملكية تلقائيًا حسب إجمالي رؤوس أموال الشركاء النشطين."
    hero-back-href="/partners"
    hero-gradient-class="bg-gradient-to-br from-violet-700 via-purple-700 to-slate-900"
    card-title="بيانات الشريك"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">اسم الشريك</label>
        <FormControl v-model="form.name" type="text" placeholder="مثال: محمد أحمد" />
        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">رقم الهاتف</label>
        <FormControl v-model="form.phone" type="text" placeholder="0910000000" />
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">البريد الإلكتروني</label>
        <FormControl v-model="form.email" type="text" placeholder="example@mail.com" />
        <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">تاريخ بداية الشراكة</label>
        <FormControl v-model="form.start_date" type="date" />
        <div v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">رأس المال</label>
        <FormControl v-model="form.capital_amount" type="number" />
        <div v-if="form.errors.capital_amount" class="mt-1 text-sm text-red-600">{{ form.errors.capital_amount }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">نسبة الملكية</label>
        <div class="rounded-2xl bg-violet-50 px-4 py-3 text-sm font-bold leading-7 text-violet-700 ring-1 ring-violet-200">
          تُحسب تلقائيًا بعد الحفظ حسب رأس المال وإجمالي رؤوس أموال الشركاء النشطين.
        </div>
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">العنوان</label>
        <FormControl v-model="form.address" type="text" placeholder="عنوان الشريك" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
        <FormControl v-model="form.notes" type="textarea" />
      </div>

      <div class="md:col-span-2 flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
        <input id="is_active" v-model="form.is_active" type="checkbox" />
        <label for="is_active" class="text-sm font-bold text-slate-700">الشريك فعال داخل النظام</label>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/partners">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ الشريك"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>