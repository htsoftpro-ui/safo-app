<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useCategoryStore } from '@/stores/category'

const store = useCategoryStore()
const showForm = ref(false)
const editingId = ref<number | null>(null)
const form = ref({ name: '', name_en: '', icon: '', sort_order: '0', is_active: true })
const error = ref('')

onMounted(() => store.fetchCategories())

function openCreate() {
  editingId.value = null
  form.value = { name: '', name_en: '', icon: '', sort_order: '0', is_active: true }
  showForm.value = true
}

function openEdit(cat: any) {
  editingId.value = cat.id
  form.value = { name: cat.name, name_en: cat.name_en || '', icon: cat.icon || '', sort_order: String(cat.sort_order || 0), is_active: cat.is_active }
  showForm.value = true
}

async function handleSubmit() {
  error.value = ''
  const payload = { ...form.value, sort_order: Number(form.value.sort_order) }
  let result
  if (editingId.value) {
    result = await store.updateCategory(editingId.value, payload)
  } else {
    result = await store.createCategory(payload)
  }
  if (result.success) {
    showForm.value = false
    store.fetchCategories()
  } else {
    error.value = result.message || 'فشلت العملية'
  }
}

async function handleDelete(id: number) {
  if (!confirm('هل أنت متأكد من حذف هذه الفئة؟')) return
  const result = await store.deleteCategory(id)
  if (!result.success) alert(result.message || 'فشل الحذف')
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold">الفئات</h2>
      <button @click="openCreate" class="btn-primary">+ فئة جديدة</button>
    </div>

    <!-- Form Modal -->
    <div v-if="showForm" class="card mb-6">
      <h3 class="font-bold mb-4">{{ editingId ? 'تعديل الفئة' : 'فئة جديدة' }}</h3>
      <div v-if="error" class="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm">{{ error }}</div>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="label">الاسم *</label><input v-model="form.name" type="text" class="input-field" required /></div>
          <div><label class="label">الاسم بالإنجليزية</label><input v-model="form.name_en" type="text" class="input-field" /></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="label">الأيقونة</label><input v-model="form.icon" type="text" class="input-field" placeholder="📦" /></div>
          <div><label class="label">الترتيب</label><input v-model="form.sort_order" type="number" class="input-field" /></div>
        </div>
        <label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" class="rounded" /><span class="text-sm">نشط</span></label>
        <div class="flex gap-4">
          <button type="submit" class="btn-primary">{{ editingId ? 'تحديث' : 'إنشاء' }}</button>
          <button type="button" @click="showForm = false" class="btn-secondary">إلغاء</button>
        </div>
      </form>
    </div>

    <!-- Categories Table -->
    <div class="card">
      <div v-if="store.loading" class="text-center py-8 text-gray-500">جاري التحميل...</div>
      <div v-else-if="store.categories.length === 0" class="text-center py-8 text-gray-500">لا توجد فئات</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-right text-gray-500 border-b">
              <th class="py-2">الأيقونة</th>
              <th class="py-2">الاسم</th>
              <th class="py-2">المنتجات</th>
              <th class="py-2">الحالة</th>
              <th class="py-2">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cat in store.categories" :key="cat.id" class="border-b hover:bg-gray-50">
              <td class="py-3 text-xl">{{ cat.icon || '📂' }}</td>
              <td class="py-3">
                <p class="font-medium">{{ cat.name }}</p>
                <p class="text-xs text-gray-400">{{ cat.name_en || '-' }}</p>
              </td>
              <td class="py-3">{{ cat.products_count || 0 }}</td>
              <td class="py-3">
                <span :class="cat.is_active ? 'badge badge-delivered' : 'badge badge-cancelled'">{{ cat.is_active ? 'نشط' : 'معطل' }}</span>
              </td>
              <td class="py-3">
                <div class="flex gap-2">
                  <button @click="openEdit(cat)" class="text-blue-600 hover:underline text-sm">تعديل</button>
                  <button @click="handleDelete(cat.id)" class="text-red-600 hover:underline text-sm">حذف</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
