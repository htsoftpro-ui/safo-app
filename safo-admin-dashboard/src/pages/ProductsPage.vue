<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useProductStore } from '@/stores/product'

const store = useProductStore()
const search = ref('')
const statusFilter = ref('')

onMounted(() => store.fetchProducts())

function applyFilters() {
  const params: Record<string, any> = {}
  if (search.value) params.search = search.value
  if (statusFilter.value !== '') params.is_active = statusFilter.value === 'active'
  store.fetchProducts(params)
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('ar-YE').format(amount) + ' ﷼'
}

async function handleToggleActive(id: number) { await store.toggleActive(id) }
async function handleToggleFeatured(id: number) { await store.toggleFeatured(id) }
async function handleDelete(id: number) {
  if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) return
  const result = await store.deleteProduct(id)
  if (!result.success) alert(result.message || 'فشل الحذف')
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold">المنتجات</h2>
      <span class="text-sm text-gray-500">{{ store.total }} منتج</span>
    </div>

    <div class="card mb-6">
      <div class="flex flex-wrap gap-4">
        <input v-model="search" type="text" class="input-field flex-1 min-w-[200px]" placeholder="بحث عن منتج..." @keyup.enter="applyFilters" />
        <select v-model="statusFilter" class="input-field w-auto" @change="applyFilters">
          <option value="">الكل</option>
          <option value="active">نشط</option>
          <option value="inactive">غير نشط</option>
        </select>
        <button @click="applyFilters" class="btn-primary">بحث</button>
      </div>
    </div>

    <div class="card">
      <div v-if="store.loading" class="text-center py-8 text-gray-500">جاري التحميل...</div>
      <div v-else-if="store.products.length === 0" class="text-center py-8 text-gray-500">لا توجد منتجات</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-right text-gray-500 border-b">
              <th class="py-2">المنتج</th>
              <th class="py-2">السعر</th>
              <th class="py-2">المخزون</th>
              <th class="py-2">المبيعات</th>
              <th class="py-2">الحالة</th>
              <th class="py-2">مميز</th>
              <th class="py-2">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in store.products" :key="product.id" class="border-b hover:bg-gray-50">
              <td class="py-3">
                <div>
                  <p class="font-medium">{{ product.name }}</p>
                  <p class="text-xs text-gray-400">{{ product.category?.name || '-' }}</p>
                </div>
              </td>
              <td class="py-3 font-medium">{{ formatCurrency(product.price) }}</td>
              <td class="py-3">
                <span :class="product.stock_quantity <= 10 ? 'text-red-600 font-bold' : ''">{{ product.stock_quantity }}</span>
              </td>
              <td class="py-3">{{ product.sales_count }}</td>
              <td class="py-3">
                <button @click="handleToggleActive(product.id)" :class="product.is_active ? 'text-green-600' : 'text-red-600'" class="hover:underline">
                  {{ product.is_active ? 'نشط' : 'معطل' }}
                </button>
              </td>
              <td class="py-3">
                <button @click="handleToggleFeatured(product.id)" class="hover:underline" :class="product.is_featured ? 'text-yellow-500' : 'text-gray-400'">
                  {{ product.is_featured ? '⭐' : '☆' }}
                </button>
              </td>
              <td class="py-3">
                <button @click="handleDelete(product.id)" class="text-red-600 hover:underline text-sm">حذف</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
