import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('@/pages/DashboardPage.vue') },
      { path: 'products', name: 'products', component: () => import('@/pages/ProductsPage.vue') },
      { path: 'products/create', name: 'product-create', component: () => import('@/pages/ProductFormPage.vue') },
      { path: 'products/:id/edit', name: 'product-edit', component: () => import('@/pages/ProductFormPage.vue') },
      { path: 'orders', name: 'orders', component: () => import('@/pages/OrdersPage.vue') },
      { path: 'orders/:id', name: 'order-detail', component: () => import('@/pages/OrderDetailPage.vue') },
      { path: 'profile', name: 'profile', component: () => import('@/pages/ProfilePage.vue') },
    ],
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) return '/login'
  if (to.meta.guest && auth.isAuthenticated) return '/'

  // Role guard: only supplier users can access supplier routes
  if (to.meta.requiresAuth && auth.isAuthenticated && !auth.isApprovedSupplier) {
    auth.logout()
    return '/login'
  }
})

export default router
