<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const phone = ref('')
const password = ref('')
const error = ref('')

async function handleLogin() {
  error.value = ''
  const result = await auth.login(phone.value, password.value)
  if (result.success) {
    router.push('/')
  } else {
    error.value = result.message || 'خطأ في تسجيل الدخول'
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="card w-full max-w-md">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-shield-halved"></i> سافو</h1>
        <p class="text-gray-500 mt-1">لوحة إدارة النظام</p>
      </div>

      <div v-if="error" class="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm">{{ error }}</div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="label">رقم الهاتف</label>
          <input v-model="phone" type="text" class="input-field" placeholder="77xxxxxxx" maxlength="9" required />
        </div>
        <div>
          <label class="label">كلمة المرور</label>
          <input v-model="password" type="password" class="input-field" placeholder="••••••••" required />
        </div>
        <button type="submit" class="btn-primary w-full" :disabled="auth.loading">
          {{ auth.loading ? 'جاري الدخول...' : 'تسجيل الدخول' }}
        </button>
      </form>


    </div>
  </div>
</template>
