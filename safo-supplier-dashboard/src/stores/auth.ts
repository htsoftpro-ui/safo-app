import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi, profileApi } from '@/api'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isSupplier = computed(() => user.value?.type === 'supplier')

  function init() {
    token.value = localStorage.getItem('token')
    const stored = localStorage.getItem('user')
    if (stored) {
      try { user.value = JSON.parse(stored) } catch { /* ignore */ }
    }
  }

  async function login(phone: string, password: string) {
    loading.value = true
    try {
      const { data } = await authApi.login(phone, password)
      if (data.success) {
        token.value = data.data.token
        user.value = data.data.user
        localStorage.setItem('token', data.data.token)
        localStorage.setItem('user', JSON.stringify(data.data.user))
        return { success: true }
      }
      return { success: false, message: data.message }
    } catch (err: any) {
      return {
        success: false,
        message: err.response?.data?.message || 'خطأ في تسجيل الدخول',
      }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await authApi.logout() } catch { /* ignore */ }
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  async function fetchProfile() {
    try {
      const { data } = await profileApi.show()
      if (data.success) {
        user.value = data.data
        localStorage.setItem('user', JSON.stringify(data.data))
      }
    } catch { /* ignore */ }
  }

  init()

  return { user, token, loading, isAuthenticated, isSupplier, login, logout, fetchProfile }
})
