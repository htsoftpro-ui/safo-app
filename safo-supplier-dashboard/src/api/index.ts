import axios from 'axios'
import router from '@/router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor — attach token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor — handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 || error.response?.status === 403) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

export default api

// ─── Auth ────────────────────────────────────────────

export const authApi = {
  login: (phone: string, password: string) =>
    api.post('/auth/login', { phone, password, role: 'supplier' }),
  register: (data: Record<string, string>) =>
    api.post('/auth/register', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
}

// ─── Dashboard ──────────────────────────────────────

export const dashboardApi = {
  stats: () => api.get('/supplier/dashboard'),
}

// ─── Products ───────────────────────────────────────

export const productApi = {
  list: (params?: Record<string, any>) =>
    api.get('/supplier/products', { params }),
  create: (data: Record<string, any>) =>
    api.post('/supplier/products', data),
  update: (id: number, data: Record<string, any>) =>
    api.put(`/supplier/products/${id}`, data),
  delete: (id: number) =>
    api.delete(`/supplier/products/${id}`),
  updateStock: (id: number, quantity: number, action: string = 'set') =>
    api.patch(`/supplier/products/${id}/stock`, { quantity, action }),
  uploadImage: (id: number, file: File) => {
    const formData = new FormData()
    formData.append('image', file)
    return api.post(`/supplier/products/${id}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  deleteImage: (id: number, url: string) =>
    api.delete(`/supplier/products/${id}/image`, { data: { url } }),
}

// ─── Orders ─────────────────────────────────────────

export const orderApi = {
  list: (params?: Record<string, any>) =>
    api.get('/supplier/orders', { params }),
  show: (id: number) =>
    api.get(`/supplier/orders/${id}`),
  accept: (id: number) =>
    api.post(`/supplier/orders/${id}/accept`),
  reject: (id: number, reason: string) =>
    api.post(`/supplier/orders/${id}/reject`, { reason }),
  process: (id: number) =>
    api.post(`/supplier/orders/${id}/process`),
  ready: (id: number) =>
    api.post(`/supplier/orders/${id}/ready`),
  ship: (id: number, notes?: string) =>
    api.post(`/supplier/orders/${id}/ship`, { notes }),
}

// ─── Profile ────────────────────────────────────────

export const profileApi = {
  show: () => api.get('/profile'),
  update: (data: Record<string, any>) =>
    api.put('/profile', data),
  changePassword: (current_password: string, password: string, password_confirmation: string) =>
    api.post('/profile/change-password', { current_password, password, password_confirmation }),
}

// ─── Categories ─────────────────────────────────────

export const categoryApi = {
  list: () => api.get('/categories'),
}
