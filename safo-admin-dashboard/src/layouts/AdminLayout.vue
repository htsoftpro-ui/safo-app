<script setup lang="ts">
import { ref } from 'vue'
import { RouterView, RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const sidebarOpen = ref(true)

const navItems = [
  { name: 'لوحة التحكم', route: '/', icon: '📊' },
  { name: 'المستخدمون', route: '/users', icon: '👥' },
  { name: 'الفئات', route: '/categories', icon: '📂' },
  { name: 'المنتجات', route: '/products', icon: '📦' },
  { name: 'الطلبات', route: '/orders', icon: '🛒' },
  { name: 'التقارير', route: '/reports', icon: '📈' },
]

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex-shrink-0 flex flex-col" :class="{ 'hidden': !sidebarOpen }">
      <div class="p-4 border-b border-gray-700">
        <h1 class="text-xl font-bold">⚙️ سافو</h1>
        <p class="text-sm text-gray-400 mt-1">لوحة الإدارة</p>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        <RouterLink
          v-for="item in navItems"
          :key="item.route"
          :to="item.route"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 transition-colors"
          active-class="bg-blue-600"
        >
          <span class="text-lg">{{ item.icon }}</span>
          <span>{{ item.name }}</span>
        </RouterLink>
      </nav>

      <div class="p-4 border-t border-gray-700">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center text-sm">
            {{ auth.user?.name?.charAt(0) }}
          </div>
          <div>
            <p class="text-sm font-medium">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-400">مدير النظام</p>
          </div>
        </div>
        <button @click="handleLogout" class="w-full text-left text-sm text-red-400 hover:text-red-300 py-1">
          تسجيل الخروج
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col">
      <header class="bg-white shadow-sm border-b px-6 py-3 flex items-center justify-between">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-600">☰</button>
        <div></div>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
          <span class="badge bg-red-100 text-red-700">Admin</span>
        </div>
      </header>

      <main class="flex-1 p-6 overflow-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>
