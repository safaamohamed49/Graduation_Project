<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import EntityFormShell from '@/Components/App/EntityFormShell.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  employee: Object,
  branches: Array,
  roles: Array,
  permissionsGroups: Array,
  canManagePermissions: Boolean,
})

const form = useForm({
  branch_id: props.employee.branch_id ?? '',
  name: props.employee.name ?? '',
  phone: props.employee.phone ?? '',
  email: props.employee.email ?? '',
  address: props.employee.address ?? '',
  salary: props.employee.salary ?? 0,
  hire_date: props.employee.hire_date ? String(props.employee.hire_date).slice(0, 10) : '',
  is_active: Boolean(props.employee.is_active),
  notes: props.employee.notes ?? '',

  has_user: Boolean(props.employee.user),
  username: props.employee.user?.username ?? '',
  password: '',
  role_id: props.employee.user?.role_id ?? '',
  is_locked: Boolean(props.employee.user?.is_locked),
  extra_permissions: props.employee.user?.extra_permissions ?? [],
  denied_permissions: props.employee.user?.denied_permissions ?? [],
})

const togglePermission = (listName, permission) => {
  const list = form[listName]

  if (list.includes(permission)) {
    form[listName] = list.filter((item) => item !== permission)
  } else {
    form[listName] = [...list, permission]
  }

  if (listName === 'extra_permissions') {
    form.denied_permissions = form.denied_permissions.filter((item) => item !== permission)
  }

  if (listName === 'denied_permissions') {
    form.extra_permissions = form.extra_permissions.filter((item) => item !== permission)
  }
}

const rolePermissions = (roleId) => {
  const role = props.roles.find((item) => Number(item.id) === Number(roleId))
  return role?.permissions ?? []
}

const hasRolePermission = (permission) => {
  return rolePermissions(form.role_id).includes('*') || rolePermissions(form.role_id).includes(permission)
}

const submit = () => {
  form.put(`/employees/${props.employee.id}`)
}
</script>

<template>
  <EntityFormShell
    page-title="تعديل موظف"
    hero-badge="الموظفين / تعديل"
    hero-title="تعديل بيانات الموظف"
    hero-description="عدلي بيانات الموظف والراتب والفرع، ويمكنك تعديل حساب الدخول والصلاحيات الخاصة المرتبطة به."
    hero-back-href="/employees"
    hero-gradient-class="bg-gradient-to-br from-indigo-800 via-slate-900 to-cyan-900"
    card-title="بيانات الموظف"
    @submit="submit"
  >
    <div class="grid gap-5 p-6 md:grid-cols-2">
      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">اسم الموظف</label>
        <FormControl v-model="form.name" type="text" placeholder="مثال: أحمد محمد" />
        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الفرع</label>
        <select
          v-model="form.branch_id"
          class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
        >
          <option value="">اختاري الفرع</option>
          <option v-for="branch in props.branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
        <div v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">{{ form.errors.branch_id }}</div>
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">رقم الهاتف</label>
        <FormControl v-model="form.phone" type="text" placeholder="0910000000" />
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">البريد الإلكتروني</label>
        <FormControl v-model="form.email" type="text" placeholder="example@mail.com" />
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">الراتب الأساسي</label>
        <FormControl v-model="form.salary" type="number" placeholder="0.00" />
      </div>

      <div>
        <label class="mb-2 block text-sm font-black text-slate-700">تاريخ التوظيف</label>
        <FormControl v-model="form.hire_date" type="date" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">العنوان</label>
        <FormControl v-model="form.address" type="text" placeholder="عنوان الموظف" />
      </div>

      <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-black text-slate-700">ملاحظات</label>
        <FormControl v-model="form.notes" type="textarea" placeholder="أي ملاحظات عن الموظف" />
      </div>

      <div class="md:col-span-2 flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
        <input id="is_active" v-model="form.is_active" type="checkbox" />
        <label for="is_active" class="text-sm font-bold text-slate-700">الموظف فعال داخل النظام</label>
      </div>

      <div class="md:col-span-2 rounded-[26px] bg-indigo-50 p-5 ring-1 ring-indigo-100">
        <div class="flex items-center gap-3">
          <input id="has_user" v-model="form.has_user" type="checkbox" />
          <label for="has_user" class="text-sm font-black text-indigo-800">
            الموظف لديه حساب دخول للنظام
          </label>
        </div>
      </div>

      <template v-if="form.has_user">
        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">اسم المستخدم</label>
          <FormControl v-model="form.username" type="text" placeholder="مثال: ahmed.branch1" />
          <div v-if="form.errors.username" class="mt-1 text-sm text-red-600">{{ form.errors.username }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">كلمة المرور الجديدة</label>
          <FormControl v-model="form.password" type="password" placeholder="اتركيها فارغة إذا لا تريدي تغييرها" />
          <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-black text-slate-700">الدور</label>
          <select
            v-model="form.role_id"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
          >
            <option value="">اختاري الدور</option>
            <option v-for="role in props.roles" :key="role.id" :value="role.id">
              {{ role.name }}
            </option>
          </select>
          <div v-if="form.errors.role_id" class="mt-1 text-sm text-red-600">{{ form.errors.role_id }}</div>
        </div>

        <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
          <input id="is_locked" v-model="form.is_locked" type="checkbox" />
          <label for="is_locked" class="text-sm font-bold text-slate-700">قفل حساب الدخول مؤقتًا</label>
        </div>

        <div v-if="props.canManagePermissions" class="md:col-span-2 rounded-[28px] bg-white p-5 ring-1 ring-slate-200">
          <div class="mb-4">
            <h3 class="text-lg font-black text-slate-800">الصلاحيات الخاصة</h3>
            <p class="mt-1 text-sm font-bold text-slate-500">
              يمكنك إضافة صلاحيات خاصة لهذا المستخدم أو منعه من صلاحيات موجودة في دوره.
            </p>
          </div>

          <div class="grid gap-4 lg:grid-cols-2">
            <div
              v-for="group in props.permissionsGroups"
              :key="group.title"
              class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100"
            >
              <div class="mb-3 text-sm font-black text-slate-700">{{ group.title }}</div>

              <div class="space-y-3">
                <div
                  v-for="item in group.items"
                  :key="item.key"
                  class="rounded-xl bg-white p-3 ring-1 ring-slate-100"
                >
                  <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="text-sm font-bold text-slate-700">{{ item.label }}</span>
                    <span
                      v-if="hasRolePermission(item.key)"
                      class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-black text-emerald-700"
                    >
                      من الدور
                    </span>
                  </div>

                  <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-xs font-bold text-cyan-700">
                      <input
                        type="checkbox"
                        :checked="form.extra_permissions.includes(item.key)"
                        @change="togglePermission('extra_permissions', item.key)"
                      />
                      إضافة خاصة
                    </label>

                    <label class="flex items-center gap-2 text-xs font-bold text-rose-700">
                      <input
                        type="checkbox"
                        :checked="form.denied_permissions.includes(item.key)"
                        @change="togglePermission('denied_permissions', item.key)"
                      />
                      منع الصلاحية
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="form.errors.extra_permissions" class="mt-2 text-sm text-red-600">{{ form.errors.extra_permissions }}</div>
          <div v-if="form.errors.denied_permissions" class="mt-2 text-sm text-red-600">{{ form.errors.denied_permissions }}</div>
        </div>
      </template>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <Link href="/employees">
          <BaseButton label="إلغاء" color="light" />
        </Link>

        <BaseButton
          label="حفظ التعديل"
          color="primary"
          type="submit"
          :disabled="form.processing"
        />
      </div>
    </template>
  </EntityFormShell>
</template>