<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrderStore } from '@/stores/order'

const store = useOrderStore()
const route = useRoute()
const router = useRouter()
const orderId = computed(() => Number(route.params.id))
const cancelReason = ref('')
const showCancel = ref(false)
const newStatus = ref('')
const statusNote = ref('')
const actionLoading = ref(false)

onMounted(() => store.fetchOrder(orderId.value))
const order = computed(() => store.currentOrder)

const statusColors: Record<string, string> = {
  pending: 'badge-pending', confirmed: 'badge-confirmed', processing: 'badge-processing',
  ready: 'badge-ready', shipped: 'badge-shipped', delivered: 'badge-delivered',
  cancelled: 'badge-cancelled', returned: 'badge-returned',
}

function formatCurrency(amount: number) { return new Intl.NumberFormat('ar-YE').format(amount) + ' ﷼' }
function formatDate(date: string) { return new Date(date).toLocaleDateString('ar-YE', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }

async function handleCancel() {
  actionLoading.value = true
  try {
    const result = await store.cancelOrder(orderId.value, cancelReason.value)
    if (result.success) { showCancel.value = false; cancelReason.value = '' }
    else alert(result.message || 'فشلت العملية')
  } finally { actionLoading.value = false }
}

async function handleStatusUpdate() {
  if (!newStatus.value) return
  actionLoading.value = true
  try {
    const result = await store.updateStatus(orderId.value, newStatus.value, statusNote.value || undefined)
    if (result.success) { newStatus.value = ''; statusNote.value = '' }
    else alert(result.message || 'فشلت العملية')
  } finally { actionLoading.value = false }
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
      <div class="card mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h3 class="text-xl font-bold">{{ order.order_number }}</h3>
            <p class="text-gray-500">{{ formatDate(order.created_at) }}</p>
          </div>
          <span :class="['badge text-base px-4 py-1', statusColors[order.status]]">{{ order.status_label || order.status }}</span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <!-- Items -->
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
              <div class="flex justify-between"><span class="text-gray-500">المجموع</span><span>{{ formatCurrency(order.subtotal || order.total_amount) }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">التوصيل</span><span>{{ formatCurrency(order.delivery_fee || 0) }}</span></div>
              <div class="flex justify-between font-bold text-lg border-t pt-2"><span>الإجمالي</span><span>{{ formatCurrency(order.total_amount) }}</span></div>
            </div>
          </div>

          <!-- Status History -->
          <div v-if="order.status_history?.length" class="card">
            <h4 class="font-bold mb-4">سجل الحالات</h4>
            <div class="space-y-3">
              <div v-for="(entry, idx) in order.status_history" :key="idx" class="flex gap-3">
                <div class="w-2 h-2 rounded-full mt-2" :class="idx === 0 ? 'bg-blue-500' : 'bg-gray-300'"></div>
                <div>
                  <p class="text-sm font-medium">{{ entry.from_status ? `${entry.from_status} → ` : '' }}{{ entry.to_status }}</p>
                  <p v-if="entry.note" class="text-xs text-gray-400">{{ entry.note }}</p>
                  <p class="text-xs text-gray-400">{{ formatDate(entry.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <!-- Customer -->
          <div class="card mb-6">
            <h4 class="font-bold mb-3">العميل</h4>
            <p class="font-medium">{{ order.user?.name || '-' }}</p>
            <p class="text-sm text-gray-500">{{ order.user?.phone || '-' }}</p>
          </div>

          <!-- Delivery -->
          <div class="card mb-6">
            <h4 class="font-bold mb-3">عنوان التوصيل</h4>
            <p class="text-sm">{{ order.delivery_address || '-' }}</p>
          </div>

          <!-- Payment -->
          <div class="card mb-6">
            <h4 class="font-bold mb-3">الدفع</h4>
            <p class="text-sm">{{ order.payment_method === 'cash' ? 'نقدي عند التوصيل' : order.payment_method }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ order.payment_status === 'paid' ? 'مدفوع' : 'في الانتظار' }}</p>
          </div>

          <!-- Admin Actions -->
          <div class="card">
            <h4 class="font-bold mb-3">إجراءات الإدارة</h4>

            <!-- Update Status -->
            <div class="mb-4">
              <label class="label">تغيير الحالة</label>
              <select v-model="newStatus" class="input-field text-sm">
                <option value="">اختر الحالة</option>
                <option value="confirmed">تأكيد</option>
                <option value="processing">بدء التجهيز</option>
                <option value="ready">جاهز للشحن</option>
                <option value="shipped">شحن</option>
                <option value="delivered">توصيل</option>
                <option value="returned">إرجاع</option>
              </select>
              <input v-model="statusNote" type="text" class="input-field text-sm mt-2" placeholder="ملاحظة (اختياري)" />
              <button @click="handleStatusUpdate" class="btn-primary w-full mt-2" :disabled="!newStatus || actionLoading">
                {{ actionLoading ? 'جاري...' : 'تحديث الحالة' }}
              </button>
            </div>

            <!-- Cancel -->
            <div v-if="order.status !== 'cancelled' && order.status !== 'delivered' && order.status !== 'returned'">
              <button @click="showCancel = !showCancel" class="btn-danger w-full">إلغاء الطلب</button>
              <div v-if="showCancel" class="mt-3 p-3 bg-red-50 rounded-lg">
                <textarea v-model="cancelReason" class="input-field text-sm" rows="2" placeholder="سبب الإلغاء..." required></textarea>
                <button @click="handleCancel" class="btn-danger w-full mt-2" :disabled="!cancelReason || actionLoading">تأكيد الإلغاء</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
