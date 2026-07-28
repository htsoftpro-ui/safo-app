import { defineStore } from 'pinia'
import { ref } from 'vue'
import { userApi } from '@/api'
import type { User } from '@/types'

export const useUserStore = defineStore('user', () => {
  const users = ref<User[]>([])
  const currentUser = ref<User | null>(null)
  const loading = ref(false)
  const currentPage = ref(1)
  const lastPage = ref(1)
  const total = ref(0)

  async function fetchUsers(params: Record<string, any> = {}) {
    loading.value = true
    try {
      const { data } = await userApi.list({ per_page: 20, ...params })
      users.value = data.data
      currentPage.value = data.meta?.current_page || 1
      lastPage.value = data.meta?.last_page || 1
      total.value = data.meta?.total || data.data.length
    } catch { /* ignore */ }
    finally { loading.value = false }
  }

  async function fetchUser(id: number) {
    loading.value = true
    try {
      const { data } = await userApi.show(id)
      if (data.success) currentUser.value = data.data
    } catch { /* ignore */ }
    finally { loading.value = false }
  }

  async function toggleStatus(id: number) {
    const { data } = await userApi.toggleStatus(id)
    if (data.success) {
      const idx = users.value.findIndex(u => u.id === id)
      if (idx !== -1) users.value[idx].is_active = data.data.is_active
      return { success: true }
    }
    return { success: false }
  }

  async function verifySupplier(id: number) {
    const { data } = await userApi.verifySupplier(id)
    return { success: data.success }
  }

  async function deleteUser(id: number) {
    const { data } = await userApi.delete(id)
    if (data.success) {
      users.value = users.value.filter(u => u.id !== id)
      return { success: true }
    }
    return { success: false, message: data.message }
  }

  return { users, currentUser, loading, currentPage, lastPage, total, fetchUsers, fetchUser, toggleStatus, verifySupplier, deleteUser }
})
