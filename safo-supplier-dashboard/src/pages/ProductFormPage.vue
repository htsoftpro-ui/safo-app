<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useProductStore } from '@/stores/product'

const store = useProductStore()
const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const loading = ref(false)
const error = ref('')

const form = ref({
  name: '',
  name_en: '',
  description: '',
  category_id: '',
  price: '',
  compare_price: '',
  cost_price: '',
  unit: 'piece',
  unit_quantity: '1',
  min_order_quantity: '1',
  stock_quantity: '0',
  low_stock_threshold: '10',
  is_active: true,
  is_featured: false,
})

onMounted(async () => {
  await store.fetchCategories()
  if (isEdit.value) {
    // Load product data for editing
    const { productApi } = await import('@/api')
    try {
      const { data } = await productApi.list({ search: route.params.id })
      // In real app, use a show endpoint
    } catch { /* ignore */ }
  }
})

async function handleSubmit() {
  loading.value = true
  error.value = ''

  const payload = {
    ...form.value,
    category_id: Number(form.value.category_id),
    price: Number(form.value.price),
    compare_price: form.value.compare_price ? Number(form.value.compare_price) : null,
    cost_price: form.value.cost_price ? Number(form.value.cost_price) : null,
    unit_quantity: Number(form.value.unit_quantity),
    min_order_quantity: Number(form.value.min_order_quantity),
    stock_quantity: Number(form.value.stock_quantity),
    low_stock_threshold: Number(form.value.low_stock_threshold),
  }

  try {
    let result
    if (isEdit.value) {
      result = await store.updateProduct(Number(route.params.id), payload)
    } else {
      result = await store.createProduct(payload)
    }
    if (result.success) {
      router.push('/products')
    } else {
      error.value = result.message || 'فشل الحفظ'
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'خطأ في الخادم'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <button @click="router.back()" class="text-gray-600 hover:text-gray-900">← رجوع</button>
      <h2 class="text-2xl font-bold">{{ isEdit ? 'تعديل المنتج' : 'منتج جديد' }}</h2>
    </div>

    <div v-if="error" class="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm">{{ error }}</div>

    <form @submit.prevent="handleSubmit" class="card max-w-2xl space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label">اسم المنتج *</label>
          <input v-model="form.name" type="text" class="input-field" required />
        </div>
        <div>
          <label class="label">الاسم بالإنجليزية</label>
          <input v-model="form.name_en" type="text" class="input-field" />
        </div>
      </div>

      <div>
        <label class="label">الوصف</label>
        <textarea v-model="form.description" class="input-field" rows="3"></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="label">الفئة *</label>
          <select v-model="form.category_id" class="input-field" required>
            <option value="">اختر الفئة</option>
            <option v-for="cat in store.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div>
          <label class="label">الوحدة *</label>
          <select v-model="form.unit" class="input-field" required>
            <option value="piece">قطعة</option>
            <option value="kg">كيلو</option>
            <option value="box">كرتون</option>
            <option value="pack">علبة</option>
            <option value="liter">لتر</option>
          </select>
        </div>
        <div>
          <label class="label">الحد الأدنى للطلب</label>
          <input v-model="form.min_order_quantity" type="number" min="1" class="input-field" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="label">السعر *</label>
          <input v-model="form.price" type="number" min="0" step="0.01" class="input-field" required />
        </div>
        <div>
          <label class="label">السعر قبل الخصم</label>
          <input v-model="form.compare_price" type="number" min="0" step="0.01" class="input-field" />
        </div>
        <div>
          <label class="label">سعر التكلفة</label>
          <input v-model="form.cost_price" type="number" min="0" step="0.01" class="input-field" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="label">المخزون *</label>
          <input v-model="form.stock_quantity" type="number" min="0" class="input-field" required />
        </div>
        <div>
          <label class="label">حد المخزون المنخفض</label>
          <input v-model="form.low_stock_threshold" type="number" min="0" class="input-field" />
        </div>
      </div>

      <div class="flex gap-6">
        <label class="flex items-center gap-2">
          <input v-model="form.is_active" type="checkbox" class="rounded" />
          <span class="text-sm">نشط</span>
        </label>
        <label class="flex items-center gap-2">
          <input v-model="form.is_featured" type="checkbox" class="rounded" />
          <span class="text-sm">مميز</span>
        </label>
      </div>

      <div class="flex gap-4 pt-4">
        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'جاري الحفظ...' : (isEdit ? 'تحديث' : 'إنشاء') }}
        </button>
        <button type="button" @click="router.back()" class="btn-secondary">إلغاء</button>
      </div>
    </form>
  </div>
</template>
