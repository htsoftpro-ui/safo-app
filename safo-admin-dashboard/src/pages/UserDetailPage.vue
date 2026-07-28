<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'

const store = useUserStore()
const route = useRoute()
const router = useRouter()
const userId = computed(() => Number(route.params.id))

onMounted(() => store.fetchUser(userId.value))
const user = computed(() => store.currentUser)
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <button @click="router.back()" class="text-gray-600 hover:text-gray-900">← رجوع</button>
      <h2 class="text-2xl font-bold">تفاصيل المستخدم</h2>
    </div>

    <div v-if="!user" class="text-center py-12 text-gray-500">جاري التحميل...</div>

    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
          <h3 class="font-bold mb-4">المعلومات الأساسية</h3>
          <div class="space-y-3 text-sm">
            <div><span class="text-gray-500">الاسم:</span> {{ user.name }}</div>
            <div><span class="text-gray-500">الهاتف:</span> {{ user.phone }}</div>
            <div><span class="text-gray-500">البريد:</span> {{ user.email || '-' }}</div>
            <div><span class="text-gray-500">النوع:</span> {{ user.type === 'admin' ? 'مدير' : user.type === 'supplier' ? 'مورد' : 'عميل' }}</div>
            <div><span class="text-gray-500">المدينة:</span> {{ user.city || '-' }}</div>
            <div><span class="text-gray-500">الحالة:</span> <span :class="user.is_active ? 'text-green-600' : 'text-red-600'">{{ user.is_active ? 'نشط' : 'معطل' }}</span></div>
          </div>
        </div>

        <div v-if="user.supplier" class="card">
          <h3 class="font-bold mb-4">معلومات المورد</h3>
          <div class="space-y-3 text-sm">
            <div><span class="text-gray-500">الشركة:</span> {{ user.supplier.company_name }}</div>
            <div><span class="text-gray-500">التوثيق:</span> <span :class="user.supplier.is_verified ? 'text-green-600' : 'text-yellow-600'">{{ user.supplier.is_verified ? 'موثق' : 'غير موثق' }}</span></div>
            <div><span class="text-gray-500">التقييم:</span> {{ user.supplier.rating }}/5</div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
