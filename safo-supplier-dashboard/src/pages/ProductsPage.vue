<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProductStore } from '@/stores/product'

const store = useProductStore()
const router = useRouter()
const search = ref('')
const statusFilter = ref('')

onMounted(() => {
  store.fetchProducts()
  store.fetchCategories()
})

function applyFilters() {
  const params: Record<string, any> = {}
  if (search.value) params.search = search.value
  if (statusFilter.value) params.status = statusFilter.value
  store.fetchProducts(params)
}

async function handleDelete(id: number) {
  if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) return
  const result = await store.deleteProduct(id)
  if (!result.success) alert(result.message || 'فشل الحذف')
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('ar-YE').format(amount) + ' ﷼'
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold">المنتجات</h2>
      <router-link to="/products/create" class="btn-primary">+ منتج جديد</router-link>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="flex flex-wrap gap-4">
        <input v-model="search" type="text" class="input-field flex-1 min-w-[200px]" placeholder="بحث عن منتج..."
               @keyup.enter="applyFilters" />
        <select v-model="statusFilter" class="input-field w-auto" @change="applyFilters">
          <option value="">الكل</option>
          <option value="active">نشط</option>
          <option value="inactive">غير نشط</option>
        </select>
        <button @click="applyFilters" class="btn-primary">بحث</button>
      </div>
    </div>

    <!-- Products Table -->
    <div class="card">
      <div v-if="store.loading" class="text-center py-8 text-gray-500">جاري التحميل...</div>

      <div v-else-if="store.products.length === 0" class="text-center py-8 text-gray-500">
        لا توجد منتجات. <router-link to="/products/create" class="text-blue-600">أضف منتجك الأول</router-link>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-right text-gray-500 border-b">
              <th class="py-2">المنتج</th>
              <th class="py-2">السعر</th>
              <th class="py-2">المخزون</th>
              <th class="py-2">المبيعات</th>
              <th class="py-2">الحالة</th>
              <th class="py-2">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in store.products" :key="product.id" class="border-b hover:bg-gray-50">
              <td class="py-3">
                <div class="flex items-center gap-3">
                  <img v-if="product.thumbnail" :src="product.thumbnail" class="w-10 h-10 rounded object-cover" />
                  <div v-else class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center">📦</div>
                  <div>
                    <p class="font-medium">{{ product.name }}</p>
                    <p class="text-xs text-gray-400">{{ product.category?.name }}</p>
                  </div>
                </div>
              </td>
              <td class="py-3 font-medium">{{ formatCurrency(product.price) }}</td>
              <td class="py-3">
                <span :class="product.is_low_stock ? 'text-red-600 font-bold' : ''">
                  {{ product.stock_quantity }} {{ product.unit }}
                </span>
                <span v-if="product.is_low_stock" class="text-xs text-red-500 block">مخزون منخفض</span>
              </td>
              <td class="py-3">{{ product.sales_count }}</td>
              <td class="py-3">
                <span :class="product.is_active ? 'badge badge-delivered' : 'badge badge-cancelled'">
                  {{ product.is_active ? 'نشط' : 'غير نشط' }}
                </span>
              </td>
              <td class="py-3">
                <div class="flex gap-2">
                  <button @click="router.push(`/products/${product.id}/edit`)" class="text-blue-600 hover:underline text-sm">تعديل</button>
                  <button @click="handleDelete(product.id)" class="text-red-600 hover:underline text-sm">حذف</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
