import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.type === 'admin')

  function init() {
    token.value = localStorage.getItem('admin_token')
    const stored = localStorage.getItem('admin_user')
    if (stored) {
      try { user.value = JSON.parse(stored) } catch { /* ignore */ }
    }
  }

  async function login(phone: string, password: string) {
    loading.value = true
    try {
      const { data } = await authApi.login(phone, password)
      if (data.success) {
        if (data.data.user.type !== 'admin') {
          return { success: false, message: 'هذا الحساب ليس مدير نظام' }
        }
        token.value = data.data.token
        user.value = data.data.user
        localStorage.setItem('admin_token', data.data.token)
        localStorage.setItem('admin_user', JSON.stringify(data.data.user))
        return { success: true }
      }
      return { success: false, message: data.message }
    } catch (err: any) {
      return { success: false, message: err.response?.data?.message || 'خطأ في تسجيل الدخول' }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await authApi.logout() } catch { /* ignore */ }
    token.value = null
    user.value = null
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_user')
  }

  init()

  return { user, token, loading, isAuthenticated, isAdmin, login, logout }
})
