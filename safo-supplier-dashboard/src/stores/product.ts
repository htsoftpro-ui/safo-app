import { defineStore } from 'pinia'
import { ref } from 'vue'
import { productApi, categoryApi } from '@/api'
import type { Product, Category } from '@/types'

export const useProductStore = defineStore('product', () => {
  const products = ref<Product[]>([])
  const categories = ref<Category[]>([])
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

  async function fetchCategories() {
    try {
      const { data } = await categoryApi.list()
      if (data.success) categories.value = data.data
    } catch { /* ignore */ }
  }

  async function createProduct(productData: Record<string, any>) {
    const { data } = await productApi.create(productData)
    if (data.success) {
      products.value.unshift(data.data)
      return { success: true, product: data.data }
    }
    return { success: false, message: data.message }
  }

  async function updateProduct(id: number, productData: Record<string, any>) {
    const { data } = await productApi.update(id, productData)
    if (data.success) {
      const idx = products.value.findIndex(p => p.id === id)
      if (idx !== -1) products.value[idx] = data.data
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  async function deleteProduct(id: number) {
    const { data } = await productApi.delete(id)
    if (data.success) {
      products.value = products.value.filter(p => p.id !== id)
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  async function updateStock(id: number, quantity: number, action: string = 'set') {
    const { data } = await productApi.updateStock(id, quantity, action)
    if (data.success) {
      const idx = products.value.findIndex(p => p.id === id)
      if (idx !== -1) {
        products.value[idx].stock_quantity = data.data.stock_quantity
        products.value[idx].is_low_stock = data.data.is_low_stock
      }
      return { success: true }
    }
    return { success: false }
  }

  return {
    products, categories, loading, currentPage, lastPage, total,
    fetchProducts, fetchCategories, createProduct, updateProduct, deleteProduct, updateStock,
  }
})
