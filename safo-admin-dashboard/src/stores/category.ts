import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoryApi } from '@/api'
import type { Category } from '@/types'

export const useCategoryStore = defineStore('category', () => {
  const categories = ref<Category[]>([])
  const loading = ref(false)

  async function fetchCategories() {
    loading.value = true
    try {
      const { data } = await categoryApi.list()
      if (data.success) categories.value = data.data
    } catch { /* ignore */ }
    finally { loading.value = false }
  }

  async function createCategory(catData: Record<string, any>) {
    const { data } = await categoryApi.create(catData)
    if (data.success) {
      categories.value.push(data.data)
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  async function updateCategory(id: number, catData: Record<string, any>) {
    const { data } = await categoryApi.update(id, catData)
    if (data.success) {
      const idx = categories.value.findIndex(c => c.id === id)
      if (idx !== -1) categories.value[idx] = data.data
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  async function deleteCategory(id: number) {
    const { data } = await categoryApi.delete(id)
    if (data.success) {
      categories.value = categories.value.filter(c => c.id !== id)
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  return { categories, loading, fetchCategories, createCategory, updateCategory, deleteCategory }
})
