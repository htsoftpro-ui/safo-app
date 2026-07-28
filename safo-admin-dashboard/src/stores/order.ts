import { defineStore } from 'pinia'
import { ref } from 'vue'
import { orderApi } from '@/api'
import type { Order } from '@/types'

export const useOrderStore = defineStore('order', () => {
  const orders = ref<Order[]>([])
  const currentOrder = ref<Order | null>(null)
  const loading = ref(false)
  const currentPage = ref(1)
  const lastPage = ref(1)
  const total = ref(0)

  async function fetchOrders(params: Record<string, any> = {}) {
    loading.value = true
    try {
      const { data } = await orderApi.list({ per_page: 20, ...params })
      orders.value = data.data
      currentPage.value = data.meta?.current_page || 1
      lastPage.value = data.meta?.last_page || 1
      total.value = data.meta?.total || data.data.length
    } catch { /* ignore */ }
    finally { loading.value = false }
  }

  async function fetchOrder(id: number) {
    loading.value = true
    try {
      const { data } = await orderApi.show(id)
      if (data.success) currentOrder.value = data.data
    } catch { /* ignore */ }
    finally { loading.value = false }
  }

  async function cancelOrder(id: number, reason: string) {
    const { data } = await orderApi.cancel(id, reason)
    if (data.success) {
      updateLocalOrder(id, data.data)
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  async function updateStatus(id: number, status: string, note?: string) {
    const { data } = await orderApi.updateStatus(id, status, note)
    if (data.success) {
      updateLocalOrder(id, data.data)
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  function updateLocalOrder(id: number, updated: Order) {
    const idx = orders.value.findIndex(o => o.id === id)
    if (idx !== -1) orders.value[idx] = updated
    if (currentOrder.value?.id === id) currentOrder.value = updated
  }

  return { orders, currentOrder, loading, currentPage, lastPage, total, fetchOrders, fetchOrder, cancelOrder, updateStatus }
})
