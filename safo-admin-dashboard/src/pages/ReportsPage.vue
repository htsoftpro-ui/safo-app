<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { reportApi } from '@/api'

const salesData = ref<any>(null)
const financialData = ref<any>(null)
const userStats = ref<any>(null)
const loading = ref(false)
const period = ref('month')

async function fetchAll() {
  loading.value = true
  try {
    const [sales, financial, users] = await Promise.all([
      reportApi.sales({ period: period.value }),
      reportApi.financial(),
      reportApi.users(),
    ])
    salesData.value = sales.data.data
    financialData.value = financial.data.data
    userStats.value = users.data.data
  } catch { /* ignore */ }
  finally { loading.value = false }
}

onMounted(fetchAll)

function formatCurrency(amount: number) { return new Intl.NumberFormat('ar-YE').format(amount) + ' ﷼' }
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold">التقارير</h2>
      <select v-model="period" class="input-field w-auto" @change="fetchAll">
        <option value="today">اليوم</option>
        <option value="week">هذا الأسبوع</option>
        <option value="month">هذا الشهر</option>
        <option value="year">هذا العام</option>
      </select>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">جاري التحميل...</div>

    <template v-else>
      <!-- Financial Overview -->
      <div v-if="financialData" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="card">
          <p class="text-sm text-gray-500">إجمالي الإيرادات</p>
          <p class="text-3xl font-bold text-green-600">{{ formatCurrency(financialData.total_revenue) }}</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500">إيرادات الشهر</p>
          <p class="text-3xl font-bold text-blue-600">{{ formatCurrency(financialData.month_revenue) }}</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500">مدفوعات معلقة</p>
          <p class="text-3xl font-bold text-yellow-600">{{ formatCurrency(financialData.pending_payments) }}</p>
        </div>
      </div>

      <!-- Sales Stats -->
      <div v-if="salesData" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="card">
          <h3 class="font-bold mb-4">المبيعات ({{ period === 'today' ? 'اليوم' : period === 'week' ? 'الأسبوع' : period === 'month' ? 'الشهر' : 'العام' }})</h3>
          <div class="space-y-3">
            <div class="flex justify-between"><span class="text-gray-500">عدد الطلبات</span><span class="font-bold">{{ salesData.total_orders }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">إجمالي الإيرادات</span><span class="font-bold text-green-600">{{ formatCurrency(salesData.total_revenue) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">متوسط قيمة الطلب</span><span class="font-bold">{{ formatCurrency(salesData.avg_order_value) }}</span></div>
          </div>
        </div>

        <div class="card">
          <h3 class="font-bold mb-4">المستخدمون</h3>
          <div v-if="userStats" class="space-y-3">
            <div class="flex justify-between"><span class="text-gray-500">الإجمالي</span><span class="font-bold">{{ userStats.total }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">العملاء</span><span class="font-bold">{{ userStats.customers }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">الموردون</span><span class="font-bold">{{ userStats.suppliers }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">النشطون</span><span class="font-bold text-green-600">{{ userStats.active }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">جدد اليوم</span><span class="font-bold text-blue-600">{{ userStats.new_today }}</span></div>
          </div>
        </div>
      </div>

      <!-- Daily Breakdown -->
      <div v-if="salesData?.daily?.length" class="card">
        <h3 class="font-bold mb-4">تفصيل يومي</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-right text-gray-500 border-b">
                <th class="py-2">التاريخ</th>
                <th class="py-2">الطلبات</th>
                <th class="py-2">الإيرادات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="day in salesData.daily" :key="day.date" class="border-b hover:bg-gray-50">
                <td class="py-3">{{ day.date }}</td>
                <td class="py-3">{{ day.orders }}</td>
                <td class="py-3 font-medium">{{ formatCurrency(day.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Suppliers -->
      <div v-if="financialData?.top_suppliers?.length" class="card mt-6">
        <h3 class="font-bold mb-4">أفضل الموردين</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-right text-gray-500 border-b">
                <th class="py-2">المورد</th>
                <th class="py-2">الطلبات</th>
                <th class="py-2">الإيرادات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in financialData.top_suppliers" :key="s.id" class="border-b hover:bg-gray-50">
                <td class="py-3 font-medium">{{ s.company_name }}</td>
                <td class="py-3">{{ s.order_count }}</td>
                <td class="py-3 font-medium text-green-600">{{ formatCurrency(s.revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
