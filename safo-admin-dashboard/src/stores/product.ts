import { defineStore } from 'pinia'
import { ref } from 'vue'
import { productApi } from '@/api'
import type { Product } from '@/types'

export const useProductStore = defineStore('product', () => {
  const products = ref<Product[]>([])
  const loading = ref(false)
  const currentPage = ref(1)
  const lastPage = ref(1)
  const total = ref(0)

  async function fetchProducts(params: Record<string, any> = {}) {
    loading.value = true
    try {
      const { data } = await productApi.list({ per_page: 20, ...params })
      products.value = data.data
      currentPage.value = data.meta?.current_page || 1
      lastPage.value = data.meta?.last_page || 1
      total.value = data.meta?.total || data.data.length
    } catch { /* ignore */ }
    finally { loading.value = false }
  }

  async function toggleActive(id: number) {
    const { data } = await productApi.toggleActive(id)
    if (data.success) {
      const idx = products.value.findIndex(p => p.id === id)
      if (idx !== -1) products.value[idx].is_active = data.data.is_active
      return { success: true }
    }
    return { success: false }
  }

  async function toggleFeatured(id: number) {
    const { data } = await productApi.toggleFeatured(id)
    if (data.success) {
      const idx = products.value.findIndex(p => p.id === id)
      if (idx !== -1) products.value[idx].is_featured = data.data.is_featured
      return { success: true }
    }
    return { success: false }
  }

  async function deleteProduct(id: number) {
    const { data } = await productApi.delete(id)
    if (data.success) {
      products.value = products.value.filter(p => p.id !== id)
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  return { products, loading, currentPage, lastPage, total, fetchProducts, toggleActive, toggleFeatured, deleteProduct }
})
