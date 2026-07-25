<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useOrderStore } from '@/stores/order'
import type { OrderStatus } from '@/types'

const store = useOrderStore()
const router = useRouter()
const route = useRoute()
const rejectReason = ref('')
const showReject = ref(false)
const shipNotes = ref('')
const actionLoading = ref(false)

const orderId = computed(() => Number(route.params.id))

onMounted(() => store.fetchOrder(orderId.value))

const order = computed(() => store.currentOrder)

const statusColors: Record<string, string> = {
  pending: 'badge-pending',
  confirmed: 'badge-confirmed',
  processing: 'badge-processing',
  ready: 'badge-ready',
  shipped: 'badge-shipped',
  delivered: 'badge-delivered',
  cancelled: 'badge-cancelled',
  returned: 'badge-returned',
}

const availableActions = computed(() => {
  if (!order.value) return []
  const s = order.value.status
  const actions: { key: string; label: string; color: string }[] = []
  if (s === 'pending') {
    actions.push({ key: 'accept', label: 'قبول', color: 'btn-primary' })
    actions.push({ key: 'reject', label: 'رفض', color: 'btn-danger' })
  }
  if (s === 'confirmed') actions.push({ key: 'process', label: 'بدء التجهيز', color: 'btn-primary' })
  if (s === 'processing') actions.push({ key: 'ready', label: 'جاهز للشحن', color: 'btn-primary' })
  if (s === 'ready') actions.push({ key: 'ship', label: 'شحن', color: 'btn-primary' })
  return actions
})

function formatCurrency(amount: number) {
  return new Intl.NumberFormat('ar-YE').format(amount) + ' ﷼'
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('ar-YE', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function handleAction(action: string) {
  actionLoading.value = true
  try {
    let result
    switch (action) {
      case 'accept': result = await store.acceptOrder(orderId.value); break
      case 'reject': result = await store.rejectOrder(orderId.value, rejectReason.value); showReject.value = false; break
      case 'process': result = await store.processOrder(orderId.value); break
      case 'ready': result = await store.readyOrder(orderId.value); break
      case 'ship': result = await store.shipOrder(orderId.value, shipNotes.value); break
    }
    if (result && !result.success) {
      alert('فشلت العملية')
    }
  } finally {
    actionLoading.value = false
  }
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <button @click="router.back()" class="text-gray-600 hover:text-gray-900">← رجوع</button>
      <h2 class="text-2xl font-bold">تفاصيل الطلب</h2>
    </div>

    <div v-if="!order" class="text-center py-12 text-gray-500">جاري التحميل...</div>

    <template v-else>
      <!-- Header -->
      <div class="card mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h3 class="text-xl font-bold">{{ order.order_number }}</h3>
            <p class="text-gray-500">{{ formatDate(order.created_at) }}</p>
          </div>
          <span :class="['badge text-base px-4 py-1', statusColors[order.status]]">{{ order.status_label }}</span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Items -->
        <div class="lg:col-span-2">
          <div class="card mb-6">
            <h4 class="font-bold mb-4">المنتجات</h4>
            <div class="space-y-3">
              <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between border-b pb-3">
                <div>
                  <p class="font-medium">{{ item.product_name }}</p>
                  <p class="text-sm text-gray-400">{{ item.quantity }} × {{ formatCurrency(item.unit_price) }}</p>
                </div>
                <p class="font-bold">{{ formatCurrency(item.total_price) }}</p>
              </div>
            </div>

            <div class="mt-4 space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-gray-500">المجموع الفرعي</span><span>{{ formatCurrency(order.subtotal) }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">رسوم التوصيل</span><span>{{ formatCurrency(order.delivery_fee) }}</span></div>
              <div v-if="order.discount_amount > 0" class="flex justify-between"><span class="text-gray-500">الخصم</span><span class="text-red-600">-{{ formatCurrency(order.discount_amount) }}</span></div>
              <div class="flex justify-between font-bold text-lg border-t pt-2"><span>الإجمالي</span><span>{{ formatCurrency(order.total_amount) }}</span></div>
            </div>
          </div>

          <!-- Status History -->
          <div class="card">
            <h4 class="font-bold mb-4">سجل الحالات</h4>
            <div class="space-y-3">
              <div v-for="(entry, idx) in order.status_history" :key="idx" class="flex gap-3">
                <div class="w-2 h-2 rounded-full mt-2" :class="idx === 0 ? 'bg-blue-500' : 'bg-gray-300'"></div>
                <div>
                  <p class="text-sm font-medium">
                    {{ entry.from_status ? `${entry.from_status} → ` : '' }}{{ entry.to_status }}
                  </p>
                  <p v-if="entry.note" class="text-xs text-gray-400">{{ entry.note }}</p>
                  <p class="text-xs text-gray-400">{{ formatDate(entry.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div>
          <!-- Customer -->
          <div class="card mb-6">
            <h4 class="font-bold mb-3">العميل</h4>
            <p class="font-medium">{{ order.user?.name }}</p>
            <p class="text-sm text-gray-500">{{ order.user?.phone }}</p>
          </div>

          <!-- Delivery -->
          <div class="card mb-6">
            <h4 class="font-bold mb-3">عنوان التوصيل</h4>
            <p class="text-sm">{{ order.delivery_address }}</p>
            <p v-if="order.delivery_notes" class="text-sm text-gray-400 mt-2">{{ order.delivery_notes }}</p>
          </div>

          <!-- Payment -->
          <div class="card mb-6">
            <h4 class="font-bold mb-3">الدفع</h4>
            <p class="text-sm">{{ order.payment_method === 'cash' ? 'نقدي عند التوصيل' : order.payment_method }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ order.payment_status === 'paid' ? 'مدفوع' : 'في الانتظار' }}</p>
          </div>

          <!-- Actions -->
          <div v-if="availableActions.length > 0" class="card">
            <h4 class="font-bold mb-3">إجراءات</h4>
            <div class="space-y-2">
              <button v-for="action in availableActions" :key="action.key"
                      @click="action.key === 'reject' ? (showReject = true) : action.key === 'ship' ? null : handleAction(action.key)"
                      :class="[action.color, 'w-full']"
                      :disabled="actionLoading">
                {{ actionLoading ? 'جاري...' : action.label }}
              </button>
            </div>

            <!-- Ship with notes -->
            <div v-if="availableActions.some(a => a.key === 'ship')" class="mt-3">
              <input v-model="shipNotes" type="text" class="input-field text-sm" placeholder="ملاحظات الشحن (اختياري)" />
              <button @click="handleAction('ship')" class="btn-primary w-full mt-2" :disabled="actionLoading">
                {{ actionLoading ? 'جاري...' : 'شحن الطلب' }}
              </button>
            </div>

            <!-- Reject modal -->
            <div v-if="showReject" class="mt-3 p-3 bg-red-50 rounded-lg">
              <p class="text-sm font-medium text-red-700 mb-2">سبب الرفض *</p>
              <textarea v-model="rejectReason" class="input-field text-sm" rows="2" placeholder="اكتب سبب الرفض..." required></textarea>
              <div class="flex gap-2 mt-2">
                <button @click="handleAction('reject')" class="btn-danger flex-1" :disabled="!rejectReason || actionLoading">رفض الطلب</button>
                <button @click="showReject = false" class="btn-secondary flex-1">إلغاء</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
