<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  customer: Object,
})

const form = useForm({
  name: props.customer.name ?? '',
  code: props.customer.code ?? '',
  phone: props.customer.phone ?? '',
  email: props.customer.email ?? '',
  address: props.customer.address ?? '',
  notes: props.customer.notes ?? '',
  is_active: !!props.customer.is_active,
})

const submit = () => {
  form.put(`/customers/${props.customer.id}`)
}
</script>

<template>
  <EntityFormShell
    page-title="تعديل عميل"
    hero-badge="العملاء / تعديل"
    hero-title="تعديل بيانات العميل"
    hero-description="راجعي وعدّلي بيانات العميل بشكل دقيق للحفاظ على سلامة البيانات داخل النظام."
    hero-back-href="/customers"
    hero-gradient-class="bg-gradient-to-l from-amber-500 via-orange-500 to-rose-500"
    card-title="بيانات العميل"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">اسم العميل</label>
        <FormControl v-model="form.name" type="text" />
        <div v-if="form.errors.name" class="mt-1 text-sm font-semibold text-red-600">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">رمز العميل</label>
        <FormControl v-model="form.code" type="text" />
        <div v-if="form.errors.code" class="mt-1 text-sm font-semibold text-red-600">{{ form.errors.code }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">رقم الهاتف</label>
        <FormControl v-model="form.phone" type="text" />
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">البريد الإلكتروني</label>
        <FormControl v-model="form.email" type="text" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">العنوان</label>
        <FormControl v-model="form.address" type="text" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
        <FormControl v-model="form.notes" type="textarea" />
      </div>

      <div class="md:col-span-2 flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
        <input id="is_active" v-model="form.is_active" type="checkbox" />
        <label for="is_active" class="text-sm font-bold text-slate-700">العميل فعال داخل النظام</label>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/customers">
          <BaseButton label="إلغاء" color="light" />
        </Link>
        <BaseButton
          label="حفظ التعديل"
          color="warning"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>