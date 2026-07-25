<script setup lang="ts">
import { onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useRouter } from 'vue-router'

const dashboard = useDashboardStore()
const router = useRouter()

onMounted(() => dashboard.fetchStats())

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('ar-YE', { style: 'decimal' }).format(amount) + ' ﷼'
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('ar-YE', { month: 'short', day: 'numeric' })
}

const statusColors: Record<string, string> = {
  pending: 'badge-pending',
  confirmed: 'badge-confirmed',
  processing: 'badge-processing',
  ready: 'badge-ready',
  shipped: 'badge-shipped',
  delivered: 'badge-delivered',
  cancelled: 'badge-cancelled',
}
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold mb-6">لوحة التحكم</h2>

    <div v-if="dashboard.loading" class="text-center py-12 text-gray-500">جاري التحميل...</div>

    <template v-else-if="dashboard.stats">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="card">
          <p class="text-sm text-gray-500">إجمالي الطلبات</p>
          <p class="text-3xl font-bold text-blue-600">{{ dashboard.stats.orders.total }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ dashboard.stats.orders.today }} اليوم</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500">الإيرادات</p>
          <p class="text-3xl font-bold text-green-600">{{ formatCurrency(dashboard.stats.revenue.total) }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ formatCurrency(dashboard.stats.revenue.this_month) }} هذا الشهر</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500">المنتجات</p>
          <p class="text-3xl font-bold text-purple-600">{{ dashboard.stats.products.active }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ dashboard.stats.products.low_stock }} مخزون منخفض</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500">قيد المراجعة</p>
          <p class="text-3xl font-bold text-yellow-600">{{ dashboard.stats.orders.pending }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ dashboard.stats.orders.confirmed }} مؤكد</p>
        </div>
      </div>

      <!-- Order Status Breakdown -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="card">
          <h3 class="font-bold mb-4">حالات الطلبات</h3>
          <div class="space-y-3">
            <div v-for="(count, status) in dashboard.stats.orders" :key="status"
                 class="flex items-center justify-between" v-show="!['total','today'].includes(String(status))">
              <span :class="['badge', statusColors[String(status)] || '']">{{ status }}</span>
              <span class="font-bold">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Top Products -->
        <div class="card">
          <h3 class="font-bold mb-4">المنتجات الأكثر مبيعاً</h3>
          <div class="space-y-3">
            <div v-for="product in dashboard.stats.top_products" :key="product.id"
                 class="flex items-center justify-between">
              <div>
                <p class="font-medium text-sm">{{ product.name }}</p>
                <p class="text-xs text-gray-400">{{ formatCurrency(product.price) }}</p>
              </div>
              <span class="text-sm font-bold text-green-600">{{ product.sales_count }} مبيعة</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold">آخر الطلبات</h3>
          <router-link to="/orders" class="text-sm text-blue-600 hover:underline">عرض الكل ←</router-link>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-right text-gray-500 border-b">
                <th class="py-2">رقم الطلب</th>
                <th class="py-2">العميل</th>
                <th class="py-2">الحالة</th>
                <th class="py-2">المبلغ</th>
                <th class="py-2">التاريخ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in dashboard.stats.recent_orders" :key="order.id"
                  class="border-b hover:bg-gray-50 cursor-pointer"
                  @click="router.push(`/orders/${order.id}`)">
                <td class="py-3 font-medium">{{ order.order_number }}</td>
                <td class="py-3">{{ order.customer_name }}</td>
                <td class="py-3"><span :class="['badge', statusColors[order.status]]">{{ order.status }}</span></td>
                <td class="py-3">{{ formatCurrency(order.total_amount) }}</td>
                <td class="py-3 text-gray-400">{{ formatDate(order.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
