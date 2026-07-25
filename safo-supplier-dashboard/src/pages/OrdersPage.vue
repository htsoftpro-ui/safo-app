<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/order'
import type { OrderStatus } from '@/types'

const store = useOrderStore()
const router = useRouter()
const statusFilter = ref('')

const statusOptions: { value: string; label: string }[] = [
  { value: '', label: 'الكل' },
  { value: 'pending', label: 'قيد المراجعة' },
  { value: 'confirmed', label: 'مؤكد' },
  { value: 'processing', label: 'قيد التجهيز' },
  { value: 'ready', label: 'جاهز' },
  { value: 'shipped', label: 'تم الشحن' },
  { value: 'delivered', label: 'تم التوصيل' },
  { value: 'cancelled', label: 'ملغي' },
]

const statusColors: Record<string, string> = {
  pending: 'badge-pending',
  confirmed: 'badge-confirmed',
  processing: 'badge-processing',
  ready: 'badge-ready',
  shipped: 'badge-shipped',
  delivered: 'badge-delivered',
  cancelled: 'badge-cancelled',
}

onMounted(() => store.fetchOrders())

function applyFilter() {
  const params: Record<string, any> = {}
  if (statusFilter.value) params.status = statusFilter.value
  store.fetchOrders(params)
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('ar-YE').format(amount) + ' ﷼'
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('ar-YE', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold mb-6">الطلبات</h2>

    <!-- Filter -->
    <div class="card mb-6">
      <div class="flex flex-wrap gap-4 items-center">
        <select v-model="statusFilter" class="input-field w-auto" @change="applyFilter">
          <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <span class="text-sm text-gray-500">{{ store.total }} طلب</span>
      </div>
    </div>

    <!-- Orders -->
    <div class="card">
      <div v-if="store.loading" class="text-center py-8 text-gray-500">جاري التحميل...</div>

      <div v-else-if="store.orders.length === 0" class="text-center py-8 text-gray-500">
        لا توجد طلبات
      </div>

      <div v-else class="space-y-3">
        <div v-for="order in store.orders" :key="order.id"
             class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors"
             @click="router.push(`/orders/${order.id}`)">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
              <p class="font-bold">{{ order.order_number }}</p>
              <p class="text-sm text-gray-500">{{ order.user?.name }} • {{ order.items_count }} منتج</p>
            </div>
            <div class="text-left">
              <span :class="['badge', statusColors[order.status]]">{{ order.status_label }}</span>
              <p class="text-lg font-bold mt-1">{{ formatCurrency(order.total_amount) }}</p>
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-2">{{ formatDate(order.created_at) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
