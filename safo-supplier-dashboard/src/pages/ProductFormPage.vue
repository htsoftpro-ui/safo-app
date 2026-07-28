<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useProductStore } from '@/stores/product'
import { productApi } from '@/api'

const store = useProductStore()
const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const productId = computed(() => Number(route.params.id))
const loading = ref(false)
const error = ref('')

// ─── Image Upload State ────────────────────────
const existingImages = ref<string[]>([])
const pendingFiles = ref<{ file: File; preview: string }[]>([])
const uploadingImages = ref(false)
const uploadProgress = ref(0)
const dragActive = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

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
    try {
      const { data } = await productApi.list({ search: route.params.id })
      const product = data.data?.[0]
      if (product) {
        form.value = {
          name: product.name || '',
          name_en: product.name_en || '',
          description: product.description || '',
          category_id: String(product.category_id || ''),
          price: String(product.price || ''),
          compare_price: product.compare_price ? String(product.compare_price) : '',
          cost_price: product.cost_price ? String(product.cost_price) : '',
          unit: product.unit || 'piece',
          unit_quantity: String(product.unit_quantity || '1'),
          min_order_quantity: String(product.min_order_quantity || '1'),
          stock_quantity: String(product.stock_quantity || '0'),
          low_stock_threshold: String(product.low_stock_threshold || '10'),
          is_active: product.is_active ?? true,
          is_featured: product.is_featured ?? false,
        }
        existingImages.value = product.images || []
      }
    } catch { /* ignore */ }
  }
})

// ─── Image Handlers ──────────────────────────────

function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files?.length) return
  addFiles(Array.from(input.files))
  input.value = '' // reset so same file can be re-selected
}

function addFiles(files: File[]) {
  const valid = files.filter(f => {
    if (!f.type.startsWith('image/')) return false
    if (f.size > 5 * 1024 * 1024) { // 5MB limit
      error.value = `الملف ${f.name} أكبر من 5 ميجا`
      return false
    }
    return true
  })

  for (const file of valid) {
    const reader = new FileReader()
    reader.onload = (e) => {
      pendingFiles.value.push({
        file,
        preview: e.target?.result as string,
      })
    }
    reader.readAsDataURL(file)
  }
}

function removePending(index: number) {
  pendingFiles.value.splice(index, 1)
}

async function removeExisting(index: number) {
  const url = existingImages.value[index]
  if (!url) return

  if (isEdit.value && productId.value) {
    try {
      await productApi.deleteImage(productId.value, url)
    } catch { /* ignore */ }
  }
  existingImages.value.splice(index, 1)
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  dragActive.value = true
}

function onDragLeave() {
  dragActive.value = false
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  dragActive.value = false
  const files = Array.from(e.dataTransfer?.files || [])
  addFiles(files)
}

function triggerFileInput() {
  fileInput.value?.click()
}

// ─── Upload Images ───────────────────────────────

async function uploadAllImages(pid: number): Promise<boolean> {
  if (!pendingFiles.value.length) return true

  uploadingImages.value = true
  uploadProgress.value = 0
  const total = pendingFiles.value.length

  try {
    for (let i = 0; i < total; i++) {
      const { data } = await productApi.uploadImage(pid, pendingFiles.value[i].file)
      if (data.success && data.data?.images) {
        existingImages.value = data.data.images
      }
      uploadProgress.value = Math.round(((i + 1) / total) * 100)
    }
    pendingFiles.value = []
    return true
  } catch (err: any) {
    error.value = err.response?.data?.message || 'فشل رفع بعض الصور'
    return false
  } finally {
    uploadingImages.value = false
    uploadProgress.value = 0
  }
}

// ─── Form Submit ─────────────────────────────────

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
    let pid: number

    if (isEdit.value) {
      const result = await store.updateProduct(productId.value, payload)
      if (!result.success) {
        error.value = result.message || 'فشل التحديث'
        return
      }
      pid = productId.value
    } else {
      const result = await store.createProduct(payload)
      if (!result.success || !result.product) {
        error.value = result.message || 'فشل الإنشاء'
        return
      }
      pid = result.product.id
    }

    // Upload pending images
    if (pendingFiles.value.length > 0) {
      const uploaded = await uploadAllImages(pid)
      if (!uploaded) return
    }

    router.push('/products')
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

      <!-- ═══════════ IMAGE UPLOAD SECTION ═══════════ -->
      <div>
        <label class="label">صور المنتج</label>
        <p class="text-xs text-gray-500 mb-3">يمكنك رفع حتى 10 صور (حد أقصى 5 ميجا لكل صورة)</p>

        <!-- Existing Images -->
        <div v-if="existingImages.length" class="flex flex-wrap gap-3 mb-4">
          <div
            v-for="(img, i) in existingImages"
            :key="img"
            class="relative group w-24 h-24 rounded-lg overflow-hidden border-2 border-gray-200"
          >
            <img :src="img" class="w-full h-full object-cover" alt="صورة المنتج" />
            <div v-if="i === 0" class="absolute bottom-0 left-0 right-0 bg-blue-600 text-white text-xs text-center py-0.5">
              الرئيسية
            </div>
            <button
              type="button"
              @click="removeExisting(i)"
              class="absolute top-1 left-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
            >✕</button>
          </div>
        </div>

        <!-- Pending Images (previews) -->
        <div v-if="pendingFiles.length" class="flex flex-wrap gap-3 mb-4">
          <div
            v-for="(item, i) in pendingFiles"
            :key="i"
            class="relative group w-24 h-24 rounded-lg overflow-hidden border-2 border-blue-300 border-dashed"
          >
            <img :src="item.preview" class="w-full h-full object-cover" alt="معاينة" />
            <div class="absolute bottom-0 left-0 right-0 bg-blue-500 text-white text-xs text-center py-0.5">
              جديد
            </div>
            <button
              type="button"
              @click="removePending(i)"
              class="absolute top-1 left-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
            >✕</button>
          </div>
        </div>

        <!-- Upload Progress -->
        <div v-if="uploadingImages" class="mb-4">
          <div class="flex items-center gap-3 mb-2">
            <div class="animate-spin w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full"></div>
            <span class="text-sm text-blue-600">جاري رفع الصور... {{ uploadProgress }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all" :style="{ width: uploadProgress + '%' }"></div>
          </div>
        </div>

        <!-- Drop Zone -->
        <div
          @dragover="onDragOver"
          @dragleave="onDragLeave"
          @drop="onDrop"
          @click="triggerFileInput"
          :class="[
            'border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors',
            dragActive ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50'
          ]"
        >
          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            multiple
            class="hidden"
            @change="onFileSelect"
          />
          <div class="text-4xl mb-3">📷</div>
          <p class="text-gray-600 font-medium mb-1">اسحب الصور هنا أو اضغط للاختيار</p>
          <p class="text-xs text-gray-400">JPG, PNG, WebP — حد أقصى 5 ميجا</p>
        </div>
      </div>

      <!-- ═══════════ PRODUCT INFO ═══════════ -->
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
        <button type="submit" class="btn-primary" :disabled="loading || uploadingImages">
          {{ loading ? 'جاري الحفظ...' : (isEdit ? 'تحديث' : 'إنشاء') }}
        </button>
        <button type="button" @click="router.back()" class="btn-secondary">إلغاء</button>
      </div>
    </form>
  </div>
</template>
