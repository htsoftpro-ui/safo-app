import axios from 'axios'
import router from '@/router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 || error.response?.status === 403) {
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_user')
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

export default api

// ─── Auth ─────────────────────────────────────
export const authApi = {
  login: (phone: string, password: string) => api.post('/auth/login', { phone, password, role: 'admin' }),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
}

// ─── Dashboard ───────────────────────────────
export const dashboardApi = {
  stats: () => api.get('/admin/dashboard'),
}

// ─── Users ───────────────────────────────────
export const userApi = {
  list: (params?: Record<string, any>) => api.get('/admin/users', { params }),
  show: (id: number) => api.get(`/admin/users/${id}`),
  create: (data: Record<string, any>) => api.post('/admin/users', data),
  toggleStatus: (id: number) => api.patch(`/admin/users/${id}/toggle-status`),
  verifySupplier: (id: number) => api.patch(`/admin/users/${id}/verify-supplier`),
  updateRole: (id: number, type: string) => api.put(`/admin/users/${id}/role`, { type }),
  delete: (id: number) => api.delete(`/admin/users/${id}`),
}

// ─── Categories ──────────────────────────────
export const categoryApi = {
  list: (params?: Record<string, any>) => api.get('/admin/categories', { params }),
  create: (data: Record<string, any>) => api.post('/admin/categories', data),
  update: (id: number, data: Record<string, any>) => api.put(`/admin/categories/${id}`, data),
  delete: (id: number) => api.delete(`/admin/categories/${id}`),
}

// ─── Products ────────────────────────────────
export const productApi = {
  list: (params?: Record<string, any>) => api.get('/admin/products', { params }),
  show: (id: number) => api.get(`/admin/products/${id}`),
  toggleActive: (id: number) => api.patch(`/admin/products/${id}/toggle-active`),
  toggleFeatured: (id: number) => api.patch(`/admin/products/${id}/toggle-featured`),
  delete: (id: number) => api.delete(`/admin/products/${id}`),
}

// ─── Orders ──────────────────────────────────
export const orderApi = {
  list: (params?: Record<string, any>) => api.get('/admin/orders', { params }),
  show: (id: number) => api.get(`/admin/orders/${id}`),
  cancel: (id: number, reason: string) => api.post(`/admin/orders/${id}/cancel`, { reason }),
  updateStatus: (id: number, status: string, note?: string) => api.patch(`/admin/orders/${id}/status`, { status, note }),
}

// ─── Reports ─────────────────────────────────
export const reportApi = {
  sales: (params?: Record<string, any>) => api.get('/admin/reports/sales', { params }),
  suppliers: (params?: Record<string, any>) => api.get('/admin/reports/suppliers', { params }),
  users: () => api.get('/admin/reports/users'),
  financial: () => api.get('/admin/reports/financial'),
}
