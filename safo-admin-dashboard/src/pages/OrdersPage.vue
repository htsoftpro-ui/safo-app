<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/order'

const store = useOrderStore()
const router = useRouter()
const statusFilter = ref('')
const search = ref('')

const statusOptions = [
  { value: '', label: 'الكل' },
  { value: 'pending', label: 'قيد المراجعة' },
  { value: 'confirmed', label: 'مؤكد' },
  { value: 'processing', label: 'قيد التجهيز' },
  { value: 'ready', label: 'جاهز' },
  { value: 'shipped', label: 'تم الشحن' },
  { value: 'delivered', label: 'تم التوصيل' },
  { value: 'cancelled', label: 'ملغي' },
  { value: 'returned', label: 'مرتجع' },
]

const statusColors: Record<string, string> = {
  pending: 'badge-pending', confirmed: 'badge-confirmed', processing: 'badge-processing',
  ready: 'badge-ready', shipped: 'badge-shipped', delivered: 'badge-delivered',
  cancelled: 'badge-cancelled', returned: 'badge-returned',
}

onMounted(() => store.fetchOrders())

function applyFilters() {
  const params: Record<string, any> = {}
  if (statusFilter.value) params.status = statusFilter.value
  if (search.value) params.search = search.value
  store.fetchOrders(params)
}

function formatCurrency(amount: number) { return new Intl.NumberFormat('ar-YE').format(amount) + ' ﷼' }
function formatDate(date: string) { return new Date(date).toLocaleDateString('ar-YE', { year: 'numeric', month: 'short', day: 'numeric' }) }
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold">الطلبات</h2>
      <span class="text-sm text-gray-500">{{ store.total }} طلب</span>
    </div>

    <div class="card mb-6">
      <div class="flex flex-wrap gap-4">
        <input v-model="search" type="text" class="input-field flex-1 min-w-[200px]" placeholder="بحث برقم الطلب..." @keyup.enter="applyFilters" />
        <select v-model="statusFilter" class="input-field w-auto" @change="applyFilters">
          <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <button @click="applyFilters" class="btn-primary">بحث</button>
      </div>
    </div>

    <div class="card">
      <div v-if="store.loading" class="text-center py-8 text-gray-500">جاري التحميل...</div>
      <div v-else-if="store.orders.length === 0" class="text-center py-8 text-gray-500">لا توجد طلبات</div>
      <div v-else class="space-y-3">
        <div v-for="order in store.orders" :key="order.id"
             class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors"
             @click="router.push(`/orders/${order.id}`)">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
              <p class="font-bold">{{ order.order_number }}</p>
              <p class="text-sm text-gray-500">{{ order.user?.name || order.customer_name }} • {{ order.supplier?.company_name || order.supplier_name || '-' }}</p>
            </div>
            <div class="text-left">
              <span :class="['badge', statusColors[order.status]]">{{ order.status_label || order.status }}</span>
              <p class="text-lg font-bold mt-1">{{ formatCurrency(order.total_amount) }}</p>
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-2">{{ formatDate(order.created_at) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
