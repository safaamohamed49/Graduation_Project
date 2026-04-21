<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import FormControl from '@/Components/FormControl.vue'
import BaseButton from '@/Components/BaseButton.vue'

const props = defineProps({
  categories: Object,
  filters: Object,
})

const page = usePage()

const search = ref(props.filters?.search ?? '')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const categoryToDelete = ref(null)

const createForm = useForm({
  name: '',
  code: '',
  description: '',
  is_active: true,
  notes: '',
})

const editForm = useForm({
  id: null,
  name: '',
  code: '',
  description: '',
  is_active: true,
  notes: '',
})

watch(search, (value) => {
  router.get(
    route('categories.index'),
    { search: value },
    {
      preserveState: true,
      replace: true,
      preserveScroll: true,
    }
  )
})

const categoriesData = computed(() => props.categories?.data ?? [])

const openCreateModal = () => {
  createForm.reset()
  createForm.clearErrors()
  createForm.is_active = true
  showCreateModal.value = true
}

const openEditModal = (category) => {
  editForm.clearErrors()
  editForm.id = category.id
  editForm.name = category.name
  editForm.code = category.code
  editForm.description = category.description ?? ''
  editForm.is_active = !!category.is_active
  editForm.notes = category.notes ?? ''
  showEditModal.value = true
}

const submitCreate = () => {
  createForm.post(route('categories.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    },
  })
}

const submitEdit = () => {
  editForm.put(route('categories.update', editForm.id), {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false
    },
  })
}

const deleteCategory = (category) => {
  if (!confirm(`هل أنت متأكدة من حذف الفئة "${category.name}"؟`)) return

  router.delete(route('categories.destroy', category.id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <MainLayout title="الفئات">
    <div class="space-y-6">
      <section class="rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 class="text-2xl font-black text-slate-800">إدارة الفئات</h1>
            <p class="mt-1 text-sm text-slate-500">
              إضافة وتعديل وتنظيم تصنيفات المنتجات
            </p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <div class="min-w-[260px]">
              <FormControl
                v-model="search"
                type="text"
                placeholder="ابحثي بالاسم أو الرمز"
              />
            </div>

            <BaseButton
              label="إضافة فئة"
              color="primary"
              @click="openCreateModal"
            />
          </div>
        </div>
      </section>

      <section>
        <CardBox>
          <CardBoxComponentHeader title="قائمة الفئات" />
          <div class="overflow-x-auto">
            <table class="min-w-full text-right">
              <thead class="bg-slate-50">
                <tr class="text-sm text-slate-600">
                  <th class="px-4 py-3 font-bold">#</th>
                  <th class="px-4 py-3 font-bold">اسم الفئة</th>
                  <th class="px-4 py-3 font-bold">الرمز</th>
                  <th class="px-4 py-3 font-bold">الوصف</th>
                  <th class="px-4 py-3 font-bold">عدد المنتجات</th>
                  <th class="px-4 py-3 font-bold">الحالة</th>
                  <th class="px-4 py-3 font-bold">الإجراءات</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(category, index) in categoriesData"
                  :key="category.id"
                  class="border-t text-sm text-slate-700"
                >
                  <td class="px-4 py-3">
                    {{ ((props.categories.current_page - 1) * props.categories.per_page) + index + 1 }}
                  </td>
                  <td class="px-4 py-3 font-semibold">{{ category.name }}</td>
                  <td class="px-4 py-3">{{ category.code }}</td>
                  <td class="px-4 py-3">{{ category.description || '-' }}</td>
                  <td class="px-4 py-3">{{ category.products_count }}</td>
                  <td class="px-4 py-3">
                    <span
                      class="rounded-full px-3 py-1 text-xs font-bold"
                      :class="category.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                    >
                      {{ category.is_active ? 'فعالة' : 'غير فعالة' }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-2">
                      <BaseButton
                        label="تعديل"
                        color="warning"
                        small
                        @click="openEditModal(category)"
                      />
                      <BaseButton
                        label="حذف"
                        color="danger"
                        small
                        @click="deleteCategory(category)"
                      />
                    </div>
                  </td>
                </tr>

                <tr v-if="!categoriesData.length">
                  <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                    لا توجد فئات مطابقة.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardBox>
      </section>

      <section v-if="props.categories.links?.length > 3" class="flex flex-wrap gap-2">
        <button
          v-for="link in props.categories.links"
          :key="link.label"
          class="rounded-xl border px-4 py-2 text-sm transition"
          :class="[
            link.active
              ? 'border-blue-600 bg-blue-600 text-white'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
            !link.url ? 'cursor-not-allowed opacity-50' : ''
          ]"
          :disabled="!link.url"
          @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })"
          v-html="link.label"
        />
      </section>
    </div>

    <!-- Create Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
      <div class="w-full max-w-2xl rounded-[28px] bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
          <h2 class="text-xl font-black text-slate-800">إضافة فئة</h2>
          <button class="text-xl text-slate-500" @click="showCreateModal = false">×</button>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">اسم الفئة</label>
            <FormControl v-model="createForm.name" type="text" />
            <div v-if="createForm.errors.name" class="mt-1 text-sm text-red-600">{{ createForm.errors.name }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">رمز الفئة</label>
            <FormControl v-model="createForm.code" type="text" />
            <div v-if="createForm.errors.code" class="mt-1 text-sm text-red-600">{{ createForm.errors.code }}</div>
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">الوصف</label>
            <FormControl v-model="createForm.description" type="textarea" />
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">ملاحظات</label>
            <FormControl v-model="createForm.notes" type="textarea" />
          </div>

          <div class="md:col-span-2 flex items-center gap-3">
            <input id="create_is_active" v-model="createForm.is_active" type="checkbox" />
            <label for="create_is_active" class="text-sm font-semibold text-slate-700">الفئة فعالة</label>
          </div>
        </div>

        <div class="flex justify-end gap-3 border-t px-6 py-4">
          <BaseButton label="إلغاء" color="light" @click="showCreateModal = false" />
          <BaseButton label="حفظ" color="primary" :disabled="createForm.processing" @click="submitCreate" />
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
    >
      <div class="w-full max-w-2xl rounded-[28px] bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
          <h2 class="text-xl font-black text-slate-800">تعديل فئة</h2>
          <button class="text-xl text-slate-500" @click="showEditModal = false">×</button>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">اسم الفئة</label>
            <FormControl v-model="editForm.name" type="text" />
            <div v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">رمز الفئة</label>
            <FormControl v-model="editForm.code" type="text" />
            <div v-if="editForm.errors.code" class="mt-1 text-sm text-red-600">{{ editForm.errors.code }}</div>
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">الوصف</label>
            <FormControl v-model="editForm.description" type="textarea" />
          </div>

          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">ملاحظات</label>
            <FormControl v-model="editForm.notes" type="textarea" />
          </div>

          <div class="md:col-span-2 flex items-center gap-3">
            <input id="edit_is_active" v-model="editForm.is_active" type="checkbox" />
            <label for="edit_is_active" class="text-sm font-semibold text-slate-700">الفئة فعالة</label>
          </div>
        </div>

        <div class="flex justify-end gap-3 border-t px-6 py-4">
          <BaseButton label="إلغاء" color="light" @click="showEditModal = false" />
          <BaseButton label="حفظ التعديل" color="warning" :disabled="editForm.processing" @click="submitEdit" />
        </div>
      </div>
    </div>
  </MainLayout>
</template>