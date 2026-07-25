<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { profileApi } from '@/api'

const auth = useAuthStore()
const success = ref('')
const error = ref('')

// Profile form
const profileForm = ref({
  name: auth.user?.name || '',
  email: '',
  city: auth.user?.city || '',
})

// Password form
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})

async function updateProfile() {
  error.value = ''
  success.value = ''
  try {
    const { data } = await profileApi.update(profileForm.value)
    if (data.success) {
      auth.fetchProfile()
      success.value = 'تم تحديث الملف الشخصي'
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'فشل التحديث'
  }
}

async function changePassword() {
  error.value = ''
  success.value = ''
  try {
    const { data } = await profileApi.changePassword(
      passwordForm.value.current_password,
      passwordForm.value.password,
      passwordForm.value.password_confirmation,
    )
    if (data.success) {
      success.value = 'تم تغيير كلمة المرور'
      passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'فشل تغيير كلمة المرور'
  }
}
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold mb-6">الملف الشخصي</h2>

    <div v-if="success" class="bg-green-50 text-green-700 p-3 rounded-lg mb-4 text-sm">{{ success }}</div>
    <div v-if="error" class="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm">{{ error }}</div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Profile Info -->
      <div class="card">
        <h3 class="font-bold mb-4">معلومات المتجر</h3>
        <div class="space-y-3 text-sm">
          <div><span class="text-gray-500">الاسم:</span> {{ auth.user?.name }}</div>
          <div><span class="text-gray-500">الهاتف:</span> {{ auth.user?.phone }}</div>
          <div><span class="text-gray-500">المتجر:</span> {{ auth.user?.store_name || '-' }}</div>
          <div><span class="text-gray-500">المدينة:</span> {{ auth.user?.city || '-' }}</div>
          <div>
            <span class="text-gray-500">التحقق:</span>
            <span :class="auth.user?.is_verified ? 'text-green-600' : 'text-yellow-600'">
              {{ auth.user?.is_verified ? '✓ محقق' : 'في الانتظار' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Update Profile -->
      <div class="card">
        <h3 class="font-bold mb-4">تحديث المعلومات</h3>
        <form @submit.prevent="updateProfile" class="space-y-3">
          <div>
            <label class="label">الاسم</label>
            <input v-model="profileForm.name" type="text" class="input-field" />
          </div>
          <div>
            <label class="label">البريد الإلكتروني</label>
            <input v-model="profileForm.email" type="email" class="input-field" />
          </div>
          <div>
            <label class="label">المدينة</label>
            <input v-model="profileForm.city" type="text" class="input-field" />
          </div>
          <button type="submit" class="btn-primary">تحديث</button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="card">
        <h3 class="font-bold mb-4">تغيير كلمة المرور</h3>
        <form @submit.prevent="changePassword" class="space-y-3">
          <div>
            <label class="label">كلمة المرور الحالية</label>
            <input v-model="passwordForm.current_password" type="password" class="input-field" required />
          </div>
          <div>
            <label class="label">كلمة المرور الجديدة</label>
            <input v-model="passwordForm.password" type="password" class="input-field" minlength="6" required />
          </div>
          <div>
            <label class="label">تأكيد كلمة المرور</label>
            <input v-model="passwordForm.password_confirmation" type="password" class="input-field" required />
          </div>
          <button type="submit" class="btn-primary">تغيير</button>
        </form>
      </div>
    </div>
  </div>
</template>
