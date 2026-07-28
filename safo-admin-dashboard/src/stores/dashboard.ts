import { defineStore } from 'pinia'
import { ref } from 'vue'
import { dashboardApi } from '@/api'
import type { DashboardStats } from '@/types'

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref<DashboardStats | null>(null)
  const loading = ref(false)

  async function fetchStats() {
    loading.value = true
    try {
      const { data } = await dashboardApi.stats()
      if (data.success) stats.value = data.data
    } catch { /* ignore */ }
    finally { loading.value = false }
  }

  return { stats, loading, fetchStats }
})
